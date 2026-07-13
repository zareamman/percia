<?php
namespace TMDBImporter\Domain;

class ImportJob
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_RUNNING = 'running';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';
    public const STATUS_CANCELLED = 'cancelled';

    public const TYPE_MOVIE = 'movie';
    public const TYPE_TV_SHOW = 'tv_show';
    public const TYPE_SEASON = 'season';
    public const TYPE_EPISODE = 'episode';

    private int $id = 0;
    private string $type = '';
    private array $tmdbIds = [];
    private array $importOptions = [];
    private string $status = self::STATUS_PENDING;
    private float $progress = 0.0;
    private array $processedIds = [];
    private array $failedIds = [];
    private array $errors = [];
    private ?string $scheduledAt = null;
    private ?string $startedAt = null;
    private ?string $completedAt = null;

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;
        return $this;
    }

    public function getTmdbIds(): array
    {
        return $this->tmdbIds;
    }

    public function setTmdbIds(array $tmdbIds): self
    {
        $this->tmdbIds = $tmdbIds;
        return $this;
    }

    public function getImportOptions(): array
    {
        return $this->importOptions;
    }

    public function setImportOptions(array $importOptions): self
    {
        $this->importOptions = $importOptions;
        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function getProgress(): float
    {
        return $this->progress;
    }

    public function setProgress(float $progress): self
    {
        $this->progress = $progress;
        return $this;
    }

    public function getProcessedIds(): array
    {
        return $this->processedIds;
    }

    public function addProcessedId(int $id): self
    {
        $this->processedIds[] = $id;
        $this->updateProgress();
        return $this;
    }

    public function getFailedIds(): array
    {
        return $this->failedIds;
    }

    public function addFailedId(int $id): self
    {
        $this->failedIds[] = $id;
        $this->updateProgress();
        return $this;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function addError(string $error): self
    {
        $this->errors[] = $error;
        return $this;
    }

    public function getScheduledAt(): ?string
    {
        return $this->scheduledAt;
    }

    public function setScheduledAt(?string $scheduledAt): self
    {
        $this->scheduledAt = $scheduledAt;
        return $this;
    }

    public function getStartedAt(): ?string
    {
        return $this->startedAt;
    }

    public function setStartedAt(?string $startedAt): self
    {
        $this->startedAt = $startedAt;
        return $this;
    }

    public function getCompletedAt(): ?string
    {
        return $this->completedAt;
    }

    public function setCompletedAt(?string $completedAt): self
    {
        $this->completedAt = $completedAt;
        return $this;
    }

    public function isCompleted(): bool
    {
        return in_array($this->status, [self::STATUS_COMPLETED, self::STATUS_FAILED, self::STATUS_CANCELLED], true);
    }

    public function isRunning(): bool
    {
        return $this->status === self::STATUS_RUNNING;
    }

    public function canRetry(): bool
    {
        return in_array($this->status, [self::STATUS_FAILED, self::STATUS_CANCELLED], true);
    }

    private function updateProgress(): void
    {
        $total = count($this->tmdbIds);
        if ($total > 0) {
            $processed = count($this->processedIds) + count($this->failedIds);
            $this->progress = round(($processed / $total) * 100, 2);
        }
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'tmdb_ids' => $this->tmdbIds,
            'import_options' => $this->importOptions,
            'status' => $this->status,
            'progress' => $this->progress,
            'processed_ids' => $this->processedIds,
            'failed_ids' => $this->failedIds,
            'errors' => $this->errors,
            'scheduled_at' => $this->scheduledAt,
            'started_at' => $this->startedAt,
            'completed_at' => $this->completedAt,
        ];
    }
}