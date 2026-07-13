<?php
namespace TMDBImporter\Core;

use TMDBImporter\Services\ImportService;
use TMDBImporter\Services\SyncService;
use TMDBImporter\Services\ImageService;
use TMDBImporter\Services\VideoService;
use TMDBImporter\Services\MetadataService;
use TMDBImporter\Services\TaxonomyService;
use TMDBImporter\Infrastructure\TMDB\TMDBClient;
use TMDBImporter\Infrastructure\Cache\ObjectCache;
use TMDBImporter\Infrastructure\Logging\Logger;
use TMDBImporter\Repositories\Database\ImportJobRepository;
use TMDBImporter\Repositories\Database\SeasonRepository;
use TMDBImporter\Repositories\Database\EpisodeRepository;
use TMDBImporter\Repositories\WordPress\MovieRepository;
use TMDBImporter\Repositories\WordPress\TVShowRepository;

class Bootstrap
{
    private static ?self $instance = null;
    private array $services = [];

    private function __construct()
    {
    }

    public static function init(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
            self::$instance->bootstrap();
        }
        return self::$instance;
    }

    public static function getInstance(): ?self
    {
        return self::$instance;
    }

    public static function activate(): void
    {
        $migrationManager = new MigrationManager();
        $migrationManager->run();

        flush_rewrite_rules();
    }

    public static function deactivate(): void
    {
        wp_clear_scheduled_hook('tmdb_importer_sync_cron');
        flush_rewrite_rules();
    }

    public static function uninstall(): void
    {
        // Uninstall is handled in uninstall.php
    }

    private function bootstrap(): void
    {
        $this->loadConfiguration();
        $this->runMigrations();
        $this->registerServices();
        $this->registerHooks();
        $this->registerRestRoutes();
        $this->registerAdmin();
        $this->scheduleCronJobs();

        do_action('tmdb_importer_loaded', $this);
    }

    private function loadConfiguration(): void
    {
    }

    private function runMigrations(): void
    {
        $migrationManager = new MigrationManager();
        $migrationManager->run();
    }

    private function registerServices(): void
    {
        $this->services['logger'] = new Logger();
        $this->services['cache'] = new ObjectCache();
        $this->services['tmdb_client'] = new TMDBClient(
            get_option('tmdb_importer_api_key', ''),
            get_option('tmdb_importer_language', 'en-US'),
            get_option('tmdb_importer_region', 'US'),
            $this->services['cache'],
            $this->services['logger']
        );

        $this->services['metadata'] = new MetadataService(
            $this->services['tmdb_client'],
            $this->services['cache'],
            $this->services['logger']
        );

        $this->services['taxonomy'] = new TaxonomyService(
            $this->services['tmdb_client'],
            $this->services['cache'],
            $this->services['logger']
        );

        $this->services['image'] = new ImageService(
            $this->services['tmdb_client'],
            $this->services['cache'],
            $this->services['logger']
        );

        $this->services['video'] = new VideoService(
            $this->services['tmdb_client'],
            $this->services['cache'],
            $this->services['logger']
        );

        $this->services['import_job_repo'] = new ImportJobRepository();
        $this->services['movie_repo'] = new MovieRepository();
        $this->services['tv_show_repo'] = new TVShowRepository();
        $this->services['season_repo'] = new SeasonRepository();
        $this->services['episode_repo'] = new EpisodeRepository();

        $this->services['import'] = new ImportService(
            $this->services['tmdb_client'],
            $this->services['metadata'],
            $this->services['taxonomy'],
            $this->services['image'],
            $this->services['video'],
            $this->services['logger'],
            $this->services['import_job_repo'],
            $this->services['movie_repo'],
            $this->services['tv_show_repo'],
            $this->services['season_repo'],
            $this->services['episode_repo']
        );

        $this->services['sync'] = new SyncService(
            $this->services['tmdb_client'],
            $this->services['metadata'],
            $this->services['taxonomy'],
            $this->services['image'],
            $this->services['video'],
            $this->services['logger']
        );
    }

    private function registerHooks(): void
    {
        // Register all WordPress hooks via HookRegistry
        \TMDBImporter\Hooks\HookRegistry::register();

        // AJAX handlers
        require_once TMDB_IMPORTER_PATH . 'includes/Admin/AJAX/AjaxHandler.php';
        new \TMDBImporter\Admin\AJAX\AjaxHandler();

        // Cron jobs
        add_action('tmdb_importer_sync_cron', [$this, 'runSyncCron']);
    }

    public function runSyncCron(): void
    {
        // This would trigger scheduled sync jobs
        // For now, just a placeholder
        $logger = $this->services['logger'] ?? new Logger();
        $logger->info('Sync cron job triggered');
    }

    private function registerRestRoutes(): void
    {
        add_action('rest_api_init', function () {
            $importController = new ImportController();
            $importController->register_routes();

            $syncController = new SyncController();
            $syncController->register_routes();

            $jobsController = new JobsController();
            $jobsController->register_routes();
        });
    }

    private function registerAdmin(): void
    {
        if (is_admin()) {
            require_once TMDB_IMPORTER_PATH . 'includes/Admin/AdminMenu.php';
            new \TMDBImporter\Admin\AdminMenu($this->getServices());
        }
    }

    private function scheduleCronJobs(): void
    {
        if (!wp_next_scheduled('tmdb_importer_sync_cron')) {
            wp_schedule_event(time(), 'hourly', 'tmdb_importer_sync_cron');
        }
    }

    public function getService(string $name): mixed
    {
        return $this->services[$name] ?? null;
    }

    public function getServices(): array
    {
        return $this->services;
    }
}