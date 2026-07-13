<?php
namespace TMDBImporter\REST\Controllers;

use TMDBImporter\Repositories\Database\ImportJobRepository;
use TMDBImporter\Repositories\Database\SyncJobRepository;
use WP_REST_Controller;
use WP_REST_Request;
use WP_REST_Response;

class JobsController extends WP_REST_Controller
{
    private ImportJobRepository $importJobRepository;
    private SyncJobRepository $syncJobRepository;

    public function __construct()
    {
        $this->importJobRepository = new ImportJobRepository();
        $this->syncJobRepository = new SyncJobRepository();
        $this->namespace = 'tmdb-importer/v1';
        $this->rest_base = 'jobs';
    }

    public function register_routes(): void
    {
        register_rest_route($this->namespace, '/' . $this->rest_base, [
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this, 'getJobs'],
                'permission_callback' => [$this, 'checkPermissions'],
            ],
        ]);

        register_rest_route($this->namespace, '/' . $this->rest_base . '/import', [
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this, 'getImportJobs'],
                'permission_callback' => [$this, 'checkPermissions'],
            ],
        ]);

        register_rest_route($this->namespace, '/' . $this->rest_base . '/sync', [
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this, 'getSyncJobs'],
                'permission_callback' => [$this, 'checkPermissions'],
            ],
        ]);

        register_rest_route($this->namespace, '/' . $this->rest_base . '/import/(?P<id>\d+)', [
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this, 'getImportJob'],
                'permission_callback' => [$this, 'checkPermissions'],
                'args' => ['id' => ['required' => true, 'type' => 'integer']],
            ],
        ]);

        register_rest_route($this->namespace, '/' . $this->rest_base . '/sync/(?P<id>\d+)', [
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this, 'getSyncJob'],
                'permission_callback' => [$this, 'checkPermissions'],
                'args' => ['id' => ['required' => true, 'type' => 'integer']],
            ],
        ]);
    }

    public function checkPermissions(): bool
    {
        return current_user_can('manage_tmdb_importer');
    }

    public function getJobs(WP_REST_Request $request): WP_REST_Response
    {
        $importJobs = $this->importJobRepository->findByStatus('pending');
        $syncJobs = $this->syncJobRepository->findByStatus('pending');

        return new WP_REST_Response([
            'success' => true,
            'data' => [
                'import' => array_map(fn($j) => $j->toArray(), $importJobs),
                'sync' => array_map(fn($j) => $j->toArray(), $syncJobs),
            ],
        ], 200);
    }

    public function getImportJobs(WP_REST_Request $request): WP_REST_Response
    {
        $status = $request->get_param('status') ?? 'pending';
        $jobs = $this->importJobRepository->findByStatus($status);

        return new WP_REST_Response([
            'success' => true,
            'data' => array_map(fn($j) => $j->toArray(), $jobs),
        ], 200);
    }

    public function getSyncJobs(WP_REST_Request $request): WP_REST_Response
    {
        $status = $request->get_param('status') ?? 'pending';
        $jobs = $this->syncJobRepository->findByStatus($status);

        return new WP_REST_Response([
            'success' => true,
            'data' => array_map(fn($j) => $j->toArray(), $jobs),
        ], 200);
    }

    public function getImportJob(WP_REST_Request $request): WP_REST_Response
    {
        $job = $this->importJobRepository->findById((int) $request->get_param('id'));

        if (!$job) {
            return new WP_REST_Response(['success' => false, 'error' => 'Job not found'], 404);
        }

        return new WP_REST_Response([
            'success' => true,
            'data' => $job->toArray(),
        ], 200);
    }

    public function getSyncJob(WP_REST_Request $request): WP_REST_Response
    {
        $job = $this->syncJobRepository->findById((int) $request->get_param('id'));

        if (!$job) {
            return new WP_REST_Response(['success' => false, 'error' => 'Job not found'], 404);
        }

        return new WP_REST_Response([
            'success' => true,
            'data' => $job->toArray(),
        ], 200);
    }
}