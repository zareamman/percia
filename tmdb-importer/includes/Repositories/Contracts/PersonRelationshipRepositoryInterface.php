<?php
namespace TMDBImporter\Repositories\Contracts;

interface PersonRelationshipRepositoryInterface
{
    public function findById(int $id): ?\TMDBImporter\Domain\PersonRelationship;
    public function findByPersonAndObject(int $personTermId, string $objectType, int $objectId): array;
    public function findByObject(string $objectType, int $objectId): array;
    public function save(\TMDBImporter\Domain\PersonRelationship $relationship): void;
    public function delete(int $id): bool;
    public function deleteByObject(string $objectType, int $objectId): int;
    public function deleteByPersonAndObject(int $personTermId, string $objectType, int $objectId): int;
}