<?php
namespace TMDBImporter\Admin\Settings\Sections;

class ImportSection
{
    public function registerFields(): void
    {
        add_settings_section(
            'tmdb_importer_import_section',
            'Import Settings',
            [$this, 'renderDescription'],
            'tmdb_importer_settings'
        );

        add_settings_field(
            'tmdb_importer_skip_existing',
            'Skip Existing',
            [$this, 'renderSkipExisting'],
            'tmdb_importer_settings',
            'tmdb_importer_import_section'
        );

        add_settings_field(
            'tmdb_importer_overwrite',
            'Overwrite Existing',
            [$this, 'renderOverwrite'],
            'tmdb_importer_settings',
            'tmdb_importer_import_section'
        );

        add_settings_field(
            'tmdb_importer_import_seasons',
            'Import Missing Seasons',
            [$this, 'renderImportSeasons'],
            'tmdb_importer_settings',
            'tmdb_importer_import_section'
        );

        add_settings_field(
            'tmdb_importer_import_episodes',
            'Import Missing Episodes',
            [$this, 'renderImportEpisodes'],
            'tmdb_importer_settings',
            'tmdb_importer_import_section'
        );
    }

    public function renderDescription(): void
    {
        echo '<p>Configure how imports behave.</p>';
    }

    public function renderSkipExisting(): void
    {
        $value = get_option('tmdb_importer_skip_existing', true);
        echo '<input type="checkbox" name="tmdb_importer_import[skip_existing]" value="1" ' . checked($value, true, false) . ' />';
    }

    public function renderOverwrite(): void
    {
        $value = get_option('tmdb_importer_overwrite', false);
        echo '<input type="checkbox" name="tmdb_importer_import[overwrite]" value="1" ' . checked($value, true, false) . ' />';
    }

    public function renderImportSeasons(): void
    {
        $value = get_option('tmdb_importer_import_seasons', true);
        echo '<input type="checkbox" name="tmdb_importer_import[import_seasons]" value="1" ' . checked($value, true, false) . ' />';
    }

    public function renderImportEpisodes(): void
    {
        $value = get_option('tmdb_importer_import_episodes', true);
        echo '<input type="checkbox" name="tmdb_importer_import[import_episodes]" value="1" ' . checked($value, true, false) . ' />';
    }

    public function save(array $data): bool
    {
        update_option('tmdb_importer_skip_existing', !empty($data['skip_existing']));
        update_option('tmdb_importer_overwrite', !empty($data['overwrite']));
        update_option('tmdb_importer_import_seasons', !empty($data['import_seasons']));
        update_option('tmdb_importer_import_episodes', !empty($data['import_episodes']));
        return true;
    }
}