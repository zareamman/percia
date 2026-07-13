<?php
namespace TMDBImporter\Services;

use TMDBImporter\Domain\Movie;
use TMDBImporter\Domain\TVShow;
use TMDBImporter\Domain\Season;
use TMDBImporter\Domain\Person;
use TMDBImporter\Infrastructure\TMDB\TMDBClient;
use TMDBImporter\Infrastructure\Cache\ObjectCache;
use TMDBImporter\Infrastructure\Logging\Logger;
use TMDBImporter\Repositories\WordPress\TaxonomyRepository;

class TaxonomyService
{
    private TMDBClient $tmdbClient;
    private ObjectCache $cache;
    private Logger $logger;
    private TaxonomyRepository $taxonomyRepository;

    public function __construct(TMDBClient $tmdbClient, ObjectCache $cache, Logger $logger)
    {
        $this->tmdbClient = $tmdbClient;
        $this->cache = $cache;
        $this->logger = $logger;
        $this->taxonomyRepository = new TaxonomyRepository();
    }

    public function syncMovieTaxonomies(int|Movie $object, array $data): void
    {
        $postId = $object instanceof Movie ? $object->getId() : $object;
        $genres = $data['genres'] ?? [];
        $keywords = $data['keywords']['keywords'] ?? $data['keywords'] ?? [];
        $credits = $data['credits'] ?? [];
        $companies = $data['production_companies'] ?? [];
        $releaseDate = $data['release_date'] ?? '';

        $this->syncGenres($postId, $genres);
        $this->syncKeywords($postId, $keywords);
        $this->syncPeople($postId, $credits);
        $this->syncCompanies($postId, $companies);
        $this->syncReleaseYear($postId, $releaseDate);
    }

    public function syncTVShowTaxonomies(int|TVShow $object, array $data): void
    {
        $postId = $object instanceof TVShow ? $object->getId() : $object;
        $genres = $data['genres'] ?? [];
        $keywords = $data['keywords']['results'] ?? $data['keywords'] ?? [];
        $credits = $data['credits'] ?? [];
        $networks = $data['networks'] ?? [];
        $companies = $data['production_companies'] ?? [];
        $firstAirDate = $data['first_air_date'] ?? '';

        $this->syncGenres($postId, $genres);
        $this->syncKeywords($postId, $keywords);
        $this->syncPeople($postId, $credits);
        $this->syncNetworks($postId, $networks);
        $this->syncCompanies($postId, $companies);
        $this->syncReleaseYear($postId, $firstAirDate);
    }

    public function syncSeasonTaxonomies(int|Season $object, array $data): void
    {
        $postId = $object instanceof Season ? $object->getId() : $object;
    }

    private function syncGenres(int $postId, array $genres): void
    {
        $termIds = [];
        foreach ($genres as $genre) {
            $termId = $this->taxonomyRepository->getOrCreateTerm(
                'tmdb_genre',
                $genre['name'],
                $genre['id']
            );
            $termIds[] = $termId;
        }
        $this->taxonomyRepository->attachToObject($postId, 'tmdb_genre', $termIds);
    }

    private function syncKeywords(int $postId, array $keywords): void
    {
        $termIds = [];
        foreach ($keywords as $keyword) {
            $termId = $this->taxonomyRepository->getOrCreateTerm(
                'tmdb_keyword',
                $keyword['name'],
                $keyword['id']
            );
            $termIds[] = $termId;
        }
        $this->taxonomyRepository->attachToObject($postId, 'tmdb_keyword', $termIds);
    }

    private function syncPeople(int $postId, array $credits): void
    {
        $cast = $credits['cast'] ?? [];
        $crew = $credits['crew'] ?? [];

        $allPeople = array_merge($cast, $crew);

        foreach ($allPeople as $personData) {
            $personTermId = $this->getOrCreatePerson($personData);

            $this->savePersonRelationship($personTermId, $postId, $personData);
        }
    }

    private function getOrCreatePerson(array $personData): int
    {
        $tmdbId = $personData['id'] ?? 0;
        if (!$tmdbId) {
            return 0;
        }

        $existing = $this->taxonomyRepository->findPersonByTmdbId($tmdbId);
        if ($existing) {
            $person = $existing;
        } else {
            $person = new Person();
            $person->setTmdbId($tmdbId);
            $person->setName($personData['name'] ?? '');
            $person->setProfilePath($personData['profile_path'] ?? null);
            $person->setKnownForDepartment($personData['known_for_department'] ?? null);
            $person->setPopularity($personData['popularity'] ?? null);
        }

        $this->taxonomyRepository->savePerson($person);
        return $person->getTermId();
    }

    private function savePersonRelationship(int $personTermId, int $postId, array $personData): void
    {
        global $wpdb;
        $table = $wpdb->prefix . 'tmdb_person_relationships';

        $data = [
            'person_term_id' => $personTermId,
            'object_type' => get_post_type($postId),
            'object_id' => $postId,
            'role' => $personData['character'] ?? '',
            'character_name' => $personData['character'] ?? '',
            'department' => $personData['known_for_department'] ?? '',
            'credit_order' => $personData['order'] ?? 0,
        ];

        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT id FROM {$table} WHERE person_term_id = %d AND object_type = %s AND object_id = %d",
            $personTermId,
            $data['object_type'],
            $postId
        ));

        if ($existing) {
            $wpdb->update($table, $data, ['id' => $existing->id]);
        } else {
            $wpdb->insert($table, $data);
        }
    }

    private function syncNetworks(int $postId, array $networks): void
    {
        $termIds = [];
        foreach ($networks as $network) {
            $termId = $this->taxonomyRepository->getOrCreateTerm(
                'tmdb_network',
                $network['name'],
                $network['id'],
                ['logo_path' => $network['logo_path'] ?? null]
            );
            $termIds[] = $termId;
        }
        $this->taxonomyRepository->attachToObject($postId, 'tmdb_network', $termIds);
    }

    private function syncCompanies(int $postId, array $companies): void
    {
        $termIds = [];
        foreach ($companies as $company) {
            $termId = $this->taxonomyRepository->getOrCreateTerm(
                'tmdb_production_company',
                $company['name'],
                $company['id'],
                ['logo_path' => $company['logo_path'] ?? null]
            );
            $termIds[] = $termId;
        }
        $this->taxonomyRepository->attachToObject($postId, 'tmdb_production_company', $termIds);
    }

    private function syncReleaseYear(int $postId, string $date): void
    {
        if (!$date) {
            return;
        }

        $year = date('Y', strtotime($date));
        $termId = $this->taxonomyRepository->getOrCreateTerm(
            'tmdb_release_year',
            $year,
            (int) $year
        );

        $this->taxonomyRepository->attachToObject($postId, 'tmdb_release_year', [$termId]);
    }

    public function getPersonRelationships(int $postId, string $objectType): array
    {
        global $wpdb;
        $table = $wpdb->prefix . 'tmdb_person_relationships';

        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$table} WHERE object_type = %s AND object_id = %d ORDER BY credit_order ASC",
            $objectType,
            $postId
        ), ARRAY_A) ?? [];
    }

    public function getGenresForMovie(int $movieId): array
    {
        return $this->taxonomyRepository->getObjectTerms($movieId, 'tmdb_genre');
    }

    public function getKeywordsForMovie(int $movieId): array
    {
        return $this->taxonomyRepository->getObjectTerms($movieId, 'tmdb_keyword');
    }

    public function getNetworksForTVShow(int $tvShowId): array
    {
        return $this->taxonomyRepository->getObjectTerms($tvShowId, 'tmdb_network');
    }

    public function getCompaniesForMovie(int $movieId): array
    {
        return $this->taxonomyRepository->getObjectTerms($movieId, 'tmdb_production_company');
    }
}