<?php
namespace TMDBImporter\Background;

use TMDBImporter\Domain\ImportJob;
use TMDBImporter\Background\Jobs\ImportJobHandler;
use TMDBImporter\Services\ImportService;
use TMDBImporter\Infrastructure\Logging\Logger;
use TMDBImporter\Repositories\Database\ImportJobRepository;

class JobRunner
{
    private JobQueue $queue;
    private ImportService $importService;
    private Logger $logger;

    public function __construct(ImportService $importService, Logger $logger)
    {
        $this->queue = new JobQueue($logger);
        $this->importService = $importService;
        $this->logger = $logger;
    }

    public function run(): int
    {
        if (!$this->queue->hasCapacity()) {
            $this->logger->debug('Job queue at capacity, skipping');
            return 0;
        }

        $job = $this->queue->getNextPending();
        if (!$job) {
            return 0;
        }

        if (!$this->queue->claim($job->getId())) {
            $this->logger->warning('Failed to claim job', ['job_id' => $job->getId()]);
            return 0;
        }

        $this->logger->info('Starting job', ['job_id' => $job->getId(), 'type' => $job->getType()]);

        try {
            $handler = new ImportJobHandler($job, $this->importService, $this->logger);
            $handler->run();

            if ($job->getStatus() === ImportJob::STATUS_COMPLETED) {
                $this->queue->complete($job->getId());
                $this->logger->info('Job completed', ['job_id' => $job->getId()]);
            } else {
                $this->queue->fail($job->getId(), $job->getErrors()[0] ?? 'Unknown error');
                $this->logger->error('Job failed', ['job_id' => $job->getId(), 'errors' => $job->getErrors()]);
            }
        } catch (\Throwable $e) {
            $this->queue->fail($job->getId(), $e->getMessage());
            $this->logger->error('Job exception', ['job_id' => $job->getId(), 'error' => $e->getMessage()]);
        }

        return 1;
    }
}