<?php
namespace TMDBImporter\Repositories\Contracts;

interface SyncJobRepositoryInterface
{
    public function findById(int $id): ?\TMDBImporter\Domain\SyncJob;
    public function findByStatus(string $status): array;
    public function findPending(int $limit = 10): array;
    public function findScheduled(): array;
    public function save(\TMDBImporter\Domain\SyncJob $job): void;
    public function delete(int $id): bool;
}