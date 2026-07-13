<?php
namespace TMDBImporter\REST;

use TMDBImporter\REST\Controllers\ImportController;
use TMDBImporter\REST\Controllers\SyncController;
use TMDBImporter\REST\Controllers\JobsController;

class Routes
{
    public static function register(): void
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
}