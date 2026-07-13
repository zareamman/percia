<?php
namespace TMDBImporter\Background\Jobs;

use TMDBImporter\Domain\ImportJob;
use TMDBImporter\Services\ImportService;
use TMDBImporter\Infrastructure\Logging\Logger;

abstract class BackgroundJob
{
    protected ImportJob $job;
    protected ImportService $importService;
    protected Logger $logger;

    public function __construct(ImportJob $job, ImportService $importService, Logger $logger)
    {
        $this->job = $job;
        $this->importService = $importService;
        $this->logger = $logger;
    }

    abstract public function run(): void;

    protected function updateProgress(float $progress): void
    {
        $this->job->setProgress($progress);
    }

    protected function log(string $message, array $context = []): void
    {
        $this->logger->info($message, array_merge(['job_id' => $this->job->getId()], $context));
    }

    protected function error(string $message, \Throwable $e): void
    {
        $this->logger->error($message, ['job_id' => $this->job->getId(), 'error' => $e->getMessage()]);
    }
}