<?php
namespace TMDBImporter\Repositories\Contracts;

interface ImportJobRepositoryInterface
{
    public function findById(int $id): ?\TMDBImporter\Domain\ImportJob;
    public function findByStatus(string $status): array;
    public function findPending(int $limit = 10): array;
    public function save(\TMDBImporter\Domain\ImportJob $job): void;
    public function delete(int $id): bool;
}