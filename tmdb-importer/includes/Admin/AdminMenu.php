<?php
namespace TMDBImporter\Admin;

use TMDBImporter\Services\ImportService;
use TMDBImporter\Services\SyncService;
use TMDBImporter\Services\ImageService;
use TMDBImporter\Services\VideoService;
use TMDBImporter\Services\MetadataService;
use TMDBImporter\Services\TaxonomyService;

class AdminMenu
{
    private array $services;

    public function __construct(array $services)
    {
        $this->services = $services;
        add_action('admin_menu', [$this, 'registerMenu']);
    }

    public function registerMenu(): void
    {
        add_menu_page(
            'TMDB Importer',
            'TMDB Importer',
            'manage_tmdb_importer',
            'tmdb-importer',
            [$this, 'renderDashboard'],
            'dashicons-video-alt3',
            56
        );

        add_submenu_page(
            'tmdb-importer',
            'Import',
            'Import',
            'manage_tmdb_importer',
            'tmdb-importer-import',
            [$this, 'renderImport']
        );

        add_submenu_page(
            'tmdb-importer',
            'Sync',
            'Sync',
            'manage_tmdb_importer',
            'tmdb-importer-sync',
            [$this, 'renderSync']
        );

        add_submenu_page(
            'tmdb-importer',
            'Jobs',
            'Jobs',
            'manage_tmdb_importer',
            'tmdb-importer-jobs',
            [$this, 'renderJobs']
        );

        add_submenu_page(
            'tmdb-importer',
            'Settings',
            'Settings',
            'manage_tmdb_importer',
            'tmdb-importer-settings',
            [$this, 'renderSettings']
        );

        add_submenu_page(
            'tmdb-importer',
            'Logs',
            'Logs',
            'manage_tmdb_importer',
            'tmdb-importer-logs',
            [$this, 'renderLogs']
        );
    }

    public function renderDashboard(): void
    {
        include TMDB_IMPORTER_PATH . 'includes/Admin/Pages/DashboardPage.php';
    }

    public function renderImport(): void
    {
        include TMDB_IMPORTER_PATH . 'includes/Admin/Pages/ImportPage.php';
    }

    public function renderSync(): void
    {
        include TMDB_IMPORTER_PATH . 'includes/Admin/Pages/SyncPage.php';
    }

    public function renderJobs(): void
    {
        include TMDB_IMPORTER_PATH . 'includes/Admin/Pages/JobsPage.php';
    }

    public function renderSettings(): void
    {
        include TMDB_IMPORTER_PATH . 'includes/Admin/Pages/SettingsPage.php';
    }

    public function renderLogs(): void
    {
        include TMDB_IMPORTER_PATH . 'includes/Admin/Pages/LogsPage.php';
    }

    public function getServices(): array
    {
        return $this->services;
    }
}