<?php
namespace TMDBImporter\REST\Controllers;

use TMDBImporter\Services\ImportService;
use WP_REST_Controller;
use WP_REST_Request;
use WP_REST_Response;

class ImportController extends WP_REST_Controller
{
    private ImportService $importService;

    public function __construct()
    {
        $this->importService = new ImportService();
        $this->namespace = 'tmdb-importer/v1';
        $this->rest_base = 'import';
    }

    public function register_routes(): void
    {
        register_rest_route($this->namespace, '/' . $this->rest_base . '/movie', [
            [
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => [$this, 'importMovie'],
                'permission_callback' => [$this, 'checkPermissions'],
                'args' => $this->getMovieImportArgs(),
            ],
        ]);

        register_rest_route($this->namespace, '/' . $this->rest_base . '/tv-show', [
            [
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => [$this, 'importTVShow'],
                'permission_callback' => [$this, 'checkPermissions'],
                'args' => $this->getTVShowImportArgs(),
            ],
        ]);

        register_rest_route($this->namespace, '/' . $this->rest_base . '/bulk', [
            [
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => [$this, 'importBulk'],
                'permission_callback' => [$this, 'checkPermissions'],
                'args' => $this->getBulkImportArgs(),
            ],
        ]);
    }

    public function checkPermissions(): bool
    {
        return current_user_can('manage_tmdb_importer');
    }

    public function importMovie(WP_REST_Request $request): WP_REST_Response
    {
        try {
            $movie = $this->importService->importMovie(
                (int) $request->get_param('tmdb_id'),
                [
                    'skip_existing' => (bool) $request->get_param('skip_existing'),
                    'overwrite' => (bool) $request->get_param('overwrite'),
                ]
            );

            return new WP_REST_Response([
                'success' => true,
                'data' => [
                    'post_id' => $movie->getId(),
                    'tmdb_id' => $movie->getTmdbId(),
                    'title' => $movie->getTitle(),
                ],
            ], 200);
        } catch (\Throwable $e) {
            return new WP_REST_Response(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function importTVShow(WP_REST_Request $request): WP_REST_Response
    {
        try {
            $tvShow = $this->importService->importTVShow(
                (int) $request->get_param('tmdb_id'),
                [
                    'skip_existing' => (bool) $request->get_param('skip_existing'),
                    'overwrite' => (bool) $request->get_param('overwrite'),
                    'import_seasons' => (bool) $request->get_param('import_seasons'),
                ]
            );

            return new WP_REST_Response([
                'success' => true,
                'data' => [
                    'post_id' => $tvShow->getId(),
                    'tmdb_id' => $tvShow->getTmdbId(),
                    'name' => $tvShow->getName(),
                ],
            ], 200);
        } catch (\Throwable $e) {
            return new WP_REST_Response(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function importBulk(WP_REST_Request $request): WP_REST_Response
    {
        try {
            $results = $this->importService->importBulk(
                $request->get_param('tmdb_ids'),
                $request->get_param('type'),
                null
            );

            return new WP_REST_Response([
                'success' => true,
                'data' => $results,
            ], 200);
        } catch (\Throwable $e) {
            return new WP_REST_Response(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    private function getMovieImportArgs(): array
    {
        return [
            'tmdb_id' => [
                'required' => true,
                'type' => 'integer',
                'validate_callback' => fn($v) => $v > 0,
            ],
            'skip_existing' => ['type' => 'boolean', 'default' => true],
            'overwrite' => ['type' => 'boolean', 'default' => false],
        ];
    }

    private function getTVShowImportArgs(): array
    {
        return [
            'tmdb_id' => [
                'required' => true,
                'type' => 'integer',
                'validate_callback' => fn($v) => $v > 0,
            ],
            'skip_existing' => ['type' => 'boolean', 'default' => true],
            'overwrite' => ['type' => 'boolean', 'default' => false],
            'import_seasons' => ['type' => 'boolean', 'default' => true],
        ];
    }

    private function getBulkImportArgs(): array
    {
        return [
            'tmdb_ids' => [
                'required' => true,
                'type' => 'array',
                'items' => ['type' => 'integer'],
            ],
            'type' => [
                'required' => true,
                'type' => 'string',
                'enum' => ['movie', 'tv_show'],
            ],
            'skip_existing' => ['type' => 'boolean', 'default' => true],
            'overwrite' => ['type' => 'boolean', 'default' => false],
        ];
    }
}