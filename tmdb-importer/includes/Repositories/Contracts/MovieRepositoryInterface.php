<?php
namespace TMDBImporter\Repositories\Contracts;

interface MovieRepositoryInterface
{
    public function findById(int $id): ?\TMDBImporter\Domain\Movie;
    public function findByTmdbId(int $tmdbId): ?\TMDBImporter\Domain\Movie;
    public function save(\TMDBImporter\Domain\Movie $movie): void;
    public function delete(int $id): bool;
    public function findByGenre(int $genreTermId, int $limit = 20, int $offset = 0): array;
    public function findByYear(int $year, int $limit = 20, int $offset = 0): array;
}