<?php
namespace TMDBImporter\Services;

use TMDBImporter\Domain\Movie;
use TMDBImporter\Domain\TVShow;
use TMDBImporter\Domain\Season;
use TMDBImporter\Domain\Episode;
use TMDBImporter\Infrastructure\TMDB\TMDBClient;
use TMDBImporter\Infrastructure\Cache\ObjectCache;
use TMDBImporter\Infrastructure\Logging\Logger;

class MetadataService
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

    public function updateMovieMetadata(int $postId, array $data): void
    {
        $movie = $this->getMovieRepository()->findById($postId);
        if (!$movie) {
            throw new \InvalidArgumentException("Movie not found: {$postId}");
        }

        $movie->setTitle($data['title'] ?? $movie->getTitle())
            ->setOriginalTitle($data['original_title'] ?? $movie->getOriginalTitle())
            ->setOverview($data['overview'] ?? $movie->getOverview())
            ->setReleaseDate($data['release_date'] ?? $movie->getReleaseDate())
            ->setRuntime($data['runtime'] ?? $movie->getRuntime())
            ->setRating((float) ($data['vote_average'] ?? $movie->getRating()))
            ->setVoteCount((int) ($data['vote_count'] ?? $movie->getVoteCount()))
            ->setPosterPath($data['poster_path'] ?? $movie->getPosterPath())
            ->setBackdropPath($data['backdrop_path'] ?? $movie->getBackdropPath())
            ->setOriginalLanguage($data['original_language'] ?? $movie->getOriginalLanguage())
            ->setPopularity((float) ($data['popularity'] ?? $movie->getPopularity()))
            ->setStatus($data['status'] ?? $movie->getStatus());

        if (!empty($data['genres'])) {
            $movie->setGenreIds(array_column($data['genres'], 'id'));
        }

        if (!empty($data['production_companies'])) {
            $movie->setCompanyIds(array_column($data['production_companies'], 'id'));
        }

        $this->getMovieRepository()->save($movie);
    }

    public function updateTVShowMetadata(int $postId, array $data): void
    {
        $tvShow = $this->getTVShowRepository()->findById($postId);
        if (!$tvShow) {
            throw new \InvalidArgumentException("TV Show not found: {$postId}");
        }

        $tvShow->setName($data['name'] ?? $tvShow->getName())
            ->setOriginalName($data['original_name'] ?? $tvShow->getOriginalName())
            ->setOverview($data['overview'] ?? $tvShow->getOverview())
            ->setFirstAirDate($data['first_air_date'] ?? $tvShow->getFirstAirDate())
            ->setLastAirDate($data['last_air_date'] ?? $tvShow->getLastAirDate())
            ->setNumberOfSeasons((int) ($data['number_of_seasons'] ?? $tvShow->getNumberOfSeasons()))
            ->setNumberOfEpisodes((int) ($data['number_of_episodes'] ?? $tvShow->getNumberOfEpisodes()))
            ->setRating((float) ($data['vote_average'] ?? $tvShow->getRating()))
            ->setVoteCount((int) ($data['vote_count'] ?? $tvShow->getVoteCount()))
            ->setPosterPath($data['poster_path'] ?? $tvShow->getPosterPath())
            ->setBackdropPath($data['backdrop_path'] ?? $tvShow->getBackdropPath())
            ->setOriginalLanguage($data['original_language'] ?? $tvShow->getOriginalLanguage())
            ->setPopularity((float) ($data['popularity'] ?? $tvShow->getPopularity()))
            ->setStatus($data['status'] ?? $tvShow->getStatus())
            ->setType($data['type'] ?? $tvShow->getType());

        if (!empty($data['genres'])) {
            $tvShow->setGenreIds(array_column($data['genres'], 'id'));
        }

        if (!empty($data['networks'])) {
            $tvShow->setNetworkIds(array_column($data['networks'], 'id'));
        }

        if (!empty($data['production_companies'])) {
            $tvShow->setCompanyIds(array_column($data['production_companies'], 'id'));
        }

        $this->getTVShowRepository()->save($tvShow);
    }

    public function updateSeasonMetadata(int $seasonId, array $data): void
    {
        $season = $this->getSeasonRepository()->findById($seasonId);
        if (!$season) {
            throw new \InvalidArgumentException("Season not found: {$seasonId}");
        }

        $season->setName($data['name'] ?? $season->getName())
            ->setOverview($data['overview'] ?? $season->getOverview())
            ->setPosterPath($data['poster_path'] ?? $season->getPosterPath())
            ->setAirDate($data['air_date'] ?? $season->getAirDate())
            ->setEpisodeCount((int) ($data['episode_count'] ?? $season->getEpisodeCount()));

        $this->getSeasonRepository()->save($season);
    }

    public function updateEpisodeMetadata(int $episodeId, array $data): void
    {
        $episode = $this->getEpisodeRepository()->findById($episodeId);
        if (!$episode) {
            throw new \InvalidArgumentException("Episode not found: {$episodeId}");
        }

        $episode->setName($data['name'] ?? $episode->getName())
            ->setOverview($data['overview'] ?? $episode->getOverview())
            ->setRuntime($data['runtime'] ?? $episode->getRuntime())
            ->setAirDate($data['air_date'] ?? $episode->getAirDate())
            ->setStillPath($data['still_path'] ?? $episode->getStillPath())
            ->setRating((float) ($data['vote_average'] ?? $episode->getRating()))
            ->setVoteCount((int) ($data['vote_count'] ?? $episode->getVoteCount()));

        $this->getEpisodeRepository()->save($episode);
    }

    public function mapMovieData(array $data): Movie
    {
        $movie = new Movie();
        $movie->setTmdbId($data['id'] ?? 0)
            ->setTitle($data['title'] ?? '')
            ->setOriginalTitle($data['original_title'] ?? '')
            ->setOverview($data['overview'] ?? '')
            ->setReleaseDate($data['release_date'] ?? null)
            ->setRuntime($data['runtime'] ?? null)
            ->setRating((float) ($data['vote_average'] ?? 0))
            ->setVoteCount((int) ($data['vote_count'] ?? 0))
            ->setPosterPath($data['poster_path'] ?? null)
            ->setBackdropPath($data['backdrop_path'] ?? null)
            ->setOriginalLanguage($data['original_language'] ?? null)
            ->setPopularity((float) ($data['popularity'] ?? 0))
            ->setStatus('publish');

        if (!empty($data['genres'])) {
            $movie->setGenreIds(array_column($data['genres'], 'id'));
        }

        if (!empty($data['production_companies'])) {
            $movie->setCompanyIds(array_column($data['production_companies'], 'id'));
        }

        return $movie;
    }

    public function mapTVShowData(array $data): TVShow
    {
        $tvShow = new TVShow();
        $tvShow->setTmdbId($data['id'] ?? 0)
            ->setName($data['name'] ?? '')
            ->setOriginalName($data['original_name'] ?? '')
            ->setOverview($data['overview'] ?? '')
            ->setFirstAirDate($data['first_air_date'] ?? null)
            ->setLastAirDate($data['last_air_date'] ?? null)
            ->setNumberOfSeasons((int) ($data['number_of_seasons'] ?? 0))
            ->setNumberOfEpisodes((int) ($data['number_of_episodes'] ?? 0))
            ->setRating((float) ($data['vote_average'] ?? 0))
            ->setVoteCount((int) ($data['vote_count'] ?? 0))
            ->setPosterPath($data['poster_path'] ?? null)
            ->setBackdropPath($data['backdrop_path'] ?? null)
            ->setOriginalLanguage($data['original_language'] ?? null)
            ->setPopularity((float) ($data['popularity'] ?? 0))
            ->setStatus('publish')
            ->setType($data['type'] ?? '');

        if (!empty($data['genres'])) {
            $tvShow->setGenreIds(array_column($data['genres'], 'id'));
        }

        if (!empty($data['networks'])) {
            $tvShow->setNetworkIds(array_column($data['networks'], 'id'));
        }

        if (!empty($data['production_companies'])) {
            $tvShow->setCompanyIds(array_column($data['production_companies'], 'id'));
        }

        if (!empty($data['seasons'])) {
            $tvShow->setSeasonNumbers(array_column($data['seasons'], 'season_number'));
        }

        return $tvShow;
    }

    public function mapSeasonData(array $data, int $showId): Season
    {
        $season = new Season();
        $season->setTmdbId($data['id'] ?? 0)
            ->setShowId($showId)
            ->setSeasonNumber($data['season_number'] ?? 0)
            ->setName($data['name'] ?? '')
            ->setOverview($data['overview'] ?? '')
            ->setPosterPath($data['poster_path'] ?? null)
            ->setAirDate($data['air_date'] ?? null)
            ->setEpisodeCount((int) ($data['episode_count'] ?? 0));

        return $season;
    }

    public function mapEpisodeData(array $data, int $seasonId): Episode
    {
        $episode = new Episode();
        $episode->setTmdbId($data['id'] ?? 0)
            ->setSeasonId($seasonId)
            ->setEpisodeNumber($data['episode_number'] ?? 0)
            ->setName($data['name'] ?? '')
            ->setOverview($data['overview'] ?? '')
            ->setRuntime($data['runtime'] ?? null)
            ->setAirDate($data['air_date'] ?? null)
            ->setStillPath($data['still_path'] ?? null)
            ->setRating((float) ($data['vote_average'] ?? 0))
            ->setVoteCount((int) ($data['vote_count'] ?? 0));

        return $episode;
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