<?php
namespace TMDBImporter\Services;

use TMDBImporter\Domain\Movie;
use TMDBImporter\Domain\TVShow;
use TMDBImporter\Domain\Season;
use TMDBImporter\Domain\Episode;
use TMDBImporter\Infrastructure\TMDB\TMDBClient;
use TMDBImporter\Infrastructure\Logging\Logger;

class SyncService
{
    private TMDBClient $tmdbClient;
    private MetadataService $metadataService;
    private TaxonomyService $taxonomyService;
    private ImageService $imageService;
    private VideoService $videoService;
    private Logger $logger;

    public function __construct(
        TMDBClient $tmdbClient,
        MetadataService $metadataService,
        TaxonomyService $taxonomyService,
        ImageService $imageService,
        VideoService $videoService,
        Logger $logger
    ) {
        $this->tmdbClient = $tmdbClient;
        $this->metadataService = $metadataService;
        $this->taxonomyService = $taxonomyService;
        $this->imageService = $imageService;
        $this->videoService = $videoService;
        $this->logger = $logger;
    }

    public function syncMovie(int $postId, array $options = []): array
    {
        $movie = $this->getMovieRepository()->findById($postId);
        if (!$movie) {
            throw new \InvalidArgumentException("Movie not found: {$postId}");
        }

        $tmdbData = $this->tmdbClient->getMovie($movie->getTmdbId());

        $results = [
            'metadata' => false,
            'images' => false,
            'videos' => false,
            'taxonomies' => false,
            'ratings' => false,
        ];

        if ($options['metadata'] ?? true) {
            $this->metadataService->updateMovieMetadata($postId, $tmdbData);
            $results['metadata'] = true;
        }

        if ($options['ratings'] ?? true) {
            $this->updateRatings($movie, $tmdbData);
            $results['ratings'] = true;
        }

        if ($options['images'] ?? false) {
            $this->imageService->syncMovieImages($postId, $tmdbData);
            $results['images'] = true;
        }

        if ($options['videos'] ?? false) {
            $this->videoService->syncMovieVideos($postId, $tmdbData);
            $results['videos'] = true;
        }

        if ($options['taxonomies'] ?? true) {
            $this->taxonomyService->syncMovieTaxonomies($postId, $tmdbData);
            $results['taxonomies'] = true;
        }

        do_action('tmdb_importer_movie_synced', $postId, $results);

        return $results;
    }

    public function syncTVShow(int $postId, array $options = []): array
    {
        $tvShow = $this->getTVShowRepository()->findById($postId);
        if (!$tvShow) {
            throw new \InvalidArgumentException("TV Show not found: {$postId}");
        }

        $tmdbData = $this->tmdbClient->getTVShow($tvShow->getTmdbId());

        $results = [
            'metadata' => false,
            'images' => false,
            'videos' => false,
            'taxonomies' => false,
            'ratings' => false,
            'seasons' => false,
        ];

        if ($options['metadata'] ?? true) {
            $this->metadataService->updateTVShowMetadata($postId, $tmdbData);
            $results['metadata'] = true;
        }

        if ($options['ratings'] ?? true) {
            $this->updateRatings($tvShow, $tmdbData);
            $results['ratings'] = true;
        }

        if ($options['images'] ?? false) {
            $this->imageService->syncTVShowImages($postId, $tmdbData);
            $results['images'] = true;
        }

        if ($options['videos'] ?? false) {
            $this->videoService->syncTVShowVideos($postId, $tmdbData);
            $results['videos'] = true;
        }

        if ($options['taxonomies'] ?? true) {
            $this->taxonomyService->syncTVShowTaxonomies($postId, $tmdbData);
            $results['taxonomies'] = true;
        }

        if ($options['seasons'] ?? true) {
            $this->syncSeasons($tvShow, $tmdbData, $options);
            $results['seasons'] = true;
        }

        do_action('tmdb_importer_tv_show_synced', $postId, $results);

        return $results;
    }

    public function syncSeason(int $seasonId, array $options = []): array
    {
        $season = $this->getSeasonRepository()->findById($seasonId);
        if (!$season) {
            throw new \InvalidArgumentException("Season not found: {$seasonId}");
        }

        $tmdbData = $this->tmdbClient->getSeason($season->getShowId(), $season->getSeasonNumber());

        $results = [
            'metadata' => false,
            'images' => false,
            'episodes' => false,
        ];

        if ($options['metadata'] ?? true) {
            $this->metadataService->updateSeasonMetadata($seasonId, $tmdbData);
            $results['metadata'] = true;
        }

        if ($options['images'] ?? false) {
            $this->imageService->syncSeasonImages($seasonId, $tmdbData);
            $results['images'] = true;
        }

        if ($options['episodes'] ?? true) {
            $this->syncEpisodes($season, $tmdbData, $options);
            $results['episodes'] = true;
        }

        return $results;
    }

    public function syncEpisodes(int $seasonId, array $options = []): array
    {
        $season = $this->getSeasonRepository()->findById($seasonId);
        if (!$season) {
            throw new \InvalidArgumentException("Season not found: {$seasonId}");
        }

        $tmdbData = $this->tmdbClient->getEpisodes($season->getShowId(), $season->getSeasonNumber());
        return $this->syncEpisodes($season, $tmdbData, $options);
    }

    public function syncRatings(array $postIds): array
    {
        $results = ['updated' => 0, 'failed' => 0];

        foreach ($postIds as $postId) {
            try {
                $post = get_post($postId);
                if (!$post) {
                    continue;
                }

                $tmdbId = (int) get_post_meta($postId, '_tmdb_id', true);
                if (!$tmdbId) {
                    continue;
                }

                if ($post->post_type === 'tmdb_movie') {
                    $data = $this->tmdbClient->getMovie($tmdbId);
                    $movie = $this->getMovieRepository()->findById($postId);
                    if ($movie) {
                        $this->updateRatings($movie, $data);
                        $results['updated']++;
                    }
                } elseif ($post->post_type === 'tmdb_tv_show') {
                    $data = $this->tmdbClient->getTVShow($tmdbId);
                    $tvShow = $this->getTVShowRepository()->findById($postId);
                    if ($tvShow) {
                        $this->updateRatings($tvShow, $data);
                        $results['updated']++;
                    }
                }
            } catch (\Throwable $e) {
                $this->logger->error('Failed to sync ratings', ['post_id' => $postId, 'error' => $e->getMessage()]);
                $results['failed']++;
            }
        }

        return $results;
    }

    public function scheduleSync(string $type, int $interval = HOUR_IN_SECONDS): void
    {
        if (!wp_next_scheduled("tmdb_importer_sync_{$type}")) {
            wp_schedule_event(time(), $interval === HOUR_IN_SECONDS ? 'hourly' : 'twicedaily', "tmdb_importer_sync_{$type}");
        }
    }

    private function updateRatings(Movie|TVShow $object, array $data): void
    {
        $rating = $data['vote_average'] ?? 0;
        $voteCount = $data['vote_count'] ?? 0;

        if ($object instanceof Movie) {
            $object->setRating($rating);
            $object->setVoteCount($voteCount);
            $this->getMovieRepository()->save($object);
        } else {
            $object->setRating($rating);
            $object->setVoteCount($voteCount);
            $this->getTVShowRepository()->save($object);
        }
    }

    private function syncSeasons(TVShow $tvShow, array $tmdbData, array $options): void
    {
        $seasons = $tmdbData['seasons'] ?? [];

        foreach ($seasons as $seasonData) {
            $seasonNumber = $seasonData['season_number'] ?? 0;
            if ($seasonNumber === 0) {
                continue;
            }

            $existing = $this->getSeasonRepository()->findByShowAndNumber($tvShow->getId(), $seasonNumber);

            if ($existing) {
                $this->metadataService->updateSeasonMetadata($existing->getId(), $seasonData);
            } else {
                $season = new Season();
                $season->setTmdbId($seasonData['id'] ?? 0)
                    ->setShowId($tvShow->getId())
                    ->setSeasonNumber($seasonNumber)
                    ->setName($seasonData['name'] ?? '')
                    ->setOverview($seasonData['overview'] ?? '')
                    ->setPosterPath($seasonData['poster_path'] ?? null)
                    ->setAirDate($seasonData['air_date'] ?? null)
                    ->setEpisodeCount($seasonData['episode_count'] ?? 0);

                $this->getSeasonRepository()->save($season);
            }

            if ($options['episodes'] ?? true) {
                $this->syncEpisodes($existing ?? $season, $this->tmdbClient->getEpisodes($tvShow->getTmdbId(), $seasonNumber), $options);
            }
        }
    }

    private function syncEpisodes(Season $season, array $tmdbData, array $options): array
    {
        $episodes = $tmdbData['episodes'] ?? [];
        $results = ['created' => 0, 'updated' => 0];

        foreach ($episodes as $episodeData) {
            $episodeNumber = $episodeData['episode_number'] ?? 0;
            if ($episodeNumber === 0) {
                continue;
            }

            $existing = $this->getEpisodeRepository()->findBySeasonAndNumber($season->getId(), $episodeNumber);

            if ($existing) {
                $existing->setName($episodeData['name'] ?? '')
                    ->setOverview($episodeData['overview'] ?? '')
                    ->setRuntime($episodeData['runtime'] ?? null)
                    ->setAirDate($episodeData['air_date'] ?? null)
                    ->setStillPath($episodeData['still_path'] ?? null)
                    ->setRating((float) ($episodeData['vote_average'] ?? 0))
                    ->setVoteCount((int) ($episodeData['vote_count'] ?? 0));

                $this->getEpisodeRepository()->save($existing);
                $results['updated']++;
            } else {
                $episode = new Episode();
                $episode->setTmdbId($episodeData['id'] ?? 0)
                    ->setSeasonId($season->getId())
                    ->setEpisodeNumber($episodeNumber)
                    ->setName($episodeData['name'] ?? '')
                    ->setOverview($episodeData['overview'] ?? '')
                    ->setRuntime($episodeData['runtime'] ?? null)
                    ->setAirDate($episodeData['air_date'] ?? null)
                    ->setStillPath($episodeData['still_path'] ?? null)
                    ->setRating((float) ($episodeData['vote_average'] ?? 0))
                    ->setVoteCount((int) ($episodeData['vote_count'] ?? 0));

                $this->getEpisodeRepository()->save($episode);
                $results['created']++;
            }
        }

        return $results;
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

    private function getEpisodeRepository(): \TMDBImporter\Repositories\Database\EpisodeRepository
    {
        return new \TMDBImporter\Repositories\Database\EpisodeRepository();
    }
}