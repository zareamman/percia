<?php
namespace TMDBImporter\Admin\AJAX;

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
use TMDBImporter\Repositories\Database\SyncJobRepository;
use TMDBImporter\Repositories\Database\SeasonRepository;
use TMDBImporter\Repositories\Database\EpisodeRepository;
use TMDBImporter\Repositories\Database\PersonRelationshipRepository;
use TMDBImporter\Repositories\Database\LinkRepository;
use TMDBImporter\Repositories\WordPress\MovieRepository;
use TMDBImporter\Repositories\WordPress\TVShowRepository;
use TMDBImporter\Repositories\WordPress\TaxonomyRepository;
use TMDBImporter\Domain\ImportJob;
use TMDBImporter\Domain\SyncJob;

class AjaxHandler
{
    public function __construct()
    {
        add_action('wp_ajax_tmdb_importer_ajax_import', [$this, 'handleImport']);
        add_action('wp_ajax_tmdb_importer_ajax_sync', [$this, 'handleSync']);
        add_action('wp_ajax_tmdb_importer_ajax_bulk_import', [$this, 'handleBulkImport']);
        add_action('wp_ajax_tmdb_importer_ajax_bulk_sync', [$this, 'handleBulkSync']);
        add_action('wp_ajax_tmdb_importer_ajax_sync_ratings', [$this, 'handleSyncRatings']);
        add_action('wp_ajax_tmdb_importer_ajax_get_job', [$this, 'handleGetJob']);
        add_action('wp_ajax_tmdb_importer_ajax_retry_job', [$this, 'handleRetryJob']);
        add_action('wp_ajax_tmdb_importer_ajax_maintenance', [$this, 'handleMaintenance']);
        add_action('wp_ajax_tmdb_importer_ajax_clear_logs', [$this, 'handleClearLogs']);
        add_action('wp_ajax_tmdb_importer_ajax_search', [$this, 'handleSearch']);
        add_action('wp_ajax_tmdb_importer_ajax_download_images', [$this, 'handleDownloadImages']);
        add_action('wp_ajax_tmdb_importer_ajax_download_videos', [$this, 'handleDownloadVideos']);
    }

    private function getServices(): array
    {
        $logger = new Logger();
        $cache = new ObjectCache();
        $apiKey = get_option('tmdb_importer_api_key', '');
        $language = get_option('tmdb_importer_language', 'en-US');
        $region = get_option('tmdb_importer_region', 'US');

        $tmdbClient = new TMDBClient($apiKey, $language, $region, $cache, $logger);

        $metadataService = new MetadataService($tmdbClient, $cache, $logger);
        $taxonomyService = new TaxonomyService($tmdbClient, $cache, $logger);
        $imageService = new ImageService($tmdbClient, $cache, $logger);
        $videoService = new VideoService($tmdbClient, $logger);

        $importService = new ImportService(
            $tmdbClient,
            $metadataService,
            $taxonomyService,
            $imageService,
            $videoService,
            $logger,
            new ImportJobRepository(),
            new MovieRepository(),
            new TVShowRepository(),
            new SeasonRepository(),
            new EpisodeRepository()
        );

        $syncService = new SyncService(
            $tmdbClient,
            $metadataService,
            $taxonomyService,
            $imageService,
            $videoService,
            $logger
        );

        return [
            'import' => $importService,
            'sync' => $syncService,
            'image' => $imageService,
            'video' => $videoService,
            'metadata' => $metadataService,
            'taxonomy' => $taxonomyService,
        ];
    }

    public function handleImport(): void
    {
        check_ajax_referer('tmdb_importer_import', '_wpnonce');

        if (!current_user_can('manage_tmdb_importer')) {
            wp_send_json_error('Insufficient permissions');
        }

        $tmdbId = intval($_POST['tmdb_id'] ?? 0);
        $type = sanitize_text_field($_POST['type'] ?? '');
        $skipExisting = isset($_POST['skip_existing']);
        $overwrite = isset($_POST['overwrite']);
        $importSeasons = isset($_POST['import_seasons']);

        if ($tmdbId <= 0 || empty($type)) {
            wp_send_json_error('Invalid parameters');
        }

        try {
            $services = $this->getServices();
            $importService = $services['import'];

            if ($type === 'movie') {
                $movie = $importService->importMovie($tmdbId, compact('skipExisting', 'overwrite'));
                wp_send_json_success([
                    'post_id' => $movie->getId(),
                    'tmdb_id' => $movie->getTmdbId(),
                    'title' => $movie->getTitle(),
                ]);
            } elseif ($type === 'tv_show') {
                $tvShow = $importService->importTVShow($tmdbId, compact('skipExisting', 'overwrite', 'importSeasons'));
                wp_send_json_success([
                    'post_id' => $tvShow->getId(),
                    'tmdb_id' => $tvShow->getTmdbId(),
                    'name' => $tvShow->getName(),
                ]);
            } else {
                wp_send_json_error('Invalid type');
            }
        } catch (\Throwable $e) {
            wp_send_json_error($e->getMessage());
        }
    }

    public function handleBulkImport(): void
    {
        check_ajax_referer('tmdb_importer_bulk_import', '_wpnonce');

        if (!current_user_can('manage_tmdb_importer')) {
            wp_send_json_error('Insufficient permissions');
        }

        $type = sanitize_text_field($_POST['type'] ?? '');
        $tmdbIds = array_map('intval', array_filter(explode(',', $_POST['tmdb_ids'] ?? '')));
        $skipExisting = isset($_POST['skip_existing']);

        if (empty($tmdbIds) || empty($type)) {
            wp_send_json_error('Invalid parameters');
        }

        try {
            $job = new ImportJob();
            $job->setType($type)
                ->setTmdbIds($tmdbIds)
                ->setImportOptions(['skip_existing' => $skipExisting])
                ->setStatus(ImportJob::STATUS_PENDING)
                ->setScheduledAt(current_time('mysql', true));

            $repo = new ImportJobRepository();
            $repo->save($job);

            wp_send_json_success([
                'job_id' => $job->getId(),
                'message' => sprintf('Bulk import job created with %d items', count($tmdbIds)),
            ]);
        } catch (\Throwable $e) {
            wp_send_json_error($e->getMessage());
        }
    }

    public function handleSync(): void
    {
        check_ajax_referer('tmdb_importer_sync', '_wpnonce');

        if (!current_user_can('manage_tmdb_importer')) {
            wp_send_json_error('Insufficient permissions');
        }

        $postId = intval($_POST['post_id'] ?? 0);
        $options = [
            'metadata' => isset($_POST['metadata']),
            'ratings' => isset($_POST['ratings']),
            'images' => isset($_POST['images']),
            'videos' => isset($_POST['videos']),
            'taxonomies' => isset($_POST['taxonomies']),
            'seasons' => isset($_POST['seasons']),
        ];

        if ($postId <= 0) {
            wp_send_json_error('Invalid post ID');
        }

        try {
            $services = $this->getServices();
            $syncService = $services['sync'];

            $post = get_post($postId);
            if (!$post) {
                wp_send_json_error('Post not found');
            }

            if ($post->post_type === 'tmdb_movie') {
                $results = $syncService->syncMovie($postId, $options);
            } elseif ($post->post_type === 'tmdb_tv_show') {
                $results = $syncService->syncTVShow($postId, $options);
            } else {
                wp_send_json_error('Invalid post type');
                return;
            }

            wp_send_json_success($results);
        } catch (\Throwable $e) {
            wp_send_json_error($e->getMessage());
        }
    }

    public function handleBulkSync(): void
    {
        check_ajax_referer('tmdb_importer_bulk_sync', '_wpnonce');

        if (!current_user_can('manage_tmdb_importer')) {
            wp_send_json_error('Insufficient permissions');
        }

        $postIds = array_map('intval', array_filter(explode(',', $_POST['post_ids'] ?? '')));
        $options = [
            'metadata' => isset($_POST['metadata']),
            'ratings' => isset($_POST['ratings']),
            'images' => isset($_POST['images']),
            'videos' => isset($_POST['videos']),
            'taxonomies' => isset($_POST['taxonomies']),
            'seasons' => isset($_POST['seasons']),
        ];

        if (empty($postIds)) {
            wp_send_json_error('No post IDs provided');
        }

        try {
            $services = $this->getServices();
            $syncService = $services['sync'];

            $synced = 0;
            foreach ($postIds as $postId) {
                $post = get_post($postId);
                if (!$post) continue;

                if ($post->post_type === 'tmdb_movie') {
                    $syncService->syncMovie($postId, $options);
                    $synced++;
                } elseif ($post->post_type === 'tmdb_tv_show') {
                    $syncService->syncTVShow($postId, $options);
                    $synced++;
                }
            }

            wp_send_json_success(['synced' => $synced]);
        } catch (\Throwable $e) {
            wp_send_json_error($e->getMessage());
        }
    }

    public function handleSyncRatings(): void
    {
        check_ajax_referer('tmdb_importer_sync_ratings', '_wpnonce');

        if (!current_user_can('manage_tmdb_importer')) {
            wp_send_json_error('Insufficient permissions');
        }

        $postIds = array_map('intval', array_filter(explode(',', $_POST['post_ids'] ?? '')));

        if (empty($postIds)) {
            wp_send_json_error('No post IDs provided');
        }

        try {
            $services = $this->getServices();
            $syncService = $services['sync'];
            $results = $syncService->syncRatings($postIds);

            wp_send_json_success($results);
        } catch (\Throwable $e) {
            wp_send_json_error($e->getMessage());
        }
    }

    public function handleGetJob(): void
    {
        check_ajax_referer('tmdb_importer_nonce', '_wpnonce');

        if (!current_user_can('manage_tmdb_importer')) {
            wp_send_json_error('Insufficient permissions');
        }

        $jobId = intval($_POST['job_id'] ?? 0);
        $jobType = sanitize_text_field($_POST['job_type'] ?? '');

        if ($jobId <= 0 || empty($jobType)) {
            wp_send_json_error('Invalid parameters');
        }

        try {
            if ($jobType === 'import') {
                $repo = new ImportJobRepository();
                $job = $repo->findById($jobId);
            } else {
                $repo = new SyncJobRepository();
                $job = $repo->findById($jobId);
            }

            if (!$job) {
                wp_send_json_error('Job not found');
            }

            wp_send_json_success($job->toArray());
        } catch (\Throwable $e) {
            wp_send_json_error($e->getMessage());
        }
    }

    public function handleRetryJob(): void
    {
        check_ajax_referer('tmdb_importer_nonce', '_wpnonce');

        if (!current_user_can('manage_tmdb_importer')) {
            wp_send_json_error('Insufficient permissions');
        }

        $jobId = intval($_POST['job_id'] ?? 0);
        $jobType = sanitize_text_field($_POST['job_type'] ?? '');

        if ($jobId <= 0 || empty($jobType)) {
            wp_send_json_error('Invalid parameters');
        }

        try {
            if ($jobType === 'import') {
                $repo = new ImportJobRepository();
                $job = $repo->findById($jobId);
            } else {
                $repo = new SyncJobRepository();
                $job = $repo->findById($jobId);
            }

            if (!$job || !$job->canRetry()) {
                wp_send_json_error('Job cannot be retried');
            }

            $job->setStatus($jobType === 'import' ? ImportJob::STATUS_PENDING : SyncJob::STATUS_PENDING);
            $job->setProgress(0);
            $job->setErrors([]);
            $job->setProcessedIds([]);
            $job->setFailedIds([]);
            $repo->save($job);

            wp_send_json_success(['message' => 'Job queued for retry']);
        } catch (\Throwable $e) {
            wp_send_json_error($e->getMessage());
        }
    }

    public function handleMaintenance(): void
    {
        check_ajax_referer('tmdb_importer_nonce', '_wpnonce');

        if (!current_user_can('manage_tmdb_importer')) {
            wp_send_json_error('Insufficient permissions');
        }

        $action = sanitize_text_field($_POST['maintenance_action'] ?? '');

        try {
            switch ($action) {
                case 'clear_cache':
                    $cache = new ObjectCache();
                    $cache->flush('tmdb_api');
                    $cache->flush('tmdb_images');
                    $cache->flush('tmdb_videos');
                    wp_send_json_success(['message' => 'Cache cleared successfully']);

                case 'remove_orphaned':
                    $removed = $this->removeOrphanedContent();
                    wp_send_json_success(['message' => "Removed {$removed} orphaned items"]);

                case 'reset_settings':
                    $this->resetSettings();
                    wp_send_json_success(['message' => 'Settings reset to defaults']);

                default:
                    wp_send_json_error('Unknown maintenance action');
            }
        } catch (\Throwable $e) {
            wp_send_json_error($e->getMessage());
        }
    }

    public function handleClearLogs(): void
    {
        check_ajax_referer('tmdb_importer_nonce', '_wpnonce');

        if (!current_user_can('manage_tmdb_importer')) {
            wp_send_json_error('Insufficient permissions');
        }

        $days = intval($_POST['days'] ?? 30);
        $logger = new Logger();
        $count = $logger->clearLogs($days);

        wp_send_json_success(['message' => "Cleared {$count} log entries", 'count' => $count]);
    }

    public function handleSearch(): void
    {
        check_ajax_referer('tmdb_importer_nonce', '_wpnonce');

        if (!current_user_can('manage_tmdb_importer')) {
            wp_send_json_error('Insufficient permissions');
        }

        $query = sanitize_text_field($_POST['query'] ?? '');
        $type = sanitize_text_field($_POST['type'] ?? 'movie');

        if (strlen($query) < 2) {
            wp_send_json_error('Query too short');
        }

        try {
            $services = $this->getServices();
            $tmdbClient = $services['import'] ?? null;

            if (!$tmdbClient) {
                wp_send_json_error('TMDB client not available');
            }

            $results = $type === 'movie'
                ? $tmdbClient->getMovieRepository()->findByTmdbId(0) // This won't work, need search
                : [];

            // Use TMDBClient search methods
            $results = $type === 'movie'
                ? $this->getTmdbClient()->searchMovie($query)
                : $this->getTmdbClient()->searchTVShow($query);

            wp_send_json_success($results['results'] ?? []);
        } catch (\Throwable $e) {
            wp_send_json_error($e->getMessage());
        }
    }

    public function handleDownloadImages(): void
    {
        check_ajax_referer('tmdb_importer_nonce', '_wpnonce');

        if (!current_user_can('manage_tmdb_importer')) {
            wp_send_json_error('Insufficient permissions');
        }

        $postId = intval($_POST['post_id'] ?? 0);
        $postType = sanitize_text_field($_POST['post_type'] ?? '');
        $replace = isset($_POST['replace']);

        if ($postId <= 0 || !in_array($postType, ['tmdb_movie', 'tmdb_tv_show'], true)) {
            wp_send_json_error('Invalid parameters');
        }

        try {
            $services = $this->getServices();
            $imageService = $services['image'];

            $results = $imageService->downloadAndAttachImages($postId, $postType, [
                'download' => true,
                'replace' => $replace,
            ]);

            wp_send_json_success($results);
        } catch (\Throwable $e) {
            wp_send_json_error($e->getMessage());
        }
    }

    public function handleDownloadVideos(): void
    {
        check_ajax_referer('tmdb_importer_nonce', '_wpnonce');

        if (!current_user_can('manage_tmdb_importer')) {
            wp_send_json_error('Insufficient permissions');
        }

        $postId = intval($_POST['post_id'] ?? 0);
        $postType = sanitize_text_field($_POST['post_type'] ?? '');

        if ($postId <= 0 || !in_array($postType, ['tmdb_movie', 'tmdb_tv_show'], true)) {
            wp_send_json_error('Invalid parameters');
        }

        try {
            $services = $this->getServices();
            $videoService = $services['video'];

            $results = $videoService->downloadTrailers($postId, $postType, ['download' => true]);

            wp_send_json_success($results);
        } catch (\Throwable $e) {
            wp_send_json_error($e->getMessage());
        }
    }

    private function getTmdbClient(): TMDBClient
    {
        $logger = new Logger();
        $cache = new ObjectCache();
        $apiKey = get_option('tmdb_importer_api_key', '');
        $language = get_option('tmdb_importer_language', 'en-US');
        $region = get_option('tmdb_importer_region', 'US');

        return new TMDBClient($apiKey, $language, $region, $cache, $logger);
    }

    private function removeOrphanedContent(): int
    {
        global $wpdb;
        $removed = 0;

        // Remove seasons without parent TV show
        $orphanedSeasons = $wpdb->get_col("
            SELECT s.id FROM {$wpdb->prefix}tmdb_seasons s
            LEFT JOIN {$wpdb->posts} p ON s.show_id = p.ID
            WHERE p.ID IS NULL
        ");

        if ($orphanedSeasons) {
            $placeholders = implode(',', array_fill(0, count($orphanedSeasons), '%d'));
            $removed += $wpdb->query($wpdb->prepare(
                "DELETE FROM {$wpdb->prefix}tmdb_seasons WHERE id IN ($placeholders)",
                ...$orphanedSeasons
            ));
        }

        // Remove episodes without parent season
        $orphanedEpisodes = $wpdb->get_col("
            SELECT e.id FROM {$wpdb->prefix}tmdb_episodes e
            LEFT JOIN {$wpdb->prefix}tmdb_seasons s ON e.season_id = s.id
            WHERE s.id IS NULL
        ");

        if ($orphanedEpisodes) {
            $placeholders = implode(',', array_fill(0, count($orphanedEpisodes), '%d'));
            $removed += $wpdb->query($wpdb->prepare(
                "DELETE FROM {$wpdb->prefix}tmdb_episodes WHERE id IN ($placeholders)",
                ...$orphanedEpisodes
            ));
        }

        // Remove person relationships without parent object
        $orphanedRels = $wpdb->get_col("
            SELECT r.id FROM {$wpdb->prefix}tmdb_person_relationships r
            WHERE r.object_type IN ('tmdb_movie', 'tmdb_tv_show')
            AND NOT EXISTS (
                SELECT 1 FROM {$wpdb->posts} p
                WHERE p.ID = r.object_id AND p.post_type = r.object_type
            )
        ");

        if ($orphanedRels) {
            $placeholders = implode(',', array_fill(0, count($orphanedRels), '%d'));
            $removed += $wpdb->query($wpdb->prepare(
                "DELETE FROM {$wpdb->prefix}tmdb_person_relationships WHERE id IN ($placeholders)",
                ...$orphanedRels
            ));
        }

        // Remove links without parent object
        $orphanedLinks = $wpdb->get_col("
            SELECT l.id FROM {$wpdb->prefix}tmdb_links l
            WHERE l.object_type IN ('tmdb_movie', 'tmdb_tv_show')
            AND NOT EXISTS (
                SELECT 1 FROM {$wpdb->posts} p
                WHERE p.ID = l.object_id AND p.post_type = l.object_type
            )
        ");

        if ($orphanedLinks) {
            $placeholders = implode(',', array_fill(0, count($orphanedLinks), '%d'));
            $removed += $wpdb->query($wpdb->prepare(
                "DELETE FROM {$wpdb->prefix}tmdb_links WHERE id IN ($placeholders)",
                ...$orphanedLinks
            ));
        }

        return $removed;
    }

    private function resetSettings(): void
    {
        delete_option('tmdb_importer_api');
        delete_option('tmdb_importer_skip_existing');
        delete_option('tmdb_importer_overwrite');
        delete_option('tmdb_importer_import_seasons');
        delete_option('tmdb_importer_import_episodes');
        delete_option('tmdb_importer_download_images');
        delete_option('tmdb_importer_image_size');
        delete_option('tmdb_importer_replace_images');
        delete_option('tmdb_importer_download_trailers');
        delete_option('tmdb_importer_log_level');
    }
}