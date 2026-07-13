<?php
namespace TMDBImporter\Repositories\Database;

use TMDBImporter\Domain\Season;
use TMDBImporter\Repositories\Contracts\SeasonRepositoryInterface;
use wpdb;

class SeasonRepository implements SeasonRepositoryInterface
{
    private wpdb $wpdb;
    private string $table;

    public function __construct()
    {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->table = $wpdb->prefix . 'tmdb_seasons';
    }

    public function findById(int $id): ?Season
    {
        $row = $this->wpdb->get_row($this->wpdb->prepare("SELECT * FROM {$this->table} WHERE id = %d", $id), ARRAY_A);
        return $row ? $this->mapRow($row) : null;
    }

    public function findByTmdbId(int $tmdbId): ?Season
    {
        $row = $this->wpdb->get_row($this->wpdb->prepare("SELECT * FROM {$this->table} WHERE tmdb_id = %d", $tmdbId), ARRAY_A);
        return $row ? $this->mapRow($row) : null;
    }

    public function findByShowAndNumber(int $showId, int $seasonNumber): ?Season
    {
        $row = $this->wpdb->get_row(
            $this->wpdb->prepare("SELECT * FROM {$this->table} WHERE show_id = %d AND season_number = %d", $showId, $seasonNumber),
            ARRAY_A
        );
        return $row ? $this->mapRow($row) : null;
    }

    public function findByShowId(int $showId): array
    {
        $rows = $this->wpdb->get_results(
            $this->wpdb->prepare("SELECT * FROM {$this->table} WHERE show_id = %d ORDER BY season_number ASC", $showId),
            ARRAY_A
        );
        return array_map([$this, 'mapRow'], $rows);
    }

    public function save(Season $season): void
    {
        $data = [
            'tmdb_id' => $season->getTmdbId(),
            'show_id' => $season->getShowId(),
            'season_number' => $season->getSeasonNumber(),
            'name' => $season->getName(),
            'overview' => $season->getOverview(),
            'poster_path' => $season->getPosterPath(),
            'air_date' => $season->getAirDate(),
            'episode_count' => $season->getEpisodeCount(),
            'updated_at' => current_time('mysql', true),
        ];

        if ($season->getId() > 0) {
            $this->wpdb->update(
                $this->table,
                $data,
                ['id' => $season->getId()],
                ['%d', '%d', '%d', '%s', '%s', '%s', '%s', '%d', '%s'],
                ['%d']
            );
        } else {
            $data['created_at'] = current_time('mysql', true);
            $this->wpdb->insert(
                $this->table,
                $data,
                ['%d', '%d', '%d', '%s', '%s', '%s', '%s', '%d', '%s', '%s']
            );
            $season->setId((int) $this->wpdb->insert_id);
        }
    }

    public function delete(int $id): bool
    {
        $result = $this->wpdb->delete($this->table, ['id' => $id], ['%d']);
        return $result !== false;
    }

    public function deleteByShowId(int $showId): int
    {
        return (int) $this->wpdb->delete($this->table, ['show_id' => $showId], ['%d']);
    }

    private function mapRow(array $row): Season
    {
        $season = new Season();
        $season->setId((int) $row['id'])
            ->setTmdbId((int) $row['tmdb_id'])
            ->setShowId((int) $row['show_id'])
            ->setSeasonNumber((int) $row['season_number'])
            ->setName($row['name'] ?? '')
            ->setOverview($row['overview'] ?? '')
            ->setPosterPath($row['poster_path'] ?? null)
            ->setAirDate($row['air_date'] ?? null)
            ->setEpisodeCount((int) ($row['episode_count'] ?? 0));
        return $season;
    }
}