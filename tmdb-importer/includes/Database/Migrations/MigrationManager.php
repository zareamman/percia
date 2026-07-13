<?php
namespace TMDBImporter\Database\Migrations;

use TMDBImporter\Database\Schema\SeasonsTable;
use TMDBImporter\Database\Schema\EpisodesTable;
use TMDBImporter\Database\Schema\PersonRelationshipsTable;
use TMDBImporter\Database\Schema\LinksTable;
use TMDBImporter\Database\Schema\ImportJobsTable;
use TMDBImporter\Database\Schema\SyncJobsTable;
use TMDBImporter\Infrastructure\Logging\Logger;

class MigrationManager
{
    private array $migrations = [];
    private Logger $logger;

    public function __construct(Logger $logger = null)
    {
        $this->logger = $logger ?? new Logger();
        $this->registerMigrations();
    }

    private function registerMigrations(): void
    {
        $this->migrations = [
            1 => [
                'description' => 'Create initial tables',
                'up' => function () {
                    $this->createTable(SeasonsTable::class);
                    $this->createTable(EpisodesTable::class);
                    $this->createTable(PersonRelationshipsTable::class);
                    $this->createTable(LinksTable::class);
                    $this->createTable(ImportJobsTable::class);
                    $this->createTable(SyncJobsTable::class);
                    $this->createLogsTable();
                },
                'down' => function () {
                    $this->dropTable(SyncJobsTable::class);
                    $this->dropTable(ImportJobsTable::class);
                    $this->dropTable(LinksTable::class);
                    $this->dropTable(PersonRelationshipsTable::class);
                    $this->dropTable(EpisodesTable::class);
                    $this->dropTable(SeasonsTable::class);
                    $this->dropLogsTable();
                },
            ],
        ];
    }

    public function run(): void
    {
        global $wpdb;
        $currentVersion = (int) get_option('tmdb_importer_db_version', 0);
        $latestVersion = max(array_keys($this->migrations));

        if ($currentVersion >= $latestVersion) {
            return;
        }

        for ($version = $currentVersion + 1; $version <= $latestVersion; $version++) {
            if (!isset($this->migrations[$version])) {
                continue;
            }

            $migration = $this->migrations[$version];
            $this->logger->info("Running migration {$version}: {$migration['description']}");

            try {
                $wpdb->query('START TRANSACTION');
                $migration['up']();
                $wpdb->query('COMMIT');
                update_option('tmdb_importer_db_version', $version);
                $this->logger->info("Migration {$version} completed successfully");
            } catch (\Throwable $e) {
                $wpdb->query('ROLLBACK');
                $this->logger->error("Migration {$version} failed", ['error' => $e->getMessage()]);
                throw $e;
            }
        }
    }

    public function rollback(int $targetVersion = 0): void
    {
        global $wpdb;
        $currentVersion = (int) get_option('tmdb_importer_db_version', 0);

        if ($currentVersion <= $targetVersion) {
            return;
        }

        for ($version = $currentVersion; $version > $targetVersion; $version--) {
            if (!isset($this->migrations[$version])) {
                continue;
            }

            $migration = $this->migrations[$version];
            $this->logger->info("Rolling back migration {$version}: {$migration['description']}");

            try {
                $wpdb->query('START TRANSACTION');
                $migration['down']();
                $wpdb->query('COMMIT');
                update_option('tmdb_importer_db_version', $version - 1);
                $this->logger->info("Rollback {$version} completed successfully");
            } catch (\Throwable $e) {
                $wpdb->query('ROLLBACK');
                $this->logger->error("Rollback {$version} failed", ['error' => $e->getMessage()]);
                throw $e;
            }
        }
    }

    public function getCurrentVersion(): int
    {
        return (int) get_option('tmdb_importer_db_version', 0);
    }

    public function getLatestVersion(): int
    {
        return max(array_keys($this->migrations));
    }

    private function createTable(string $class): void
    {
        global $wpdb;
        $sql = $class::getSchema();
        $wpdb->query($sql);
    }

    private function dropTable(string $class): void
    {
        global $wpdb;
        $sql = $class::getDropStatement();
        $wpdb->query($sql);
    }

    private function createLogsTable(): void
    {
        global $wpdb;
        $table = $wpdb->prefix . 'tmdb_logs';
        $charset = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            level varchar(20) NOT NULL,
            message text NOT NULL,
            context longtext DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY level (level),
            KEY created_at (created_at)
        ) {$charset};";

        $wpdb->query($sql);
    }

    private function dropLogsTable(): void
    {
        global $wpdb;
        $table = $wpdb->prefix . 'tmdb_logs';
        $wpdb->query("DROP TABLE IF EXISTS {$table};");
    }
}