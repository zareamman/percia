<?php
namespace TMDBImporter\Background;

use TMDBImporter\Infrastructure\Logging\Logger;
use TMDBImporter\Services\ImportService;
use TMDBImporter\Services\SyncService;
use TMDBImporter\Infrastructure\TMDB\TMDBClient;
use TMDBImporter\Infrastructure\Cache\ObjectCache;

class CronHandler
{
    public static function init(): void
    {
        add_action('tmdb_importer_sync_cron', [self::class, 'runScheduledSync']);
        add_action('tmdb_importer_process_jobs', [self::class, 'processJobs']);
    }

    public static function runScheduledSync(): void
    {
        $logger = new Logger();
        $logger->info('Running scheduled sync');

        try {
            // Get posts that need syncing (e.g., older than 24 hours)
            $posts = get_posts([
                'post_type' => ['tmdb_movie', 'tmdb_tv_show'],
                'posts_per_page' => 50,
                'meta_query' => [
                    'relation' => 'OR',
                    [
                        'key' => '_tmdb_last_sync',
                        'compare' => 'NOT EXISTS',
                    ],
                    [
                        'key' => '_tmdb_last_sync',
                        'value' => date('Y-m-d H:i:s', strtotime('-24 hours')),
                        'compare' => '<',
                        'type' => 'DATETIME',
                    ],
                ],
            ]);

            if (empty($posts)) {
                $logger->info('No posts need syncing');
                return;
            }

            $tmdbClient = new TMDBClient(
                get_option('tmdb_importer_api_key', ''),
                get_option('tmdb_importer_language', 'en-US'),
                get_option('tmdb_importer_region', 'US'),
                new ObjectCache(),
                $logger
            );

            $syncService = new SyncService(
                $tmdbClient,
                new \TMDBImporter\Services\MetadataService($tmdbClient, new ObjectCache(), $logger),
                new \TMDBImporter\Services\TaxonomyService($tmdbClient, new ObjectCache(), $logger),
                new \TMDBImporter\Services\ImageService($tmdbClient, new ObjectCache(), $logger),
                new \TMDBImporter\Services\VideoService($tmdbClient, new ObjectCache(), $logger),
                $logger
            );

            foreach ($posts as $post) {
                $options = ['metadata' => true, 'ratings' => true, 'taxonomies' => true];
                if ($post->post_type === 'tmdb_tv_show') {
                    $options['seasons'] = true;
                }

                try {
                    $syncService->{'sync' . ucfirst(str_replace('tmdb_', '', $post->post_type))}($post->ID, $options);
                    update_post_meta($post->ID, '_tmdb_last_sync', current_time('mysql', true));
                } catch (\Throwable $e) {
                    $logger->error('Scheduled sync failed', ['post_id' => $post->ID, 'error' => $e->getMessage()]);
                }
            }

            $logger->info('Scheduled sync completed', ['synced' => count($posts)]);
        } catch (\Throwable $e) {
            $logger->error('Scheduled sync error', ['error' => $e->getMessage()]);
        }
    }

    public static function processJobs(): void
    {
        $logger = new Logger();
        $runner = new \TMDBImporter\Background\JobRunner(
            new ImportService(
                new TMDBClient('', 'en-US', 'US', new ObjectCache(), $logger),
                new \TMDBImporter\Services\MetadataService(
                    new TMDBClient('', 'en-US', 'US', new ObjectCache(), $logger),
                    new ObjectCache(),
                    $logger
                ),
                new \TMDBImporter\Services\TaxonomyService(
                    new TMDBClient('', 'en-US', 'US', new ObjectCache(), $logger),
                    new ObjectCache(),
                    $logger
                ),
                new \TMDBImporter\Services\ImageService(
                    new TMDBClient('', 'en-US', 'US', new ObjectCache(), $logger),
                    new ObjectCache(),
                    $logger
                ),
                new \TMDBImporter\Services\VideoService(
                    new TMDBClient('', 'en-US', 'US', new ObjectCache(), $logger),
                    new ObjectCache(),
                    $logger
                ),
                $logger
            ),
            $logger
        );

        $processed = 0;
        for ($i = 0; $i < 5; $i++) { // Process up to 5 jobs per cron run
            $result = $runner->run();
            if ($result === 0) {
                break;
            }
            $processed += $result;
        }

        if ($processed > 0) {
            $logger->info('Processed background jobs', ['count' => $processed]);
        }
    }
}