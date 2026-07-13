<?php
namespace TMDBImporter\Database\Schema;

class EpisodesTable
{
    public static function getTableName(): string
    {
        global $wpdb;
        return $wpdb->prefix . 'tmdb_episodes';
    }

    public static function getSchema(): string
    {
        global $wpdb;
        $table = self::getTableName();
        $charset = $wpdb->get_charset_collate();

        return "CREATE TABLE {$table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            tmdb_id bigint(20) unsigned NOT NULL,
            season_id bigint(20) unsigned NOT NULL,
            episode_number int(11) NOT NULL,
            name varchar(255) DEFAULT '',
            overview longtext DEFAULT NULL,
            runtime int(11) DEFAULT NULL,
            air_date date DEFAULT NULL,
            still_path varchar(500) DEFAULT NULL,
            rating decimal(3,1) DEFAULT 0.0,
            vote_count int(11) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY tmdb_id (tmdb_id),
            KEY season_id (season_id),
            KEY episode_number (episode_number)
        ) {$charset};";
    }

    public static function getDropStatement(): string
    {
        $table = self::getTableName();
        return "DROP TABLE IF EXISTS {$table};";
    }
}