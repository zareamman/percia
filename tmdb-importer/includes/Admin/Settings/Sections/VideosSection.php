<?php
namespace TMDBImporter\Admin\Settings\Sections;

class VideosSection
{
    public function registerFields(): void
    {
        add_settings_section(
            'tmdb_importer_videos_section',
            'Video Settings',
            [$this, 'renderDescription'],
            'tmdb_importer_settings'
        );

        add_settings_field(
            'tmdb_importer_download_trailers',
            'Download Trailers to Media Library',
            [$this, 'renderDownloadTrailers'],
            'tmdb_importer_settings',
            'tmdb_importer_videos_section'
        );
    }

    public function renderDescription(): void
    {
        echo '<p>Configure how videos/trailers are handled.</p>';
    }

    public function renderDownloadTrailers(): void
    {
        $value = get_option('tmdb_importer_download_trailers', false);
        echo '<input type="checkbox" name="tmdb_importer_videos[download_trailers]" value="1" ' . checked($value, true, false) . ' />';
        echo '<p class="description">When enabled, trailers will be downloaded to the WordPress Media Library (requires YouTube download support).</p>';
    }

    public function save(array $data): bool
    {
        update_option('tmdb_importer_download_trailers', !empty($data['download_trailers']));
        return true;
    }
}