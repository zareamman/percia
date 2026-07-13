<?php
namespace TMDBImporter\Services;

use TMDBImporter\Domain\Movie;
use TMDBImporter\Domain\TVShow;
use TMDBImporter\Infrastructure\TMDB\TMDBClient;
use TMDBImporter\Infrastructure\Cache\ObjectCache;
use TMDBImporter\Infrastructure\Logging\Logger;

class VideoService
{
    private TMDBClient $tmdbClient;
    private ObjectCache $cache;
    private Logger $logger;

    public function __construct(TMDBClient $tmdbClient, ObjectCache $cache, Logger $logger)
    {
        $this->tmdbClient = $tmdbClient;
        $this->cache = $cache;
        $this->logger = $logger;
    }

    public function processMovieVideos(Movie $movie, array $videosData): void
    {
        $trailers = $this->filterTrailers($videosData['results'] ?? []);
        if (!empty($trailers)) {
            update_post_meta($movie->getId(), '_tmdb_trailer', $trailers[0]['key']);
            update_post_meta($movie->getId(), '_tmdb_videos', $trailers);
        }
    }

public function processTVShowVideos(TVShow $tvShow, array $videosData): void
    {
        $trailers = $this->filterTrailers($videosData);
        $videoIds = array_map(fn($v) => $v['key'], $trailers);

        if (!empty($videoIds)) {
            update_post_meta($tvShow->getId(), '_tmdb_video_ids', $videoIds);
            $this->logger->info("TV show videos stored", ['post_id' => $tvShow->getId(), 'count' => count($videoIds)]);
        }
    }

    public function processSeasonVideos(Season $season, array $videosData): void
    {
        $videos = $tmdbData['videos']['results'] ?? [];
        $trailers = $this->filterTrailers($videos);

        if (!empty($trailers)) {
            update_post_meta($postId, '_tmdb_trailer', $trailers[0]['key']);
            update_post_meta($postId, '_tmdb_videos', $trailers);
        }
    }

    public function syncTVShowVideos(int $postId, array $tmdbData): void
    {
        $videos = $tmdbData['videos']['results'] ?? [];
        $trailers = $this->filterTrailers($videos);

        if (!empty($trailers)) {
            update_post_meta($postId, '_tmdb_trailer', $trailers[0]['key']);
            update_post_meta($postId, '_tmdb_videos', $trailers);
        }
    }

    public function getTrailerKey(int $postId): ?string
    {
        return get_post_meta($postId, '_tmdb_trailer', true) ?: null;
    }

    public function getAllVideos(int $postId): array
    {
        return get_post_meta($postId, '_tmdb_videos', true) ?: [];
    }

    public function getYouTubeUrl(string $key): string
    {
        return "https://www.youtube.com/watch?v={$key}";
    }

    public function getEmbedUrl(string $key): string
    {
        return "https://www.youtube.com/embed/{$key}";
    }

    public function getThumbnailUrl(string $key, string $quality = 'mqdefault'): string
    {
        return "https://img.youtube.com/vi/{$key}/{$quality}.jpg";
    }

    private function filterTrailers(array $videos): array
    {
        return array_filter($videos, function ($video) {
            return ($video['site'] ?? '') === 'YouTube'
                && in_array($video['type'] ?? '', ['Trailer', 'Teaser'], true)
                && !empty($video['key']);
        });
    }
}