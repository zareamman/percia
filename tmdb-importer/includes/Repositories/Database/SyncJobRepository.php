<?php
namespace TMDBImporter\Repositories\Database;

use TMDBImporter\Domain\SyncJob;
use TMDBImporter\Repositories\Contracts\SyncJobRepositoryInterface;

class SyncJobRepository implements SyncJobRepositoryInterface
{
    private wpdb $wpdb;
    private string $table;

    public function __construct()
    {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->table = $wpdb->prefix . 'tmdb_sync_jobs';
    }

    public function findById(int $id): ?SyncJob
    {
        $row = $this->wpdb->get_row($this->wpdb->prepare("SELECT * FROM {$this->table} WHERE id = %d", $id), ARRAY_A);
        return $row ? $this->mapRow($row) : null;
    }

    public function findByStatus(string $status): array
    {
        $rows = $this->wpdb->get_results(
            $this->wpdb->prepare("SELECT * FROM {$this->table} WHERE status = %s ORDER BY created_at ASC", $status),
            ARRAY_A
        );
        return array_map([$this, 'mapRow'], $rows);
    }

    public function findPending(int $limit = 10): array
    {
        $rows = $this->wpdb->get_results(
            $this->wpdb->prepare("SELECT * FROM {$this->table} WHERE status = %s ORDER BY created_at ASC LIMIT %d", SyncJob::STATUS_PENDING, $limit),
            ARRAY_A
        );
        return array_map([$this, 'mapRow'], $rows);
    }

    public function findScheduled(): array
    {
        $rows = $this->wpdb->get_results(
            $this->wpdb->prepare("SELECT * FROM {$this->table} WHERE status = %s AND scheduled_at <= %s ORDER BY scheduled_at ASC", SyncJob::STATUS_PENDING, current_time('mysql', true)),
            ARRAY_A
        );
        return array_map([$this, 'mapRow'], $rows);
    }

    public function save(SyncJob $job): void
    {
        $data = [
            'type' => $job->getType(),
            'object_ids' => wp_json_encode($job->getObjectIds()),
            'sync_options' => wp_json_encode($job->getSyncOptions()),
            'status' => $job->getStatus(),
            'progress' => $job->getProgress(),
            'processed_ids' => wp_json_encode($job->getProcessedIds()),
            'failed_ids' => wp_json_encode($job->getFailedIds()),
            'errors' => wp_json_encode($job->getErrors()),
            'scheduled_at' => $job->getScheduledAt(),
            'started_at' => $job->getStartedAt(),
            'completed_at' => $job->getCompletedAt(),
        ];

        if ($job->getId() > 0) {
            $this->wpdb->update(
                $this->table,
                $data,
                ['id' => $job->getId()],
                ['%s', '%s', '%s', '%s', '%f', '%s', '%s', '%s', '%s', '%s', '%s'],
                ['%d']
            );
        } else {
            $data['created_at'] = current_time('mysql', true);
            $this->wpdb->insert(
                $this->table,
                $data,
                ['%s', '%s', '%s', '%s', '%f', '%s', '%s', '%s', '%s', '%s', '%s', '%s']
            );
            $job->setId((int) $this->wpdb->insert_id);
        }
    }

    public function delete(int $id): bool
    {
        $result = $this->wpdb->delete($this->table, ['id' => $id], ['%d']);
        return $result !== false;
    }

    private function mapRow(array $row): SyncJob
    {
        $job = new SyncJob();
        $job->setId((int) $row['id'])
            ->setType($row['type'])
            ->setObjectIds(json_decode($row['object_ids'] ?? '[]', true))
            ->setSyncOptions(json_decode($row['sync_options'] ?? '{}', true))
            ->setStatus($row['status'])
            ->setProgress((float) $row['progress'])
            ->setProcessedIds(json_decode($row['processed_ids'] ?? '[]', true))
            ->setFailedIds(json_decode($row['failed_ids'] ?? '[]', true))
            ->setErrors(json_decode($row['errors'] ?? '[]', true))
            ->setScheduledAt($row['scheduled_at'])
            ->setStartedAt($row['started_at'])
            ->setCompletedAt($row['completed_at']);
        return $job;
    }
}