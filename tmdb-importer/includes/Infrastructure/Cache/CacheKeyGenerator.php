<?php
namespace TMDBImporter\Infrastructure\Cache;

class CacheKeyGenerator
{
    public static function tmdbMovie(int $tmdbId, string $language = 'en-US'): string
    {
        return "tmdb_movie:{$tmdbId}:{$language}";
    }

    public static function tmdbTVShow(int $tmdbId, string $language = 'en-US'): string
    {
        return "tmdb_tv_show:{$tmdbId}:{$language}";
    }

    public static function tmdbSeason(int $showId, int $seasonNumber, string $language = 'en-US'): string
    {
        return "tmdb_season:{$showId}:{$seasonNumber}:{$language}";
    }

    public static function tmdbEpisodes(int $showId, int $seasonNumber, string $language = 'en-US'): string
    {
        return "tmdb_episodes:{$showId}:{$seasonNumber}:{$language}";
    }

    public static function tmdbMovieImages(int $tmdbId): string
    {
        return "tmdb_movie_images:{$tmdbId}";
    }

    public static function tmdbTVShowImages(int $tmdbId): string
    {
        return "tmdb_tv_show_images:{$tmdbId}";
    }

    public static function tmdbPersonImages(int $tmdbId): string
    {
        return "tmdb_person_images:{$tmdbId}";
    }

    public static function tmdbMovieVideos(int $tmdbId): string
    {
        return "tmdb_movie_videos:{$tmdbId}";
    }

    public static function tmdbTVShowVideos(int $tmdbId): string
    {
        return "tmdb_tv_show_videos:{$tmdbId}";
    }

    public static function tmdbPerson(int $tmdbId, string $language = 'en-US'): string
    {
        return "tmdb_person:{$tmdbId}:{$language}";
    }

    public static function tmdbSearchMovie(string $query, string $language = 'en-US'): string
    {
        $hash = md5(strtolower(trim($query)));
        return "tmdb_search_movie:{$hash}:{$language}";
    }

    public static function tmdbSearchTVShow(string $query, string $language = 'en-US'): string
    {
        $hash = md5(strtolower(trim($query)));
        return "tmdb_search_tv_show:{$hash}:{$language}";
    }

    public static function tmdbSearchPerson(string $query, string $language = 'en-US'): string
    {
        $hash = md5(strtolower(trim($query)));
        return "tmdb_search_person:{$hash}:{$language}";
    }

    public static function taxonomyTerm(string $taxonomy, int $tmdbId): string
    {
        return "taxonomy_term:{$taxonomy}:tmdb_{$tmdbId}";
    }

    public static function importJob(int $jobId): string
    {
        return "import_job:{$jobId}";
    }

    public static function syncJob(int $jobId): string
    {
        return "sync_job:{$jobId}";
    }

    public static function settings(): string
    {
        return 'settings:all';
    }

    public static function importSettings(): string
    {
        return 'settings:import';
    }

    public static function imageSettings(): string
    {
        return 'settings:images';
    }

    public static function videoSettings(): string
    {
        return 'settings:videos';
    }
}