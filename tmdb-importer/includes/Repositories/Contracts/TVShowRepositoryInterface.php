<?php
namespace TMDBImporter\Repositories\Contracts;

interface TVShowRepositoryInterface
{
    public function findById(int $id): ?\TMDBImporter\Domain\TVShow;
    public function findByTmdbId(int $tmdbId): ?\TMDBImporter\Domain\TVShow;
    public function save(\TMDBImporter\Domain\TVShow $tvShow): void;
    public function delete(int $id): bool;
    public function findByGenre(int $genreTermId, int $limit = 20, int $offset = 0): array;
    public function findByYear(int $year, int $limit = 20, int $offset = 0): array;
    public function findByNetwork(int $networkTermId, int $limit = 20, int $offset = 0): array;
}