<?php
namespace TMDBImporter\Admin\Settings\Sections;

class MaintenanceSection
{
    public function registerFields(): void
    {
        add_settings_section(
            'tmdb_importer_maintenance_section',
            'Maintenance',
            [$this, 'renderDescription'],
            'tmdb_importer_settings'
        );

        add_settings_field(
            'tmdb_importer_clear_cache',
            'Clear Cache',
            [$this, 'renderClearCache'],
            'tmdb_importer_settings',
            'tmdb_importer_maintenance_section'
        );

        add_settings_field(
            'tmdb_importer_remove_orphaned',
            'Remove Orphaned Content',
            [$this, 'renderRemoveOrphaned'],
            'tmdb_importer_settings',
            'tmdb_importer_maintenance_section'
        );

        add_settings_field(
            'tmdb_importer_reset_settings',
            'Reset All Settings',
            [$this, 'renderResetSettings'],
            'tmdb_importer_settings',
            'tmdb_importer_maintenance_section'
        );
    }

    public function renderDescription(): void
    {
        echo '<p>Maintenance tools for the plugin.</p>';
    }

    public function renderClearCache(): void
    {
        echo '<button type="button" class="button button-secondary" id="tmdb-importer-clear-cache">Clear Cache</button>';
        echo '<p class="description">Clear all cached TMDB API responses.</p>';
    }

    public function renderRemoveOrphaned(): void
    {
        echo '<button type="button" class="button button-secondary" id="tmdb-importer-remove-orphaned">Remove Orphaned Content</button>';
        echo '<p class="description">Remove seasons, episodes, and relationships that no longer have a parent TV show.</p>';
    }

    public function renderResetSettings(): void
    {
        echo '<button type="button" class="button button-secondary" id="tmdb-importer-reset-settings">Reset Settings</button>';
        echo '<p class="description">Reset all plugin settings to defaults.</p>';
    }

    public function save(array $data): bool
    {
        return true;
    }
}