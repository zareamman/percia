<?php
namespace TMDBImporter\Repositories\Database;

use TMDBImporter\Domain\ImportJob;
use TMDBImporter\Repositories\Contracts\ImportJobRepositoryInterface;

class ImportJobRepository implements ImportJobRepositoryInterface
{
    private wpdb $wpdb;
    private string $table;

    public function __construct()
    {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->table = $wpdb->prefix . 'tmdb_import_jobs';
    }

    public function findById(int $id): ?ImportJob
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
            $this->wpdb->prepare("SELECT * FROM {$this->table} WHERE status = %s ORDER BY created_at ASC LIMIT %d", ImportJob::STATUS_PENDING, $limit),
            ARRAY_A
        );
        return array_map([$this, 'mapRow'], $rows);
    }

    public function save(ImportJob $job): void
    {
        $data = [
            'type' => $job->getType(),
            'tmdb_ids' => wp_json_encode($job->getTmdbIds()),
            'import_options' => wp_json_encode($job->getImportOptions()),
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

    private function mapRow(array $row): ImportJob
    {
        $job = new ImportJob();
        $job->setId((int) $row['id'])
            ->setType($row['type'])
            ->setTmdbIds(json_decode($row['tmdb_ids'] ?? '[]', true))
            ->setImportOptions(json_decode($row['import_options'] ?? '{}', true))
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