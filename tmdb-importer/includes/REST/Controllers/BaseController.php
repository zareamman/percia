<?php
namespace TMDBImporter\REST\Controllers;

use TMDBImporter\Services\ImportService;
use TMDBImporter\Services\SyncService;
use TMDBImporter\Services\MetadataService;
use TMDBImporter\Services\ImageService;
use TMDBImporter\Services\VideoService;
use TMDBImporter\Services\TaxonomyService;
use WP_REST_Controller;
use WP_REST_Request;
use WP_REST_Response;

class BaseController extends WP_REST_Controller
{
    protected ImportService $importService;
    protected SyncService $syncService;
    protected MetadataService $metadataService;
    protected ImageService $imageService;
    protected VideoService $videoService;
    protected TaxonomyService $taxonomyService;

    public function __construct(
        ImportService $importService,
        SyncService $syncService,
        MetadataService $metadataService,
        ImageService $imageService,
        VideoService $videoService,
        TaxonomyService $taxonomyService
    ) {
        $this->importService = $importService;
        $this->syncService = $syncService;
        $this->metadataService = $metadataService;
        $this->imageService = $imageService;
        $this->videoService = $videoService;
        $this->taxonomyService = $taxonomyService;
    }

    protected function checkPermissions(WP_REST_Request $request): bool
    {
        return current_user_can('manage_tmdb_importer');
    }

    protected function validateRequest(WP_REST_Request $request, array $requiredParams): array
    {
        $errors = [];
        foreach ($requiredParams as $param) {
            if (!$request->has_param($param)) {
                $errors[] = "Missing required parameter: {$param}";
            }
        }
        return $errors;
    }

    protected function errorResponse(string $message, int $status = 400): WP_REST_Response
    {
        return new WP_REST_Response([
            'success' => false,
            'error' => $message,
        ], $status);
    }

    protected function successResponse(array $data, int $status = 200): WP_REST_Response
    {
        return new WP_REST_Response([
            'success' => true,
            'data' => $data,
        ], $status);
    }

    protected function getService(string $name): mixed
    {
        return match ($name) {
            'import' => $this->importService,
            'sync' => $this->syncService,
            'metadata' => $this->metadataService,
            'images' => $this->imageService,
            'videos' => $this->videoService,
            'taxonomy' => $this->taxonomyService,
            default => null,
        };
    }
}