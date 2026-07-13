<?php
namespace TMDBImporter\Services;

use TMDBImporter\Domain\Movie;
use TMDBImporter\Domain\TVShow;
use TMDBImporter\Domain\Season;
use TMDBImporter\Domain\Episode;
use TMDBImporter\Domain\ImportJob;
use TMDBImporter\Infrastructure\TMDB\TMDBClient;
use TMDBImporter\Infrastructure\Logging\Logger;
use TMDBImporter\Repositories\Contracts\MovieRepositoryInterface;
use TMDBImporter\Repositories\Contracts\TVShowRepositoryInterface;
use TMDBImporter\Repositories\Database\SeasonRepository;
use TMDBImporter\Repositories\Database\EpisodeRepository;
use TMDBImporter\Repositories\Database\ImportJobRepository;

class ImportService
{
    private TMDBClient $tmdbClient;
    private MetadataService $metadataService;
    private TaxonomyService $taxonomyService;
    private ImageService $imageService;
    private VideoService $videoService;
    private Logger $logger;
    private ImportJobRepository $importJobRepository;
    private MovieRepositoryInterface $movieRepository;
    private TVShowRepositoryInterface $tvShowRepository;
    private SeasonRepository $seasonRepository;
    private EpisodeRepository $episodeRepository;

    public function __construct(
        TMDBClient $tmdbClient,
        MetadataService $metadataService,
        TaxonomyService $taxonomyService,
        ImageService $imageService,
        VideoService $videoService,
        Logger $logger,
        ImportJobRepository $importJobRepository,
        MovieRepositoryInterface $movieRepository,
        TVShowRepositoryInterface $tvShowRepository,
        SeasonRepository $seasonRepository,
        EpisodeRepository $episodeRepository
    ) {
        $this->tmdbClient = $tmdbClient;
        $this->metadataService = $metadataService;
        $this->taxonomyService = $taxonomyService;
        $this->imageService = $imageService;
        $this->videoService = $videoService;
        $this->logger = $logger;
        $this->importJobRepository = $importJobRepository;
        $this->movieRepository = $movieRepository;
        $this->tvShowRepository = $tvShowRepository;
        $this->seasonRepository = $seasonRepository;
        $this->episodeRepository = $episodeRepository;
    }

    public function importMovie(int $tmdbId, array $options = []): Movie
    {
        $this->logger->info("Starting movie import", ['tmdb_id' => $tmdbId]);

        $existing = $this->movieRepository->findByTmdbId($tmdbId);
        if ($existing && ($options['skip_existing'] ?? false)) {
            $this->logger->info("Movie already exists, skipping", ['tmdb_id' => $tmdbId]);
            return $existing;
        }

        $data = $this->tmdbClient->getMovie($tmdbId);
        $movie = $this->metadataService->mapMovieData($data);

        $this->taxonomyService->syncMovieTaxonomies($movie, $data);
        $this->imageService->processMovieImages($movie, $data['images'] ?? []);
        $this->videoService->processMovieVideos($movie, $data['videos'] ?? []);

        if ($existing && ($options['overwrite'] ?? false)) {
            $movie->setId($existing->getId());
        }

        $this->movieRepository->save($movie);

        do_action('tmdb_importer_movie_imported', $movie, $data);

        $this->logger->info("Movie imported successfully", ['post_id' => $movie->getId(), 'tmdb_id' => $tmdbId]);

        return $movie;
    }

    public function importTVShow(int $tmdbId, array $options = []): TVShow
    {
        $this->logger->info("Starting TV show import", ['tmdb_id' => $tmdbId]);

        $existing = $this->tvShowRepository->findByTmdbId($tmdbId);
        if ($existing && ($options['skip_existing'] ?? false)) {
            $this->logger->info("TV show already exists, skipping", ['tmdb_id' => $tmdbId]);
            return $existing;
        }

        $data = $this->tmdbClient->getTVShow($tmdbId);
        $tvShow = $this->metadataService->mapTVShowData($data);

        $this->taxonomyService->syncTVShowTaxonomies($tvShow, $data);
        $this->imageService->processTVShowImages($tvShow, $data['images'] ?? []);
        $this->videoService->processTVShowVideos($tvShow, $data['videos'] ?? []);

        if ($existing && ($options['overwrite'] ?? false)) {
            $tvShow->setId($existing->getId());
        }

        $this->tvShowRepository->save($tvShow);

        if ($options['import_seasons'] ?? true) {
            $this->importSeasons($tvShow, $data);
        }

        do_action('tmdb_importer_tv_show_imported', $tvShow, $data);

        $this->logger->info("TV show imported successfully", ['post_id' => $tvShow->getId(), 'tmdb_id' => $tmdbId]);

        return $tvShow;
    }

    public function importSeason(int $showId, int $seasonNumber, array $options = []): Season
    {
        $tvShow = $this->tvShowRepository->findById($showId);
        if (!$tvShow) {
            throw new \InvalidArgumentException("TV show not found: {$showId}");
        }

        $tmdbShowId = $tvShow->getTmdbId();
        $this->logger->info("Starting season import", ['show_id' => $showId, 'season_number' => $seasonNumber]);

        $data = $this->tmdbClient->getSeason($tmdbShowId, $seasonNumber);
        $season = $this->metadataService->mapSeasonData($data, $showId);

        $this->imageService->processSeasonImages($season, $data['images'] ?? []);

        $this->seasonRepository->save($season);

        if ($options['import_episodes'] ?? true) {
            $this->importEpisodes($season->getId(), $data['episodes'] ?? []);
        }

        $this->logger->info("Season imported successfully", ['season_id' => $season->getId()]);

        return $season;
    }

    public function importEpisodes(int $seasonId, array $episodesData = []): array
    {
        $season = $this->seasonRepository->findById($seasonId);
        if (!$season) {
            throw new \InvalidArgumentException("Season not found: {$seasonId}");
        }

        if (empty($episodesData)) {
            $data = $this->tmdbClient->getEpisodes($season->getShowId(), $season->getSeasonNumber());
            $episodesData = $data['episodes'] ?? [];
        }

        $episodes = [];
        foreach ($episodesData as $epData) {
            $episode = $this->metadataService->mapEpisodeData($epData, $seasonId);
            $this->imageService->processEpisodeImages($episode, $epData['still_path'] ?? null);
            $this->episodeRepository->save($episode);
            $episodes[] = $episode;
        }

        $this->logger->info("Episodes imported", ['season_id' => $seasonId, 'count' => count($episodes)]);

        return $episodes;
    }

    public function importBulk(array $tmdbIds, string $type, callable $progressCallback = null): array
    {
        $results = ['success' => [], 'failed' => []];

        $job = new ImportJob();
        $job->setType($type)
            ->setTmdbIds($tmdbIds)
            ->setImportOptions(['skip_existing' => true])
            ->setStatus(ImportJob::STATUS_RUNNING)
            ->setStartedAt(current_time('mysql', true));
        $this->importJobRepository->save($job);

        foreach ($tmdbIds as $index => $tmdbId) {
            try {
                if ($progressCallback) {
                    $progressCallback($index + 1, count($tmdbIds));
                }

                $job->addProcessedId($tmdbId);
                $this->importJobRepository->save($job);

                if ($type === 'movie') {
                    $result = $this->importMovie($tmdbId, ['skip_existing' => true]);
                    $results['success'][] = $result->getId();
                } elseif ($type === 'tv_show') {
                    $result = $this->importTVShow($tmdbId, ['skip_existing' => true, 'import_seasons' => true]);
                    $results['success'][] = $result->getId();
                }
            } catch (\Throwable $e) {
                $job->addFailedId($tmdbId);
                $job->addError($e->getMessage());
                $results['failed'][] = ['tmdb_id' => $tmdbId, 'error' => $e->getMessage()];
                $this->logger->error("Bulk import failed for {$type} {$tmdbId}", ['error' => $e->getMessage()]);
            }

            $this->importJobRepository->save($job);
        }

        $job->setStatus(ImportJob::STATUS_COMPLETED)
            ->setCompletedAt(current_time('mysql', true));
        $this->importJobRepository->save($job);

        return $results;
    }

    private function importSeasons(TVShow $tvShow, array $data): void
    {
        $seasons = $data['seasons'] ?? [];
        foreach ($seasons as $seasonData) {
            if (($seasonData['season_number'] ?? 0) === 0) {
                continue;
            }

            try {
                $this->importSeason($tvShow->getId(), $seasonData['season_number'], ['import_episodes' => true]);
            } catch (\Throwable $e) {
                $this->logger->error("Failed to import season", [
                    'show_id' => $tvShow->getId(),
                    'season_number' => $seasonData['season_number'],
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}