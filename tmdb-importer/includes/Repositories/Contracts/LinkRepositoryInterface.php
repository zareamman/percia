<?php
namespace TMDBImporter\Repositories\Contracts;

interface LinkRepositoryInterface
{
    public function findById(int $id): ?\TMDBImporter\Domain\Link;
    public function findByObject(string $objectType, int $objectId): array;
    public function save(\TMDBImporter\Domain\Link $link): void;
    public function delete(int $id): bool;
    public function deleteByObject(string $objectType, int $objectId): int;
}