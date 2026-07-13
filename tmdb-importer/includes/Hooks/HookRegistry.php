<?php
namespace TMDBImporter\Hooks;

use TMDBImporter\Infrastructure\Logging\Logger;
use TMDBImporter\Repositories\Database\ImportJobRepository;
use TMDBImporter\Repositories\Database\SeasonRepository;
use TMDBImporter\Repositories\Database\EpisodeRepository;
use TMDBImporter\Repositories\WordPress\MovieRepository;
use TMDBImporter\Repositories\WordPress\TVShowRepository;
use TMDBImporter\Admin\AdminMenu;
use TMDBImporter\Admin\Settings\SettingsManager;
use TMDBImporter\Admin\Settings\Sections\APISection;
use TMDBImporter\Admin\Settings\Sections\ImportSection;
use TMDBImporter\Admin\Settings\Sections\ImagesSection;
use TMDBImporter\Admin\Settings\Sections\VideosSection;
use TMDBImporter\Admin\Settings\Sections\MaintenanceSection;

class HookRegistry
{
    public static function register(): void
    {
        add_action('init', [self::class, 'registerPostTypes']);
        add_action('init', [self::class, 'registerTaxonomies']);
        add_action('admin_menu', [self::class, 'registerAdminMenu']);
        add_action('admin_init', [self::class, 'registerSettings']);
        add_action('admin_enqueue_scripts', [self::class, 'enqueueAdminAssets']);
        add_filter('cron_schedules', [self::class, 'addCronSchedules']);

        add_action('before_delete_post', [self::class, 'cleanupOnDelete']);
        add_action('delete_term', [self::class, 'cleanupTermRelationships'], 10, 3);

        add_action('tmdb_importer_sync_cron', [self::class, 'runSyncCron']);
        add_action('tmdb_importer_process_jobs', [self::class, 'processJobs']);
    }

    public static function registerPostTypes(): void
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

    public static function registerTaxonomies(): void
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

    public static function registerAdminMenu(): void
    {
        if (!is_admin()) {
            return;
        }

        $services = self::getServices();
        new AdminMenu($services);
    }

    public static function registerSettings(): void
    {
        $settingsManager = new SettingsManager();
        $sections = [
            'api' => new APISection(),
            'import' => new ImportSection(),
            'images' => new ImagesSection(),
            'videos' => new VideosSection(),
            'maintenance' => new MaintenanceSection(),
        ];

        foreach ($sections as $slug => $section) {
            $section->registerFields();
        }
    }

    public static function enqueueAdminAssets(): void
    {
        $screen = get_current_screen();
        if (!$screen || strpos($screen->id, 'tmdb-importer') === false) {
            return;
        }

        wp_enqueue_style(
            'tmdb-importer-admin',
            TMDB_IMPORTER_URL . 'assets/css/admin.css',
            [],
            TMDB_IMPORTER_VERSION
        );

        wp_enqueue_script(
            'tmdb-importer-admin',
            TMDB_IMPORTER_URL . 'assets/js/admin.js',
            ['jquery'],
            TMDB_IMPORTER_VERSION,
            true
        );

        wp_localize_script('tmdb-importer-admin', 'tmdbImporterNonce', wp_create_nonce('tmdb_importer_nonce'));
    }

    public static function addCronSchedules(array $schedules): array
    {
        $schedules['every_15_minutes'] = [
            'interval' => 15 * MINUTE_IN_SECONDS,
            'display' => 'Every 15 Minutes',
        ];
        $schedules['every_30_minutes'] = [
            'interval' => 30 * MINUTE_IN_SECONDS,
            'display' => 'Every 30 Minutes',
        ];
        return $schedules;
    }

    public static function cleanupOnDelete(int $postId): void
    {
        global $wpdb;

        $post = get_post($postId);
        if (!$post) {
            return;
        }

        $logger = new Logger();

        if ($post->post_type === 'tmdb_tv_show') {
            $logger->info('Cleaning up TV show deletion', ['post_id' => $postId]);

            // Delete seasons
            $seasonRepo = new SeasonRepository();
            $seasonRepo->deleteByShowId($postId);

            // Delete person relationships
            $relRepo = new PersonRelationshipRepository();
            $relRepo->deleteByObject('tmdb_tv_show', $postId);

            // Delete links
            $linkRepo = new LinkRepository();
            $linkRepo->deleteByObject('tmdb_tv_show', $postId);
        } elseif ($post->post_type === 'tmdb_movie') {
            $logger->info('Cleaning up movie deletion', ['post_id' => $postId]);

            // Delete person relationships
            $relRepo = new PersonRelationshipRepository();
            $relRepo->deleteByObject('tmdb_movie', $postId);

            // Delete links
            $linkRepo = new LinkRepository();
            $linkRepo->deleteByObject('tmdb_movie', $postId);
        }
    }

    public static function cleanupTermRelationships(int $termId, int $ttId, string $taxonomy): void
    {
        if (!str_starts_with($taxonomy, 'tmdb_')) {
            return;
        }

        global $wpdb;

        if ($taxonomy === 'tmdb_person') {
            $relRepo = new PersonRelationshipRepository();
            $relRepo->deleteByPersonAndObject($termId, 'tmdb_movie', 0); // Delete all for this person
            $relRepo->deleteByPersonAndObject($termId, 'tmdb_tv_show', 0);
        }
    }

    public static function runSyncCron(): void
    {
        $logger = new Logger();
        $logger->info('Running scheduled sync cron');
    }

    public static function processJobs(): void
    {
        $logger = new Logger();
        $logger->debug('Processing background jobs');
    }

    private static function getServices(): array
    {
        static $services = null;

        if ($services === null) {
            $logger = new Logger();
            $cache = new \TMDBImporter\Infrastructure\Cache\ObjectCache();
            $apiKey = get_option('tmdb_importer_api_key', '');
            $language = get_option('tmdb_importer_language', 'en-US');
            $region = get_option('tmdb_importer_region', 'US');

            $tmdbClient = new \TMDBImporter\Infrastructure\TMDB\TMDBClient($apiKey, $language, $region, $cache, $logger);

            $metadataService = new \TMDBImporter\Services\MetadataService($tmdbClient, $cache, $logger);
            $taxonomyService = new \TMDBImporter\Services\TaxonomyService($tmdbClient, $cache, $logger);
            $imageService = new \TMDBImporter\Services\ImageService($tmdbClient, $cache, $logger);
            $videoService = new \TMDBImporter\Services\VideoService($tmdbClient, $logger);

            $importJobRepository = new ImportJobRepository();
            $movieRepository = new \TMDBImporter\Repositories\WordPress\MovieRepository();
            $tvShowRepository = new \TMDBImporter\Repositories\WordPress\TVShowRepository();
            $seasonRepository = new SeasonRepository();
            $episodeRepository = new EpisodeRepository();

            $importService = new \TMDBImporter\Services\ImportService(
                $tmdbClient,
                $metadataService,
                $taxonomyService,
                $imageService,
                $videoService,
                $logger,
                $importJobRepository,
                $movieRepository,
                $tvShowRepository,
                $seasonRepository,
                $episodeRepository
            );

            $syncService = new \TMDBImporter\Services\SyncService(
                $tmdbClient,
                $metadataService,
                $taxonomyService,
                $imageService,
                $videoService,
                $logger
            );

            $services = [
                'import' => $importService,
                'sync' => $syncService,
                'metadata' => $metadataService,
                'taxonomy' => $taxonomyService,
                'image' => $imageService,
                'video' => $videoService,
                'logger' => $logger,
                'cache' => $cache,
                'tmdb_client' => $tmdbClient,
            ];
        }

        return $services;
    }
}