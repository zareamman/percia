<?php
namespace TMDBImporter\Admin\Settings\Sections;

class APISection
{
    public function registerFields(): void
    {
        add_settings_section(
            'tmdb_importer_api_section',
            'API Settings',
            [$this, 'renderDescription'],
            'tmdb_importer_settings'
        );

        add_settings_field(
            'tmdb_importer_api_key',
            'TMDB API Key',
            [$this, 'renderApiKey'],
            'tmdb_importer_settings',
            'tmdb_importer_api_section'
        );

        add_settings_field(
            'tmdb_importer_language',
            'Language',
            [$this, 'renderLanguage'],
            'tmdb_importer_settings',
            'tmdb_importer_api_section'
        );

        add_settings_field(
            'tmdb_importer_region',
            'Region',
            [$this, 'renderRegion'],
            'tmdb_importer_settings',
            'tmdb_importer_api_section'
        );
    }

    public function renderDescription(): void
    {
        echo '<p>Configure your TMDB API credentials.</p>';
    }

    public function renderApiKey(): void
    {
        $options = get_option('tmdb_importer_api', []);
        $value = $options['api_key'] ?? '';
        echo '<input type="password" name="tmdb_importer_api[api_key]" value="' . esc_attr($value) . '" class="regular-text" />';
        echo '<p class="description">Get your API key from <a href="https://www.themoviedb.org/settings/api" target="_blank">TMDB</a>.</p>';
    }

    public function renderLanguage(): void
    {
        $options = get_option('tmdb_importer_api', []);
        $value = $options['language'] ?? 'en-US';
        $languages = [
            'en-US' => 'English (US)',
            'en-GB' => 'English (UK)',
            'es-ES' => 'Spanish',
            'fr-FR' => 'French',
            'de-DE' => 'German',
            'it-IT' => 'Italian',
            'ja-JP' => 'Japanese',
            'ko-KR' => 'Korean',
            'zh-CN' => 'Chinese (Simplified)',
        ];
        echo '<select name="tmdb_importer_api[language]">';
        foreach ($languages as $code => $name) {
            echo '<option value="' . esc_attr($code) . '" ' . selected($value, $code, false) . '>' . esc_html($name) . '</option>';
        }
        echo '</select>';
    }

    public function renderRegion(): void
    {
        $options = get_option('tmdb_importer_api', []);
        $value = $options['region'] ?? 'US';
        $regions = [
            'US' => 'United States',
            'GB' => 'United Kingdom',
            'CA' => 'Canada',
            'AU' => 'Australia',
            'DE' => 'Germany',
            'FR' => 'France',
            'ES' => 'Spain',
            'IT' => 'Italy',
            'JP' => 'Japan',
            'KR' => 'South Korea',
        ];
        echo '<select name="tmdb_importer_api[region]">';
        foreach ($regions as $code => $name) {
            echo '<option value="' . esc_attr($code) . '" ' . selected($value, $code, false) . '>' . esc_html($name) . '</option>';
        }
        echo '</select>';
    }

    public function save(array $data): bool
    {
        $current = get_option('tmdb_importer_api', []);
        $current['api_key'] = sanitize_text_field($data['api_key'] ?? '');
        $current['language'] = sanitize_text_field($data['language'] ?? 'en-US');
        $current['region'] = sanitize_text_field($data['region'] ?? 'US');
        update_option('tmdb_importer_api', $current);
        return true;
    }
}