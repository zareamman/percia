<?php
namespace TMDBImporter\Repositories\Contracts;

interface SeasonRepositoryInterface
{
    public function findById(int $id): ?\TMDBImporter\Domain\Season;
    public function findByTmdbId(int $tmdbId): ?\TMDBImporter\Domain\Season;
    public function findByShowAndNumber(int $showId, int $seasonNumber): ?\TMDBImporter\Domain\Season;
    public function findByShowId(int $showId): array;
    public function save(\TMDBImporter\Domain\Season $season): void;
    public function delete(int $id): bool;
    public function deleteByShowId(int $showId): int;
}