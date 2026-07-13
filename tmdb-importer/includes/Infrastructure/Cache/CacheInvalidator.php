<?php
namespace TMDBImporter\Infrastructure\Cache;

use TMDBImporter\Infrastructure\Cache\ObjectCache;
use TMDBImporter\Infrastructure\Cache\CacheKeyGenerator;

class CacheInvalidator
{
    private ObjectCache $cache;

    public function __construct(ObjectCache $cache)
    {
        $this->cache = $cache;
    }

    public function invalidateMovie(int $tmdbId): void
    {
        $this->cache->delete(CacheKeyGenerator::tmdbMovie($tmdbId));
        $this->cache->delete(CacheKeyGenerator::tmdbMovieImages($tmdbId));
        $this->cache->delete(CacheKeyGenerator::tmdbMovieVideos($tmdbId));
    }

    public function invalidateTVShow(int $tmdbId): void
    {
        $this->cache->delete(CacheKeyGenerator::tmdbTVShow($tmdbId));
        $this->cache->delete(CacheKeyGenerator::tmdbTVShowImages($tmdbId));
        $this->cache->delete(CacheKeyGenerator::tmdbTVShowVideos($tmdbId));
    }

    public function invalidateSeason(int $showId, int $seasonNumber): void
    {
        $this->cache->delete(CacheKeyGenerator::tmdbSeason($showId, $seasonNumber));
        $this->cache->delete(CacheKeyGenerator::tmdbEpisodes($showId, $seasonNumber));
    }

    public function invalidatePerson(int $tmdbId): void
    {
        $this->cache->delete(CacheKeyGenerator::tmdbPerson($tmdbId));
        $this->cache->delete(CacheKeyGenerator::tmdbPersonImages($tmdbId));
    }

    public function invalidateTaxonomy(string $taxonomy, int $tmdbId): void
    {
        $this->cache->delete(CacheKeyGenerator::taxonomyTerm($taxonomy, $tmdbId));
    }

    public function invalidateImportJob(int $jobId): void
    {
        $this->cache->delete(CacheKeyGenerator::importJob($jobId));
    }

    public function invalidateSyncJob(int $jobId): void
    {
        $this->cache->delete(CacheKeyGenerator::syncJob($jobId));
    }

    public function invalidateSettings(): void
    {
        $this->cache->delete(CacheKeyGenerator::settings());
        $this->cache->delete(CacheKeyGenerator::importSettings());
        $this->cache->delete(CacheKeyGenerator::imageSettings());
        $this->cache->delete(CacheKeyGenerator::videoSettings());
    }

    public function invalidateAll(): void
    {
        $this->cache->flush();
    }

    public function invalidateOnImport(string $type, array $ids): void
    {
        foreach ($ids as $id) {
            match ($type) {
                'movie' => $this->invalidateMovie($id),
                'tv_show' => $this->invalidateTVShow($id),
                'season' => $this->invalidateSeason($id['show_id'], $id['season_number']),
                'episode' => $this->invalidateSeason($id['show_id'], $id['season_number']),
                'person' => $this->invalidatePerson($id),
                default => null,
            };
        }
    }

    public function invalidateOnSync(string $type, array $ids): void
    {
        $this->invalidateOnImport($type, $ids);
    }
}