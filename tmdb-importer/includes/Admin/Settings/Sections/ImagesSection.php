<?php
namespace TMDBImporter\Admin\Settings\Sections;

class ImagesSection
{
    public function registerFields(): void
    {
        add_settings_section(
            'tmdb_importer_images_section',
            'Image Settings',
            [$this, 'renderDescription'],
            'tmdb_importer_settings'
        );

        add_settings_field(
            'tmdb_importer_download_images',
            'Download Images to Media Library',
            [$this, 'renderDownloadImages'],
            'tmdb_importer_settings',
            'tmdb_importer_images_section'
        );

        add_settings_field(
            'tmdb_importer_image_size',
            'Image Size',
            [$this, 'renderImageSize'],
            'tmdb_importer_settings',
            'tmdb_importer_images_section'
        );

        add_settings_field(
            'tmdb_importer_replace_images',
            'Replace Existing Images',
            [$this, 'renderReplaceImages'],
            'tmdb_importer_settings',
            'tmdb_importer_images_section'
        );
    }

    public function renderDescription(): void
    {
        echo '<p>Configure how images are handled.</p>';
    }

    public function renderDownloadImages(): void
    {
        $value = get_option('tmdb_importer_download_images', false);
        echo '<input type="checkbox" name="tmdb_importer_images[download_images]" value="1" ' . checked($value, true, false) . ' />';
        echo '<p class="description">When enabled, images will be downloaded to the WordPress Media Library.</p>';
    }

    public function renderImageSize(): void
    {
        $value = get_option('tmdb_importer_image_size', 'w500');
        $sizes = [
            'w185' => 'w185 (Thumbnail)',
            'w342' => 'w342 (Small)',
            'w500' => 'w500 (Medium)',
            'w780' => 'w780 (Large)',
            'w1280' => 'w1280 (Extra Large)',
            'original' => 'Original',
        ];
        echo '<select name="tmdb_importer_images[image_size]">';
        foreach ($sizes as $code => $label) {
            echo '<option value="' . esc_attr($code) . '" ' . selected($value, $code, false) . '>' . esc_html($label) . '</option>';
        }
        echo '</select>';
    }

    public function renderReplaceImages(): void
    {
        $value = get_option('tmdb_importer_replace_images', false);
        echo '<input type="checkbox" name="tmdb_importer_images[replace_images]" value="1" ' . checked($value, true, false) . ' />';
    }

    public function save(array $data): bool
    {
        update_option('tmdb_importer_download_images', !empty($data['download_images']));
        update_option('tmdb_importer_image_size', sanitize_text_field($data['image_size'] ?? 'w500'));
        update_option('tmdb_importer_replace_images', !empty($data['replace_images']));
        return true;
    }
}