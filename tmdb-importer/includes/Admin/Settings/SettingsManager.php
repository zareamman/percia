<?php
namespace TMDBImporter\Admin\Settings;

class SettingsManager
{
    private array $sections = [];

    public function __construct()
    {
        add_action('admin_init', [$this, 'registerSettings']);
        $this->initSections();
    }

    private function initSections(): void
    {
        $this->sections = [
            'api' => new APISection(),
            'import' => new ImportSection(),
            'images' => new ImagesSection(),
            'videos' => new VideosSection(),
            'maintenance' => new MaintenanceSection(),
        ];
    }

    public function registerSettings(): void
    {
        register_setting('tmdb_importer_settings', 'tmdb_importer_api');
        register_setting('tmdb_importer_settings', 'tmdb_importer_import');
        register_setting('tmdb_importer_settings', 'tmdb_importer_images');
        register_setting('tmdb_importer_settings', 'tmdb_importer_videos');

        foreach ($this->sections as $section) {
            $section->registerFields();
        }
    }

    public function getSections(): array
    {
        return $this->sections;
    }

    public function save(array $data): bool
    {
        $saved = true;
        foreach ($this->sections as $section) {
            if (!$section->save($data)) {
                $saved = false;
            }
        }
        return $saved;
    }
}