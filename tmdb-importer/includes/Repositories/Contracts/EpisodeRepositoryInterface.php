<?php
namespace TMDBImporter\Repositories\Contracts;

interface EpisodeRepositoryInterface
{
    public function findById(int $id): ?\TMDBImporter\Domain\Episode;
    public function findByTmdbId(int $tmdbId): ?\TMDBImporter\Domain\Episode;
    public function findBySeasonAndNumber(int $seasonId, int $episodeNumber): ?\TMDBImporter\Domain\Episode;
    public function findBySeasonId(int $seasonId): array;
    public function save(\TMDBImporter\Domain\Episode $episode): void;
    public function delete(int $id): bool;
    public function deleteBySeasonId(int $seasonId): int;
}