<?php
namespace TMDBImporter\Database\Schema;

class PersonRelationshipsTable
{
    public static function getTableName(): string
    {
        global $wpdb;
        return $wpdb->prefix . 'tmdb_person_relationships';
    }

    public static function getSchema(): string
    {
        global $wpdb;
        $table = self::getTableName();
        $charset = $wpdb->get_charset_collate();

        return "CREATE TABLE {$table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            person_term_id bigint(20) unsigned NOT NULL,
            object_type varchar(50) NOT NULL,
            object_id bigint(20) unsigned NOT NULL,
            role varchar(100) NOT NULL DEFAULT '',
            character_name varchar(255) DEFAULT NULL,
            department varchar(100) DEFAULT NULL,
            credit_order int(11) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY person_term_id (person_term_id),
            KEY object_lookup (object_type, object_id),
            KEY role (role),
            KEY credit_order (credit_order)
        ) {$charset};";
    }

    public static function getDropStatement(): string
    {
        $table = self::getTableName();
        return "DROP TABLE IF EXISTS {$table};";
    }
}