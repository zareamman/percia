<?php
namespace TMDBImporter\Database\Schema;

class SeasonsTable
{
    public static function getSchema(): string
    {
        global $wpdb;
        $table = $wpdb->prefix . 'tmdb_seasons';
        $charset = $wpdb->get_charset_collate();

        return "CREATE TABLE {$table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            tmdb_id bigint(20) unsigned NOT NULL,
            show_id bigint(20) unsigned NOT NULL,
            season_number int(11) NOT NULL,
            name varchar(255) DEFAULT '',
            overview longtext DEFAULT NULL,
            poster_path varchar(500) DEFAULT NULL,
            air_date date DEFAULT NULL,
            episode_count int(11) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY tmdb_id (tmdb_id),
            KEY show_id (show_id),
            KEY season_number (season_number),
            KEY show_season (show_id, season_number)
        ) {$charset};";
    }

    public static function getDropStatement(): string
    {
        global $wpdb;
        $table = $wpdb->prefix . 'tmdb_seasons';
        return "DROP TABLE IF EXISTS {$table};";
    }
}