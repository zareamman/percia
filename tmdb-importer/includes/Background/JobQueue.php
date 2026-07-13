<?php
namespace TMDBImporter\Background;

use TMDBImporter\Domain\ImportJob;
use TMDBImporter\Repositories\Database\ImportJobRepository;
use TMDBImporter\Infrastructure\Logging\Logger;

class JobQueue
{
    private ImportJobRepository $repository;
    private Logger $logger;
    private const MAX_CONCURRENT = 1;

    public function __construct(Logger $logger)
    {
        $this->repository = new ImportJobRepository();
        $this->logger = $logger;
    }

    public function add(ImportJob $job): void
    {
        $this->repository->save($job);
    }

    public function getNextPending(): ?ImportJob
    {
        $jobs = $this->repository->findPending(1);
        return $jobs[0] ?? null;
    }

    public function claim(int $jobId): bool
    {
        $job = $this->repository->findById($jobId);
        if (!$job || $job->getStatus() !== ImportJob::STATUS_PENDING) {
            return false;
        }

        $job->setStatus(ImportJob::STATUS_RUNNING);
        $job->setStartedAt(current_time('mysql', true));
        $this->repository->save($job);

        return true;
    }

    public function release(int $jobId): void
    {
        $job = $this->repository->findById($jobId);
        if ($job && $job->getStatus() === ImportJob::STATUS_RUNNING) {
            $job->setStatus(ImportJob::STATUS_PENDING);
            $this->repository->save($job);
        }
    }

    public function complete(int $jobId): void
    {
        $job = $this->repository->findById($jobId);
        if ($job) {
            $job->setStatus(ImportJob::STATUS_COMPLETED);
            $job->setCompletedAt(current_time('mysql', true));
            $job->setProgress(100);
            $this->repository->save($job);
        }
    }

    public function fail(int $jobId, string $error): void
    {
        $job = $this->repository->findById($jobId);
        if ($job) {
            $job->setStatus(ImportJob::STATUS_FAILED);
            $job->addError($error);
            $job->setCompletedAt(current_time('mysql', true));
            $this->repository->save($job);
        }
    }

    public function getRunningCount(): int
    {
        $jobs = $this->repository->findByStatus(ImportJob::STATUS_RUNNING);
        return count($jobs);
    }

    public function hasCapacity(): bool
    {
        return $this->getRunningCount() < self::MAX_CONCURRENT;
    }
}