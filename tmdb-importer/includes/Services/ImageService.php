<?php
namespace TMDBImporter\Services;

use TMDBImporter\Domain\Movie;
use TMDBImporter\Domain\TVShow;
use TMDBImporter\Domain\Season;
use TMDBImporter\Domain\Episode;
use TMDBImporter\Infrastructure\TMDB\TMDBClient;
use TMDBImporter\Infrastructure\Logging\Logger;
use TMDBImporter\Infrastructure\Cache\ObjectCache;

class ImageService
{
    private TMDBClient $tmdbClient;
    private ObjectCache $cache;
    private Logger $logger;
    private string $baseImageUrl = 'https://image.tmdb.org/t/p/';

    public function __construct(TMDBClient $tmdbClient, ObjectCache $cache, Logger $logger)
    {
        $this->tmdbClient = $tmdbClient;
        $this->cache = $cache;
        $this->logger = $logger;
    }

    public function processMovieImages(Movie $movie, array $imagesData): void
    {
        $poster = $this->getBestImage($imagesData['posters'] ?? [], 'w500');
        $backdrop = $this->getBestImage($imagesData['backdrops'] ?? [], 'w1280');
        $logos = $this->getImagesByType($imagesData['logos'] ?? [], 'original');

        if ($poster) {
            $movie->setPosterPath($this->buildImageUrl($poster, 'w500'));
        }
        if ($backdrop) {
            $movie->setBackdropPath($this->buildImageUrl($backdrop, 'w1280'));
        }

        update_post_meta($movie->getId(), '_tmdb_logos', $logos);
    }

    public function processTVShowImages(TVShow $tvShow, array $imagesData): void
    {
        $poster = $this->getBestImage($imagesData['posters'] ?? [], 'w500');
        $backdrop = $this->getBestImage($imagesData['backdrops'] ?? [], 'w1280');
        $logos = $this->getImagesByType($imagesData['logos'] ?? [], 'original');

        if ($poster) {
            $tvShow->setPosterPath($this->buildImageUrl($poster, 'w500'));
        }
        if ($backdrop) {
            $tvShow->setBackdropPath($this->buildImageUrl($backdrop, 'w1280'));
        }

        update_post_meta($tvShow->getId(), '_tmdb_logos', $logos);
    }

    public function processSeasonImages(Season $season, array $imagesData): void
    {
        $poster = $this->getBestImage($imagesData['posters'] ?? [], 'w500');
        if ($poster) {
            $season->setPosterPath($this->buildImageUrl($poster, 'w500'));
        }
    }

    public function processEpisodeImages(Episode $episode, ?string $stillPath): void
    {
        if ($stillPath) {
            $episode->setStillPath($this->buildImageUrl($stillPath, 'w300'));
        }
    }

    public function syncMovieImages(int $postId, array $tmdbData): void
    {
        $images = $this->getMovieImages($tmdbData);
        $movie = $this->getMovieRepository()->findById($postId);

        if (!$movie) {
            return;
        }

        $poster = $this->getBestImage($images['posters'] ?? [], 'w500');
        $backdrop = $this->getBestImage($images['backdrops'] ?? [], 'w1280');

        if ($poster) {
            $movie->setPosterPath($this->buildImageUrl($poster, 'w500'));
        }
        if ($backdrop) {
            $movie->setBackdropPath($this->buildImageUrl($backdrop, 'w1280'));
        }

        $this->getMovieRepository()->save($movie);
    }

    public function syncTVShowImages(int $postId, array $tmdbData): void
    {
        $images = $this->getTVShowImages($tmdbData);
        $tvShow = $this->getTVShowRepository()->findById($postId);

        if (!$tvShow) {
            return;
        }

        $poster = $this->getBestImage($images['posters'] ?? [], 'w500');
        $backdrop = $this->getBestImage($images['backdrops'] ?? [], 'w1280');

        if ($poster) {
            $tvShow->setPosterPath($this->buildImageUrl($poster, 'w500'));
        }
        if ($backdrop) {
            $tvShow->setBackdropPath($this->buildImageUrl($backdrop, 'w1280'));
        }

        $this->getTVShowRepository()->save($tvShow);
    }

    public function syncSeasonImages(int $seasonId, array $tmdbData): void
    {
        $season = $this->getSeasonRepository()->findById($seasonId);
        if (!$season) {
            return;
        }

        $images = $tmdbData['images'] ?? $tmdbData;
        $poster = $this->getBestImage($images['posters'] ?? [], 'w500');

        if ($poster) {
            $season->setPosterPath($this->buildImageUrl($poster, 'w500'));
            $this->getSeasonRepository()->save($season);
        }
    }

    public function downloadAndAttachImages(int $postId, string $postType, array $options = []): array
    {
        $download = $options['download'] ?? true;
        $replace = $options['replace'] ?? false;

        if (!$download) {
            return ['downloaded' => 0, 'skipped' => 0];
        }

        $imageUrls = $this->getImageUrlsForPost($postId, $postType);
        $results = ['downloaded' => 0, 'skipped' => 0, 'errors' => []];

        foreach ($imageUrls as $type => $url) {
            if (!$replace && has_post_thumbnail($postId) && $type === 'poster') {
                $results['skipped']++;
                continue;
            }

            try {
                $attachmentId = $this->downloadAndCreateAttachment($url, $postId, $type);
                if ($attachmentId) {
                    if ($type === 'poster') {
                        set_post_thumbnail($postId, $attachmentId);
                    }
                    $results['downloaded']++;
                }
            } catch (\Throwable $e) {
                $results['errors'][] = $e->getMessage();
                $this->logger->error("Failed to download image", ['post_id' => $postId, 'type' => $type, 'error' => $e->getMessage()]);
            }
        }

        return $results;
    }

    public function downloadPersonImage(int $personTermId): ?int
    {
        $person = $this->getTaxonomyRepository()->getPerson($personTermId);
        if (!$person || !$person->getProfilePath()) {
            return null;
        }

        try {
            return $this->downloadAndCreateAttachment(
                $this->buildImageUrl($person->getProfilePath(), 'w185'),
                $personTermId,
                'profile',
                'term'
            );
        } catch (\Throwable $e) {
            $this->logger->error("Failed to download person image", ['term_id' => $personTermId, 'error' => $e->getMessage()]);
            return null;
        }
    }

    private function getBestImage(array $images, string $preferredSize): ?string
    {
        if (empty($images)) {
            return null;
        }

        $sorted = array_filter($images, fn($img) => !empty($img['file_path']));
        usort($sorted, fn($a, $b) => ($a['vote_average'] ?? 0) <=> ($b['vote_average'] ?? 0));

        return end($sorted)['file_path'] ?? null;
    }

    private function getImagesByType(array $images, string $size): array
    {
        return array_map(
            fn($img) => $this->buildImageUrl($img['file_path'], $size),
            array_filter($images, fn($img) => !empty($img['file_path']))
        );
    }

    private function buildImageUrl(string $path, string $size): string
    {
        return $this->baseImageUrl . $size . $path;
    }

    private function getMovieImages(array $data): array
    {
        $cacheKey = 'movie_images:' . $data['id'];
        $cached = $this->cache->get($cacheKey, 'tmdb_images');
        if ($cached) {
            return $cached;
        }

        $images = $this->tmdbClient->getMovieImages($data['id']);
        $this->cache->set($cacheKey, $images, HOUR_IN_SECONDS, 'tmdb_images');

        return $images;
    }

    private function getTVShowImages(array $data): array
    {
        $cacheKey = 'tv_images:' . $data['id'];
        $cached = $this->cache->get($cacheKey, 'tmdb_images');
        if ($cached) {
            return $cached;
        }

        $images = $this->tmdbClient->getTVShowImages($data['id']);
        $this->cache->set($cacheKey, $images, HOUR_IN_SECONDS, 'tmdb_images');

        return $images;
    }

    private function getImageUrlsForPost(int $postId, string $postType): array
    {
        $urls = [];

        if ($postType === 'tmdb_movie') {
            $poster = get_post_meta($postId, '_tmdb_poster_path', true);
            $backdrop = get_post_meta($postId, '_tmdb_backdrop_path', true);

            if ($poster) {
                $urls['poster'] = $this->buildImageUrl($poster, 'w500');
            }
            if ($backdrop) {
                $urls['backdrop'] = $this->buildImageUrl($backdrop, 'w1280');
            }
        } elseif ($postType === 'tmdb_tv_show') {
            $poster = get_post_meta($postId, '_tmdb_poster_path', true);
            $backdrop = get_post_meta($postId, '_tmdb_backdrop_path', true);

            if ($poster) {
                $urls['poster'] = $this->buildImageUrl($poster, 'w500');
            }
            if ($backdrop) {
                $urls['backdrop'] = $this->buildImageUrl($backdrop, 'w1280');
            }
        }

        return $urls;
    }

    private function downloadAndCreateAttachment(string $url, int $objectId, string $type, string $objectType = 'post'): ?int
    {
        $response = wp_remote_get($url, ['timeout' => 30]);
        if (is_wp_error($response)) {
            throw new \RuntimeException('Failed to download image: ' . $response->get_error_message());
        }

        $statusCode = wp_remote_retrieve_response_code($response);
        if ($statusCode !== 200) {
            throw new \RuntimeException("HTTP {$statusCode}");
        }

        $fileContent = wp_remote_retrieve_body($response);
        if (!$fileContent) {
            throw new \RuntimeException('Empty response');
        }

        $fileName = $this->generateFileName($url, $objectId, $type);
        $upload = wp_upload_bits($fileName, null, $fileContent);

        if (!empty($upload['error'])) {
            throw new \RuntimeException('Upload failed: ' . $upload['error']);
        }

        $attachment = [
            'post_title' => sanitize_file_name($fileName),
            'post_content' => '',
            'post_status' => 'inherit',
            'post_mime_type' => $upload['type'],
            'guid' => $upload['url'],
            'post_parent' => $objectType === 'post' ? $objectId : 0,
        ];

        $attachmentId = wp_insert_attachment($attachment, $upload['file'], $objectType === 'post' ? $objectId : 0);

        if (is_wp_error($attachmentId)) {
            throw new \RuntimeException('Failed to create attachment: ' . $attachmentId->get_error_message());
        }

        $attachData = wp_generate_attachment_metadata($attachmentId, $upload['file']);
        wp_update_attachment_metadata($attachmentId, $attachData);

        update_post_meta($attachmentId, '_tmdb_source_url', $url);
        update_post_meta($attachmentId, '_tmdb_image_type', $type);

        if ($objectType === 'term') {
            update_term_meta($objectId, 'image_attachment_id', $attachmentId);
        }

        return $attachmentId;
    }

    private function generateFileName(string $url, int $objectId, string $type): string
    {
        $extension = pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION) ?: 'jpg';
        return "tmdb-{$type}-{$objectId}." . $extension;
    }

    private function getMovieRepository(): \TMDBImporter\Repositories\WordPress\MovieRepository
    {
        return new \TMDBImporter\Repositories\WordPress\MovieRepository();
    }

    private function getTVShowRepository(): \TMDBImporter\Repositories\WordPress\TVShowRepository
    {
        return new \TMDBImporter\Repositories\WordPress\TVShowRepository();
    }

    private function getSeasonRepository(): \TMDBImporter\Repositories\Database\SeasonRepository
    {
        return new \TMDBImporter\Repositories\Database\SeasonRepository();
    }

    private function getTaxonomyRepository(): \TMDBImporter\Repositories\WordPress\TaxonomyRepository
    {
        return new \TMDBImporter\Repositories\WordPress\TaxonomyRepository();
    }
}