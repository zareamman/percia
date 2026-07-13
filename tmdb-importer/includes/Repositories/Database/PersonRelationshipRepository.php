<?php
namespace TMDBImporter\Repositories\Database;

use TMDBImporter\Domain\PersonRelationship;
use TMDBImporter\Repositories\Contracts\PersonRelationshipRepositoryInterface;
use wpdb;

class PersonRelationshipRepository implements PersonRelationshipRepositoryInterface
{
    private wpdb $wpdb;
    private string $table;

    public function __construct()
    {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->table = $wpdb->prefix . 'tmdb_person_relationships';
    }

    public function findById(int $id): ?PersonRelationship
    {
        $row = $this->wpdb->get_row($this->wpdb->prepare("SELECT * FROM {$this->table} WHERE id = %d", $id), ARRAY_A);
        return $row ? $this->mapRow($row) : null;
    }

    public function findByPersonAndObject(int $personTermId, string $objectType, int $objectId): array
    {
        $rows = $this->wpdb->get_results(
            $this->wpdb->prepare("SELECT * FROM {$this->table} WHERE person_term_id = %d AND object_type = %s AND object_id = %d ORDER BY credit_order ASC", $personTermId, $objectType, $objectId),
            ARRAY_A
        );
        return array_map([$this, 'mapRow'], $rows);
    }

    public function findByObject(string $objectType, int $objectId): array
    {
        $rows = $this->wpdb->get_results(
            $this->wpdb->prepare("SELECT * FROM {$this->table} WHERE object_type = %s AND object_id = %d ORDER BY credit_order ASC", $objectType, $objectId),
            ARRAY_A
        );
        return array_map([$this, 'mapRow'], $rows);
    }

    public function save(PersonRelationship $relationship): void
    {
        $data = [
            'person_term_id' => $relationship->getPersonTermId(),
            'object_type' => $relationship->getObjectType(),
            'object_id' => $relationship->getObjectId(),
            'role' => $relationship->getRole(),
            'character_name' => $relationship->getCharacterName(),
            'department' => $relationship->getDepartment(),
            'credit_order' => $relationship->getCreditOrder(),
            'updated_at' => current_time('mysql', true),
        ];

        if ($relationship->getId() > 0) {
            $this->wpdb->update(
                $this->table,
                $data,
                ['id' => $relationship->getId()],
                ['%d', '%s', '%d', '%s', '%s', '%s', '%d', '%s'],
                ['%d']
            );
        } else {
            $data['created_at'] = current_time('mysql', true);
            $this->wpdb->insert(
                $this->table,
                $data,
                ['%d', '%s', '%d', '%s', '%s', '%s', '%d', '%s', '%s']
            );
            $relationship->setId((int) $this->wpdb->insert_id);
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

    public function deleteByPersonAndObject(int $personTermId, string $objectType, int $objectId): int
    {
        return (int) $this->wpdb->delete($this->table, ['person_term_id' => $personTermId, 'object_type' => $objectType, 'object_id' => $objectId], ['%d', '%s', '%d']);
    }

    private function mapRow(array $row): PersonRelationship
    {
        $rel = new PersonRelationship();
        $rel->setId((int) $row['id'])
            ->setPersonTermId((int) $row['person_term_id'])
            ->setObjectType($row['object_type'])
            ->setObjectId((int) $row['object_id'])
            ->setRole($row['role'])
            ->setCharacterName($row['character_name'] ?? null)
            ->setDepartment($row['department'] ?? null)
            ->setCreditOrder((int) ($row['credit_order'] ?? 0));
        return $rel;
    }
}