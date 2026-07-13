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
use TMDBImporter\Database\Migrations\MigrationManager;

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

    private function bootstrap(): void
    {
        $this->loadConfiguration();
        $this->runMigrations();
        $this->registerServices();
        $this->registerHooks();
        $this->registerPostTypes();
        $this->registerTaxonomies();
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

        $this->services['import'] = new ImportService(
            $this->services['tmdb_client'],
            $this->services['metadata'],
            $this->services['taxonomy'],
            $this->services['image'],
            $this->services['video'],
            $this->services['logger']
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
    }

    private function registerPostTypes(): void
    {
        $labelsMovie = [
            'name' => 'Movies',
            'singular_name' => 'Movie',
            'add_new' => 'Add New',
            'add_new_item' => 'Add New Movie',
            'edit_item' => 'Edit Movie',
            'new_item' => 'New Movie',
            'view_item' => 'View Movie',
            'search_items' => 'Search Movies',
            'not_found' => 'No movies found',
            'not_found_in_trash' => 'No movies found in Trash',
        ];

        register_post_type('tmdb_movie', [
            'labels' => $labelsMovie,
            'public' => true,
            'has_archive' => true,
            'supports' => ['title', 'editor', 'thumbnail', 'excerpt', 'custom-fields'],
            'rewrite' => ['slug' => 'movie'],
            'menu_icon' => 'dashicons-video-alt3',
            'show_in_rest' => true,
        ]);

        $labelsTVShow = [
            'name' => 'TV Shows',
            'singular_name' => 'TV Show',
            'add_new' => 'Add New',
            'add_new_item' => 'Add New TV Show',
            'edit_item' => 'Edit TV Show',
            'new_item' => 'New TV Show',
            'view_item' => 'View TV Show',
            'search_items' => 'Search TV Shows',
            'not_found' => 'No TV shows found',
            'not_found_in_trash' => 'No TV shows found in Trash',
        ];

        register_post_type('tmdb_tv_show', [
            'labels' => $labelsTVShow,
            'public' => true,
            'has_archive' => true,
            'supports' => ['title', 'editor', 'thumbnail', 'excerpt', 'custom-fields'],
            'rewrite' => ['slug' => 'tv-show'],
            'menu_icon' => 'dashicons-video-alt2',
            'show_in_rest' => true,
        ]);
    }

    private function registerTaxonomies(): void
    {
        $taxonomies = [
            'tmdb_genre' => ['tmdb_movie', 'tmdb_tv_show'],
            'tmdb_release_year' => ['tmdb_movie', 'tmdb_tv_show'],
            'tmdb_keyword' => ['tmdb_movie', 'tmdb_tv_show'],
            'tmdb_person' => ['tmdb_movie', 'tmdb_tv_show'],
            'tmdb_network' => ['tmdb_tv_show'],
            'tmdb_production_company' => ['tmdb_movie', 'tmdb_tv_show'],
        ];

        foreach ($taxonomies as $taxonomy => $objectTypes) {
            register_taxonomy($taxonomy, $objectTypes, [
                'labels' => [
                    'name' => ucfirst(str_replace('tmdb_', '', str_replace('_', ' ', $taxonomy))) . 's',
                    'singular_name' => ucfirst(str_replace('tmdb_', '', str_replace('_', ' ', $taxonomy))),
                ],
                'public' => true,
                'hierarchical' => false,
                'show_in_rest' => true,
                'show_admin_column' => true,
                'query_var' => true,
                'rewrite' => ['slug' => str_replace('tmdb_', '', $taxonomy)],
            ]);
        }
    }

    private function registerRestRoutes(): void
    {
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