<?php
namespace TMDBImporter\Repositories\WordPress;

use TMDBImporter\Domain\TVShow;
use TMDBImporter\Repositories\Contracts\TVShowRepositoryInterface;

class TVShowRepository implements TVShowRepositoryInterface
{
    public function findById(int $id): ?TVShow
    {
        $post = get_post($id);
        if (!$post || $post->post_type !== 'tmdb_tv_show') {
            return null;
        }
        return $this->mapPost($post);
    }

    public function findByTmdbId(int $tmdbId): ?TVShow
    {
        $posts = get_posts([
            'post_type' => 'tmdb_tv_show',
            'meta_key' => '_tmdb_id',
            'meta_value' => $tmdbId,
            'posts_per_page' => 1,
            'post_status' => 'any',
        ]);
        return $posts ? $this->mapPost($posts[0]) : null;
    }

    public function save(TVShow $tvShow): void
    {
        $postData = $tvShow->toArray();
        $postId = $tvShow->getId() > 0 ? $tvShow->getId() : 0;

        $postId = wp_insert_post(array_merge($postData, ['ID' => $postId]), true);

        if (is_wp_error($postId)) {
            throw new \RuntimeException('Failed to save TV show: ' . $postId->get_error_message());
        }

        $tvShow->setId($postId);
        $this->saveMeta($postId, $tvShow);
    }

    public function delete(int $id): bool
    {
        $result = wp_delete_post($id, true);
        return $result !== false && !is_wp_error($result);
    }

    public function findByGenre(int $genreTermId, int $limit = 20, int $offset = 0): array
    {
        $posts = get_posts([
            'post_type' => 'tmdb_tv_show',
            'tax_query' => [[
                'taxonomy' => 'tmdb_genre',
                'field' => 'term_id',
                'terms' => $genreTermId,
            ]],
            'posts_per_page' => $limit,
            'offset' => $offset,
            'post_status' => 'publish',
        ]);
        return array_map([$this, 'mapPost'], $posts);
    }

    public function findByYear(int $year, int $limit = 20, int $offset = 0): array
    {
        $posts = get_posts([
            'post_type' => 'tmdb_tv_show',
            'tax_query' => [[
                'taxonomy' => 'tmdb_release_year',
                'field' => 'slug',
                'terms' => (string) $year,
            ]],
            'posts_per_page' => $limit,
            'offset' => $offset,
            'post_status' => 'publish',
        ]);
        return array_map([$this, 'mapPost'], $posts);
    }

    public function findByNetwork(int $networkTermId, int $limit = 20, int $offset = 0): array
    {
        $posts = get_posts([
            'post_type' => 'tmdb_tv_show',
            'tax_query' => [[
                'taxonomy' => 'tmdb_network',
                'field' => 'term_id',
                'terms' => $networkTermId,
            ]],
            'posts_per_page' => $limit,
            'offset' => $offset,
            'post_status' => 'publish',
        ]);
        return array_map([$this, 'mapPost'], $posts);
    }

    private function saveMeta(int $postId, TVShow $tvShow): void
    {
        $meta = [
            '_tmdb_id' => $tvShow->getTmdbId(),
            '_tmdb_rating' => $tvShow->getRating(),
            '_tmdb_vote_count' => $tvShow->getVoteCount(),
            '_tmdb_first_air_date' => $tvShow->getFirstAirDate(),
            '_tmdb_last_air_date' => $tvShow->getLastAirDate(),
            '_tmdb_number_of_seasons' => $tvShow->getNumberOfSeasons(),
            '_tmdb_number_of_episodes' => $tvShow->getNumberOfEpisodes(),
            '_tmdb_poster_path' => $tvShow->getPosterPath(),
            '_tmdb_backdrop_path' => $tvShow->getBackdropPath(),
            '_tmdb_original_language' => $tvShow->getOriginalLanguage(),
            '_tmdb_popularity' => $tvShow->getPopularity(),
            '_tmdb_status' => $tvShow->getType(),
            '_tmdb_genre_ids' => $tvShow->getGenreIds(),
            '_tmdb_keyword_ids' => $tvShow->getKeywordIds(),
            '_tmdb_network_ids' => $tvShow->getNetworkIds(),
            '_tmdb_company_ids' => $tvShow->getCompanyIds(),
            '_tmdb_season_numbers' => $tvShow->getSeasonNumbers(),
        ];

        foreach ($meta as $key => $value) {
            if (is_array($value)) {
                update_post_meta($postId, $key, $value);
            } else {
                update_post_meta($postId, $key, $value);
            }
        }

        if ($tvShow->getPersonRelationships()) {
            update_post_meta($postId, '_tmdb_person_relationships', $tvShow->getPersonRelationships());
        }
    }

    private function mapPost(\WP_Post $post): TVShow
    {
        $tvShow = new TVShow();
        $tvShow->setId((int) $post->ID)
            ->setName($post->post_title)
            ->setOverview($post->post_content)
            ->setStatus($post->post_status);

        $tvShow->setTmdbId((int) get_post_meta($post->ID, '_tmdb_id', true))
            ->setRating((float) get_post_meta($post->ID, '_tmdb_rating', true))
            ->setVoteCount((int) get_post_meta($post->ID, '_tmdb_vote_count', true))
            ->setFirstAirDate(get_post_meta($post->ID, '_tmdb_first_air_date', true) ?: null)
            ->setLastAirDate(get_post_meta($post->ID, '_tmdb_last_air_date', true) ?: null)
            ->setNumberOfSeasons((int) get_post_meta($post->ID, '_tmdb_number_of_seasons', true))
            ->setNumberOfEpisodes((int) get_post_meta($post->ID, '_tmdb_number_of_episodes', true))
            ->setPosterPath(get_post_meta($post->ID, '_tmdb_poster_path', true) ?: null)
            ->setBackdropPath(get_post_meta($post->ID, '_tmdb_backdrop_path', true) ?: null)
            ->setOriginalLanguage(get_post_meta($post->ID, '_tmdb_original_language', true) ?: null)
            ->setPopularity(get_post_meta($post->ID, '_tmdb_popularity', true) ?: null)
            ->setType(get_post_meta($post->ID, '_tmdb_status', true) ?: '')
            ->setGenreIds((array) get_post_meta($post->ID, '_tmdb_genre_ids', true))
            ->setKeywordIds((array) get_post_meta($post->ID, '_tmdb_keyword_ids', true))
            ->setNetworkIds((array) get_post_meta($post->ID, '_tmdb_network_ids', true))
            ->setCompanyIds((array) get_post_meta($post->ID, '_tmdb_company_ids', true))
            ->setSeasonNumbers((array) get_post_meta($post->ID, '_tmdb_season_numbers', true))
            ->setPersonRelationships((array) get_post_meta($post->ID, '_tmdb_person_relationships', true));

        return $tvShow;
    }
}