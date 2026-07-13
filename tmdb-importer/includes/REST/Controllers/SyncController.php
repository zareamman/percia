<?php
namespace TMDBImporter\REST\Controllers;

use TMDBImporter\Services\SyncService;
use TMDBImporter\Services\ImportService;
use WP_REST_Controller;
use WP_REST_Request;
use WP_REST_Response;

class SyncController extends WP_REST_Controller
{
    private SyncService $syncService;

    public function __construct()
    {
        $this->syncService = new SyncService();
        $this->namespace = 'tmdb-importer/v1';
        $this->rest_base = 'sync';
    }

    public function register_routes(): void
    {
        register_rest_route($this->namespace, '/' . $this->rest_base . '/movie', [
            [
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => [$this, 'syncMovie'],
                'permission_callback' => [$this, 'checkPermissions'],
                'args' => $this->getMovieSyncArgs(),
            ],
        ]);

        register_rest_route($this->namespace, '/' . $this->rest_base . '/tv-show', [
            [
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => [$this, 'syncTVShow'],
                'permission_callback' => [$this, 'checkPermissions'],
                'args' => $this->getTVShowSyncArgs(),
            ],
        ]);

        register_rest_route($this->namespace, '/' . $this->rest_base . '/ratings', [
            [
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => [$this, 'syncRatings'],
                'permission_callback' => [$this, 'checkPermissions'],
                'args' => $this->getRatingsSyncArgs(),
            ],
        ]);
    }

    public function checkPermissions(): bool
    {
        return current_user_can('manage_tmdb_importer');
    }

    public function syncMovie(WP_REST_Request $request): WP_REST_Response
    {
        try {
            $results = $this->syncService->syncMovie(
                (int) $request->get_param('post_id'),
                [
                    'metadata' => (bool) $request->get_param('metadata'),
                    'ratings' => (bool) $request->get_param('ratings'),
                    'images' => (bool) $request->get_param('images'),
                    'videos' => (bool) $request->get_param('videos'),
                    'taxonomies' => (bool) $request->get_param('taxonomies'),
                ]
            );

            return new WP_REST_Response(['success' => true, 'data' => $results], 200);
        } catch (\Throwable $e) {
            return new WP_REST_Response(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function syncTVShow(WP_REST_Request $request): WP_REST_Response
    {
        try {
            $results = $this->syncService->syncTVShow(
                (int) $request->get_param('post_id'),
                [
                    'metadata' => (bool) $request->get_param('metadata'),
                    'ratings' => (bool) $request->get_param('ratings'),
                    'images' => (bool) $request->get_param('images'),
                    'videos' => (bool) $request->get_param('videos'),
                    'taxonomies' => (bool) $request->get_param('taxonomies'),
                    'seasons' => (bool) $request->get_param('seasons'),
                ]
            );

            return new WP_REST_Response(['success' => true, 'data' => $results], 200);
        } catch (\Throwable $e) {
            return new WP_REST_Response(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function syncRatings(WP_REST_Request $request): WP_REST_Response
    {
        try {
            $postIds = $request->get_param('post_ids');
            if (!is_array($postIds)) {
                $postIds = array_map('intval', explode(',', $postIds));
            }

            $results = $this->syncService->syncRatings($postIds);

            return new WP_REST_Response(['success' => true, 'data' => $results], 200);
        } catch (\Throwable $e) {
            return new WP_REST_Response(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    private function getMovieSyncArgs(): array
    {
        return [
            'post_id' => [
                'required' => true,
                'type' => 'integer',
                'validate_callback' => fn($v) => $v > 0,
            ],
            'metadata' => ['type' => 'boolean', 'default' => true],
            'ratings' => ['type' => 'boolean', 'default' => true],
            'images' => ['type' => 'boolean', 'default' => false],
            'videos' => ['type' => 'boolean', 'default' => false],
            'taxonomies' => ['type' => 'boolean', 'default' => true],
        ];
    }

    private function getTVShowSyncArgs(): array
    {
        return [
            'post_id' => [
                'required' => true,
                'type' => 'integer',
                'validate_callback' => fn($v) => $v > 0,
            ],
            'metadata' => ['type' => 'boolean', 'default' => true],
            'ratings' => ['type' => 'boolean', 'default' => true],
            'images' => ['type' => 'boolean', 'default' => false],
            'videos' => ['type' => 'boolean', 'default' => false],
            'taxonomies' => ['type' => 'boolean', 'default' => true],
            'seasons' => ['type' => 'boolean', 'default' => true],
        ];
    }

    private function getRatingsSyncArgs(): array
    {
        return [
            'post_ids' => [
                'required' => true,
                'type' => 'array',
                'items' => ['type' => 'integer'],
            ],
        ];
    }
}