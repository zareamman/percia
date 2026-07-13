<?php
namespace TMDBImporter\Database\Schema;

class ImportJobsTable
{
    public static function getTableName(): string
    {
        global $wpdb;
        return $wpdb->prefix . 'tmdb_import_jobs';
    }

    public static function getSchema(): string
    {
        global $wpdb;
        $table = self::getTableName();
        $charset = $wpdb->get_charset_collate();

        return "CREATE TABLE {$table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            type varchar(50) NOT NULL,
            tmdb_ids longtext NOT NULL,
            import_options longtext DEFAULT NULL,
            status varchar(20) NOT NULL DEFAULT 'pending',
            progress decimal(5,2) DEFAULT 0.00,
            processed_ids longtext DEFAULT NULL,
            failed_ids longtext DEFAULT NULL,
            errors longtext DEFAULT NULL,
            scheduled_at datetime DEFAULT NULL,
            started_at datetime DEFAULT NULL,
            completed_at datetime DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY status (status),
            KEY type (type),
            KEY scheduled_at (scheduled_at)
        ) {$charset};";
    }

    public static function getDropStatement(): string
    {
        $table = self::getTableName();
        return "DROP TABLE IF EXISTS {$table};";
    }
}