<?php
namespace TMDBImporter\Repositories\Database;

use TMDBImporter\Domain\Episode;
use TMDBImporter\Repositories\Contracts\EpisodeRepositoryInterface;
use wpdb;

class EpisodeRepository implements EpisodeRepositoryInterface
{
    private wpdb $wpdb;
    private string $table;

    public function __construct()
    {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->table = $wpdb->prefix . 'tmdb_episodes';
    }

    public function findById(int $id): ?Episode
    {
        $row = $this->wpdb->get_row($this->wpdb->prepare("SELECT * FROM {$this->table} WHERE id = %d", $id), ARRAY_A);
        return $row ? $this->mapRow($row) : null;
    }

    public function findByTmdbId(int $tmdbId): ?Episode
    {
        $row = $this->wpdb->get_row($this->wpdb->prepare("SELECT * FROM {$this->table} WHERE tmdb_id = %d", $tmdbId), ARRAY_A);
        return $row ? $this->mapRow($row) : null;
    }

    public function findBySeasonAndNumber(int $seasonId, int $episodeNumber): ?Episode
    {
        $row = $this->wpdb->get_row(
            $this->wpdb->prepare("SELECT * FROM {$this->table} WHERE season_id = %d AND episode_number = %d", $seasonId, $episodeNumber),
            ARRAY_A
        );
        return $row ? $this->mapRow($row) : null;
    }

    public function findBySeasonId(int $seasonId): array
    {
        $rows = $this->wpdb->get_results(
            $this->wpdb->prepare("SELECT * FROM {$this->table} WHERE season_id = %d ORDER BY episode_number ASC", $seasonId),
            ARRAY_A
        );
        return array_map([$this, 'mapRow'], $rows);
    }

    public function save(Episode $episode): void
    {
        $data = [
            'tmdb_id' => $episode->getTmdbId(),
            'season_id' => $episode->getSeasonId(),
            'episode_number' => $episode->getEpisodeNumber(),
            'name' => $episode->getName(),
            'overview' => $episode->getOverview(),
            'runtime' => $episode->getRuntime(),
            'air_date' => $episode->getAirDate(),
            'still_path' => $episode->getStillPath(),
            'rating' => $episode->getRating(),
            'vote_count' => $episode->getVoteCount(),
            'updated_at' => current_time('mysql', true),
        ];

        if ($episode->getId() > 0) {
            $this->wpdb->update(
                $this->table,
                $data,
                ['id' => $episode->getId()],
                ['%d', '%d', '%d', '%s', '%s', '%d', '%s', '%s', '%f', '%d', '%s'],
                ['%d']
            );
        } else {
            $data['created_at'] = current_time('mysql', true);
            $this->wpdb->insert(
                $this->table,
                $data,
                ['%d', '%d', '%d', '%s', '%s', '%d', '%s', '%s', '%f', '%d', '%s', '%s']
            );
            $episode->setId((int) $this->wpdb->insert_id);
        }
    }

    public function delete(int $id): bool
    {
        $result = $this->wpdb->delete($this->table, ['id' => $id], ['%d']);
        return $result !== false;
    }

    public function deleteBySeasonId(int $seasonId): int
    {
        return (int) $this->wpdb->delete($this->table, ['season_id' => $seasonId], ['%d']);
    }

    private function mapRow(array $row): Episode
    {
        $episode = new Episode();
        $episode->setId((int) $row['id'])
            ->setTmdbId((int) $row['tmdb_id'])
            ->setSeasonId((int) $row['season_id'])
            ->setEpisodeNumber((int) $row['episode_number'])
            ->setName($row['name'] ?? '')
            ->setOverview($row['overview'] ?? '')
            ->setRuntime(isset($row['runtime']) ? (int) $row['runtime'] : null)
            ->setAirDate($row['air_date'] ?? null)
            ->setStillPath($row['still_path'] ?? null)
            ->setRating((float) ($row['rating'] ?? 0))
            ->setVoteCount((int) ($row['vote_count'] ?? 0));
        return $episode;
    }
}