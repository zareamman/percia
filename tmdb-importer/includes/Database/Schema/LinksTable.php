<?php
namespace TMDBImporter\Database\Schema;

class LinksTable
{
    public static function getTableName(): string
    {
        global $wpdb;
        return $wpdb->prefix . 'tmdb_links';
    }

    public static function getSchema(): string
    {
        global $wpdb;
        $table = self::getTableName();
        $charset = $wpdb->get_charset_collate();

        return "CREATE TABLE {$table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            object_type varchar(50) NOT NULL,
            object_id bigint(20) unsigned NOT NULL,
            server varchar(100) NOT NULL,
            language varchar(10) NOT NULL,
            quality varchar(50) NOT NULL,
            url text NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY object_lookup (object_type, object_id),
            KEY server (server),
            KEY language (language)
        ) {$charset};";
    }

    public static function getDropStatement(): string
    {
        $table = self::getTableName();
        return "DROP TABLE IF EXISTS {$table};";
    }
}