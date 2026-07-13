<?php
namespace TMDBImporter\Infrastructure\TMDB;

use TMDBImporter\Core\Exceptions\TMDBException;
use TMDBImporter\Core\Exceptions\TMDBRateLimitException;
use TMDBImporter\Core\Exceptions\TMDBNotFoundException;
use TMDBImporter\Infrastructure\Cache\ObjectCache;
use TMDBImporter\Infrastructure\Logging\Logger;

class TMDBClient
{
    private const BASE_URL = 'https://api.themoviedb.org/3';
    private const DEFAULT_LANGUAGE = 'en-US';
    private const DEFAULT_REGION = 'US';

    private string $apiKey;
    private string $language;
    private string $region;
    private ObjectCache $cache;
    private Logger $logger;
    private int $requestCount = 0;
    private float $windowStart = 0;
    private const RATE_LIMIT = 40;
    private const RATE_WINDOW = 10;

    public function __construct(string $apiKey, string $language = self::DEFAULT_LANGUAGE, string $region = self::DEFAULT_REGION, ObjectCache $cache, Logger $logger)
    {
        $this->apiKey = $apiKey;
        $this->language = $language;
        $this->region = $region;
        $this->cache = $cache;
        $this->logger = $logger;
    }

    public function getMovie(int $movieId): array
    {
        return $this->request("/movie/{$movieId}", ['append_to_response' => 'images,videos,keywords,credits,release_dates,external_ids']);
    }

    public function getTVShow(int $tvShowId): array
    {
        return $this->request("/tv/{$tvShowId}", ['append_to_response' => 'images,videos,keywords,credits,content_ratings,external_ids']);
    }

    public function getSeason(int $tvShowId, int $seasonNumber): array
    {
        return $this->request("/tv/{$tvShowId}/season/{$seasonNumber}");
    }

    public function getEpisodes(int $tvShowId, int $seasonNumber): array
    {
        return $this->request("/tv/{$tvShowId}/season/{$seasonNumber}");
    }

    public function getMovieImages(int $movieId): array
    {
        return $this->request("/movie/{$movieId}/images");
    }

    public function getTVShowImages(int $tvShowId): array
    {
        return $this->request("/tv/{$tvShowId}/images");
    }

    public function getPersonImages(int $personId): array
    {
        return $this->request("/person/{$personId}/images");
    }

    public function getMovieVideos(int $movieId): array
    {
        return $this->request("/movie/{$movieId}/videos");
    }

    public function getTVShowVideos(int $tvShowId): array
    {
        return $this->request("/tv/{$tvShowId}/videos");
    }

    public function getSeasonVideos(int $tvShowId, int $seasonNumber): array
    {
        return $this->request("/tv/{$tvShowId}/season/{$seasonNumber}/videos");
    }

    public function getEpisodeVideos(int $tvShowId, int $seasonNumber, int $episodeNumber): array
    {
        return $this->request("/tv/{$tvShowId}/season/{$seasonNumber}/episode/{$episodeNumber}/videos");
    }

    public function searchMovie(string $query, int $page = 1): array
    {
        return $this->request('/search/movie', ['query' => $query, 'page' => $page]);
    }

    public function searchTVShow(string $query, int $page = 1): array
    {
        return $this->request('/search/tv', ['query' => $query, 'page' => $page]);
    }

    public function searchPerson(string $query, int $page = 1): array
    {
        return $this->request('/search/person', ['query' => $query, 'page' => $page]);
    }

    public function getGenres(string $type = 'movie'): array
    {
        $endpoint = $type === 'tv' ? '/genre/tv/list' : '/genre/movie/list';
        return $this->request($endpoint);
    }

    public function getPerson(int $personId): array
    {
        return $this->request("/person/{$personId}", ['append_to_response' => 'images,external_ids']);
    }

    public function getPersonMovieCredits(int $personId): array
    {
        return $this->request("/person/{$personId}/movie_credits");
    }

    public function getPersonTVCredits(int $personId): array
    {
        return $this->request("/person/{$personId}/tv_credits");
    }

    public function getNetworks(): array
    {
        return $this->request('/networks');
    }

    public function getCompanies(): array
    {
        return $this->request('/companies');
    }

    public function getKeywords(): array
    {
        return $this->request('/keywords');
    }

    private function request(string $endpoint, array $params = []): array
    {
        $this->enforceRateLimit();

        $cacheKey = 'tmdb_api:' . md5($endpoint . '?' . http_build_query($params));
        $cached = $this->cache->get($cacheKey, 'tmdb_api');
        if ($cached !== false) {
            return $cached;
        }

        $params['api_key'] = $this->apiKey;
        $params['language'] = $this->language;
        $params['region'] = $this->region;

        $url = self::BASE_URL . $endpoint . '?' . http_build_query($params);

        $response = wp_remote_get($url, [
            'timeout' => 30,
            'headers' => [
                'Accept' => 'application/json',
            ],
        ]);

        $this->requestCount++;
        $this->windowStart = $this->windowStart ?: microtime(true);

        if (is_wp_error($response)) {
            $this->logger->error('TMDB API request failed', [
                'endpoint' => $endpoint,
                'error' => $response->get_error_message(),
            ]);
            throw new TMDBException('API request failed: ' . $response->get_error_message());
        }

        $statusCode = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);

        if ($statusCode === 429) {
            $retryAfter = (int) wp_remote_retrieve_header($response, 'Retry-After') ?? 10;
            $this->logger->warning('TMDB rate limit hit', ['retry_after' => $retryAfter]);
            throw new TMDBRateLimitException('Rate limit exceeded', $retryAfter);
        }

        if ($statusCode === 404) {
            throw new TMDBNotFoundException('Resource not found: ' . $endpoint);
        }

        if ($statusCode >= 400) {
            $this->logger->error('TMDB API error', [
                'endpoint' => $endpoint,
                'status' => $statusCode,
                'body' => $body,
            ]);
            throw new TMDBException("API error {$statusCode}: {$body}");
        }

        $data = json_decode($body, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new TMDBException('Invalid JSON response');
        }

        $this->cache->set($cacheKey, $data, HOUR_IN_SECONDS, 'tmdb_api');

        return $data;
    }

    private function enforceRateLimit(): void
    {
        $elapsed = microtime(true) - $this->windowStart;
        if ($elapsed >= self::RATE_WINDOW) {
            $this->requestCount = 0;
            $this->windowStart = microtime(true);
            return;
        }

        if ($this->requestCount >= self::RATE_LIMIT) {
            $sleepTime = self::RATE_WINDOW - $elapsed;
            if ($sleepTime > 0) {
                usleep($sleepTime * 1000000);
            }
            $this->requestCount = 0;
            $this->windowStart = microtime(true);
        }
    }

    public function setLanguage(string $language): void
    {
        $this->language = $language;
    }

    public function setRegion(string $region): void
    {
        $this->region = $region;
    }

    public function getApiKey(): string
    {
        return $this->apiKey;
    }

    public function getLanguage(): string
    {
        return $this->language;
    }

    public function getRegion(): string
    {
        return $this->region;
    }
}