<?php
/**
 * Settings Page
 */

if (!defined('ABSPATH')) {
    exit;
}

$services = $this->getServices();
$settingsManager = new \TMDBImporter\Admin\Settings\SettingsManager();

$message = '';
$error = '';

if (isset($_POST['tmdb_importer_save_settings']) && check_admin_referer('tmdb_importer_settings')) {
    $data = $_POST['tmdb_importer_api'] ?? [];
    $data['import'] = $_POST['tmdb_importer_import'] ?? [];
    $data['images'] = $_POST['tmdb_importer_images'] ?? [];
    $data['videos'] = $_POST['tmdb_importer_videos'] ?? [];

    if ($settingsManager->save($data)) {
        $message = 'Settings saved successfully.';
    } else {
        $error = 'Failed to save settings.';
    }
}
?>

<div class="wrap">
    <h1>TMDB Importer Settings</h1>

    <?php if ($message): ?>
        <div class="notice notice-success is-dismissible"><p><?php echo esc_html($message); ?></p></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="notice notice-error is-dismissible"><p><?php echo esc_html($error); ?></p></div>
    <?php endif; ?>

    <form method="post" action="">
        <?php wp_nonce_field('tmdb_importer_settings'); ?>
        <input type="hidden" name="tmdb_importer_save_settings" value="1" />

        <?php
        $sections = $settingsManager->getSections();
        foreach ($sections as $slug => $section):
            $section->registerFields();
        ?>
        <h2><?php echo $slug === 'api' ? 'API Settings' : ucfirst($slug) . ' Settings'; ?></h2>
        <table class="form-table">
            <?php
            // Settings fields are registered via add_settings_field, just output the table structure
            // The actual fields are rendered by the callbacks
            ?>
        </table>
        <?php
        endforeach;
        ?>

        <?php submit_button('Save All Settings', 'primary', 'submit_settings', false); ?>
    </form>

    <h2>Maintenance Actions</h2>
    <div class="tmdb-importer-maintenance">
        <div class="maintenance-action">
            <button type="button" class="button button-secondary" id="tmdb-importer-clear-cache">
                Clear Cache
            </button>
            <p class="description">Clear all cached TMDB API responses.</p>
        </div>
        <div class="maintenance-action">
            <button type="button" class="button button-secondary" id="tmdb-importer-remove-orphaned">
                Remove Orphaned Content
            </button>
            <p class="description">Remove seasons, episodes, and relationships that no longer have a parent TV show.</p>
        </div>
        <div class="maintenance-action">
            <button type="button" class="button button-secondary" id="tmdb-importer-reset-settings">
                Reset All Settings
            </button>
            <p class="description">Reset all plugin settings to defaults (requires confirmation).</p>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('tmdb-importer-clear-cache').addEventListener('click', function() {
        if (confirm('Clear all cached data?')) {
            runMaintenance('clear_cache', this);
        }
    });

    document.getElementById('tmdb-importer-remove-orphaned').addEventListener('click', function() {
        if (confirm('Remove orphaned content? This cannot be undone.')) {
            runMaintenance('remove_orphaned', this);
        }
    });

    document.getElementById('tmdb-importer-reset-settings').addEventListener('click', function() {
        if (confirm('Reset ALL settings to defaults? This cannot be undone.')) {
            if (confirm('Are you absolutely sure?')) {
                runMaintenance('reset_settings', this);
            }
        }
    });

    function runMaintenance(action, button) {
        button.disabled = true;
        button.textContent = 'Working...';

        fetch(ajaxurl + '?action=tmdb_importer_ajax_maintenance&maintenance_action=' + action + '&_wpnonce=' + tmdbImporterNonce)
            .then(r => r.json())
            .then(data => {
                button.disabled = false;
                button.textContent = action === 'clear_cache' ? 'Clear Cache' : (action === 'remove_orphaned' ? 'Remove Orphaned Content' : 'Reset All Settings');
                if (data.success) {
                    alert('Success: ' + data.message);
                    if (action === 'reset_settings') location.reload();
                } else {
                    alert('Error: ' + data.error);
                }
            });
    }
});
</script>