<?php
namespace TMDBImporter\Repositories\WordPress;

use TMDBImporter\Domain\Movie;
use TMDBImporter\Repositories\Contracts\MovieRepositoryInterface;

class MovieRepository implements MovieRepositoryInterface
{
    public function findById(int $id): ?Movie
    {
        $post = get_post($id);
        if (!$post || $post->post_type !== 'tmdb_movie') {
            return null;
        }
        return $this->mapPost($post);
    }

    public function findByTmdbId(int $tmdbId): ?Movie
    {
        $posts = get_posts([
            'post_type' => 'tmdb_movie',
            'meta_key' => '_tmdb_id',
            'meta_value' => $tmdbId,
            'posts_per_page' => 1,
            'post_status' => 'any',
        ]);
        return $posts ? $this->mapPost($posts[0]) : null;
    }

    public function save(Movie $movie): void
    {
        $postData = $movie->toArray();
        $postId = $movie->getId() > 0 ? $movie->getId() : 0;

        $postId = wp_insert_post(array_merge($postData, ['ID' => $postId]), true);

        if (is_wp_error($postId)) {
            throw new \RuntimeException('Failed to save movie: ' . $postId->get_error_message());
        }

        $movie->setId($postId);
        $this->saveMeta($postId, $movie);
    }

    public function delete(int $id): bool
    {
        $result = wp_delete_post($id, true);
        return $result !== false && !is_wp_error($result);
    }

    public function findByGenre(int $genreTermId, int $limit = 20, int $offset = 0): array
    {
        $posts = get_posts([
            'post_type' => 'tmdb_movie',
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
            'post_type' => 'tmdb_movie',
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

    private function saveMeta(int $postId, Movie $movie): void
    {
        $meta = [
            '_tmdb_id' => $movie->getTmdbId(),
            '_tmdb_rating' => $movie->getRating(),
            '_tmdb_vote_count' => $movie->getVoteCount(),
            '_tmdb_release_date' => $movie->getReleaseDate(),
            '_tmdb_runtime' => $movie->getRuntime(),
            '_tmdb_poster_path' => $movie->getPosterPath(),
            '_tmdb_backdrop_path' => $movie->getBackdropPath(),
            '_tmdb_original_language' => $movie->getOriginalLanguage(),
            '_tmdb_popularity' => $movie->getPopularity(),
            '_tmdb_genre_ids' => $movie->getGenreIds(),
            '_tmdb_keyword_ids' => $movie->getKeywordIds(),
            '_tmdb_company_ids' => $movie->getCompanyIds(),
            '_tmdb_video_ids' => $movie->getVideoIds(),
        ];

        foreach ($meta as $key => $value) {
            if (is_array($value)) {
                update_post_meta($postId, $key, $value);
            } else {
                update_post_meta($postId, $key, $value);
            }
        }

        if ($movie->getPersonRelationships()) {
            update_post_meta($postId, '_tmdb_person_relationships', $movie->getPersonRelationships());
        }
    }

    private function mapPost(\WP_Post $post): Movie
    {
        $movie = new Movie();
        $movie->setId((int) $post->ID)
            ->setTitle($post->post_title)
            ->setOverview($post->post_content)
            ->setStatus($post->post_status);

        $movie->setTmdbId((int) get_post_meta($post->ID, '_tmdb_id', true))
            ->setRating((float) get_post_meta($post->ID, '_tmdb_rating', true))
            ->setVoteCount((int) get_post_meta($post->ID, '_tmdb_vote_count', true))
            ->setReleaseDate(get_post_meta($post->ID, '_tmdb_release_date', true) ?: null)
            ->setRuntime(get_post_meta($post->ID, '_tmdb_runtime', true) ?: null)
            ->setPosterPath(get_post_meta($post->ID, '_tmdb_poster_path', true) ?: null)
            ->setBackdropPath(get_post_meta($post->ID, '_tmdb_backdrop_path', true) ?: null)
            ->setOriginalLanguage(get_post_meta($post->ID, '_tmdb_original_language', true) ?: null)
            ->setPopularity(get_post_meta($post->ID, '_tmdb_popularity', true) ?: null)
            ->setGenreIds((array) get_post_meta($post->ID, '_tmdb_genre_ids', true))
            ->setKeywordIds((array) get_post_meta($post->ID, '_tmdb_keyword_ids', true))
            ->setCompanyIds((array) get_post_meta($post->ID, '_tmdb_company_ids', true))
            ->setVideoIds((array) get_post_meta($post->ID, '_tmdb_video_ids', true))
            ->setPersonRelationships((array) get_post_meta($post->ID, '_tmdb_person_relationships', true));

        return $movie;
    }
}