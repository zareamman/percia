<?php
namespace TMDBImporter\Repositories\Database;

use TMDBImporter\Domain\Link;
use TMDBImporter\Repositories\Contracts\LinkRepositoryInterface;

class LinkRepository implements LinkRepositoryInterface
{
    private wpdb $wpdb;
    private string $table;

    public function __construct()
    {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->table = $wpdb->prefix . 'tmdb_links';
    }

    public function findById(int $id): ?Link
    {
        $row = $this->wpdb->get_row($this->wpdb->prepare("SELECT * FROM {$this->table} WHERE id = %d", $id), ARRAY_A);
        return $row ? $this->mapRow($row) : null;
    }

    public function findByObject(string $objectType, int $objectId): array
    {
        $rows = $this->wpdb->get_results(
            $this->wpdb->prepare("SELECT * FROM {$this->table} WHERE object_type = %s AND object_id = %d", $objectType, $objectId),
            ARRAY_A
        );
        return array_map([$this, 'mapRow'], $rows);
    }

    public function save(Link $link): void
    {
        $data = [
            'object_type' => $link->getObjectType(),
            'object_id' => $link->getObjectId(),
            'server' => $link->getServer(),
            'language' => $link->getLanguage(),
            'quality' => $link->getQuality(),
            'url' => $link->getUrl(),
        ];

        if ($link->getId() > 0) {
            $this->wpdb->update(
                $this->table,
                $data,
                ['id' => $link->getId()],
                ['%s', '%d', '%s', '%s', '%s', '%s'],
                ['%d']
            );
        } else {
            $data['created_at'] = current_time('mysql', true);
            $this->wpdb->insert(
                $this->table,
                $data,
                ['%s', '%d', '%s', '%s', '%s', '%s', '%s']
            );
            $link->setId((int) $this->wpdb->insert_id);
        }
    }

    public function delete(int $id): bool
    {
        $result = $this->wpdb->delete($this->table, ['id' => $id], ['%d']);
        return $result !== false;
    }

    public function deleteByObject(string $objectType, int $objectId): int
    {
        return (int) $this->wpdb->delete($this->table, ['object_type' => $objectType, 'object_id' => $objectId], ['%s', '%d']);
    }

    private function mapRow(array $row): Link
    {
        $link = new Link();
        $link->setId((int) $row['id'])
            ->setObjectType($row['object_type'])
            ->setObjectId((int) $row['object_id'])
            ->setServer($row['server'])
            ->setLanguage($row['language'])
            ->setQuality($row['quality'])
            ->setUrl($row['url']);
        return $link;
    }
}