<?php
namespace TMDBImporter\Core\Contracts;

interface RepositoryInterface
{
    public function findById(int $id): mixed;
    public function save(mixed $object): void;
    public function delete(mixed $object): void;
}