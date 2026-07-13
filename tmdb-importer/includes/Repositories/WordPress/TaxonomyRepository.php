<?php
namespace TMDBImporter\Repositories\WordPress;

use TMDBImporter\Domain\Person;
use TMDBImporter\Repositories\Contracts\TaxonomyRepositoryInterface;

class TaxonomyRepository implements TaxonomyRepositoryInterface
{
    public function getOrCreateTerm(string $taxonomy, string $name, int $tmdbId, array $meta = []): int
    {
        $term = get_term_by('meta_value', $tmdbId, $taxonomy, 'ARRAY_A', 'tmdb_id');

        if ($term) {
            $termId = (int) $term['term_id'];
            if (!empty($meta)) {
                foreach ($meta as $key => $value) {
                    update_term_meta($termId, $key, $value);
                }
            }
            return $termId;
        }

        $termId = wp_insert_term($name, $taxonomy);

        if (is_wp_error($termId)) {
            throw new \RuntimeException("Failed to create {$taxonomy} term: " . $termId->get_error_message());
        }

        $termId = $termId['term_id'];
        add_term_meta($termId, 'tmdb_id', $tmdbId, true);

        foreach ($meta as $key => $value) {
            update_term_meta($termId, $key, $value);
        }

        return $termId;
    }

    public function findByTmdbId(string $taxonomy, int $tmdbId): ?int
    {
        $term = get_term_by('meta_value', $tmdbId, $taxonomy, 'ARRAY_A', 'tmdb_id');
        return $term ? (int) $term['term_id'] : null;
    }

    public function getTerm(int $termId): ?array
    {
        $term = get_term($termId);
        return $term && !is_wp_error($term) ? [
            'term_id' => $term->term_id,
            'name' => $term->name,
            'slug' => $term->slug,
            'tmdb_id' => get_term_meta($term->term_id, 'tmdb_id', true),
        ] : null;
    }

    public function getTermMeta(int $termId, string $key = '', bool $single = true): mixed
    {
        return get_term_meta($termId, $key, $single);
    }

    public function updateTermMeta(int $termId, string $key, mixed $value): bool
    {
        return update_term_meta($termId, $key, $value);
    }

    public function deleteTerm(int $termId): bool
    {
        return wp_delete_term($termId) !== false;
    }

    public function attachToObject(int $objectId, string $taxonomy, array $termIds): void
    {
        $termIds = array_map('intval', $termIds);
        wp_set_object_terms($objectId, $termIds, $taxonomy, false);
    }

    public function getObjectTerms(int $objectId, string $taxonomy): array
    {
        $terms = wp_get_object_terms($objectId, $taxonomy, ['fields' => 'ids']);
        return is_wp_error($terms) ? [] : $terms;
    }

    public function getTermBySlug(string $taxonomy, string $slug): ?array
    {
        $term = get_term_by('slug', $slug, $taxonomy);
        return $term && !is_wp_error($term) ? [
            'term_id' => $term->term_id,
            'name' => $term->name,
            'slug' => $term->slug,
            'tmdb_id' => get_term_meta($term->term_id, 'tmdb_id', true),
        ] : null;
    }

    public function getPerson(int $termId): ?Person
    {
        $term = get_term($termId);
        if (!$term || is_wp_error($term) || $term->taxonomy !== 'tmdb_person') {
            return null;
        }

        $person = new Person();
        $person->setId((int) $term->term_id)
            ->setTmdbId((int) get_term_meta($term->term_id, 'tmdb_id', true))
            ->setName($term->name)
            ->setProfilePath(get_term_meta($term->term_id, 'profile_path', true) ?: null)
            ->setBiography(get_term_meta($term->term_id, 'biography', true) ?: null)
            ->setHomepage(get_term_meta($term->term_id, 'homepage', true) ?: null)
            ->setKnownForDepartment(get_term_meta($term->term_id, 'known_for_department', true) ?: null)
            ->setPopularity(get_term_meta($term->term_id, 'popularity', true) ?: null)
            ->setBirthday(get_term_meta($term->term_id, 'birthday', true) ?: null)
            ->setPlaceOfBirth(get_term_meta($term->term_id, 'place_of_birth', true) ?: null)
            ->setDeathday(get_term_meta($term->term_id, 'deathday', true) ?: null);

        return $person;
    }

    public function savePerson(Person $person): void
    {
        if ($person->getTermId() > 0) {
            $term = get_term($person->getTermId(), 'tmdb_person');
            if ($term && !is_wp_error($term)) {
                wp_update_term($term->term_id, 'tmdb_person', ['name' => $person->getName()]);
            }
        } else {
            $result = wp_insert_term($person->getName(), 'tmdb_person');
            if (is_wp_error($result)) {
                throw new \RuntimeException('Failed to create person term: ' . $result->get_error_message());
            }
            $person->setTermId($result['term_id']);
        }

        $this->savePersonMeta($person);
    }

    private function savePersonMeta(Person $person): void
    {
        $meta = [
            'tmdb_id' => $person->getTmdbId(),
            'profile_path' => $person->getProfilePath(),
            'biography' => $person->getBiography(),
            'homepage' => $person->getHomepage(),
            'known_for_department' => $person->getKnownForDepartment(),
            'popularity' => $person->getPopularity(),
            'birthday' => $person->getBirthday(),
            'place_of_birth' => $person->getPlaceOfBirth(),
            'deathday' => $person->getDeathday(),
        ];

        foreach ($meta as $key => $value) {
            if ($value !== null) {
                update_term_meta($person->getTermId(), $key, $value);
            }
        }
    }

    public function findPersonByTmdbId(int $tmdbId): ?Person
    {
        $term = get_term_by('meta_value', $tmdbId, 'tmdb_person', 'ARRAY_A', 'tmdb_id');
        return $term ? $this->getPerson((int) $term['term_id']) : null;
    }
}