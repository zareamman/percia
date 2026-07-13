<?php
/**
 * Import Page
 */

if (!defined('ABSPATH')) {
    exit;
}

$services = $this->getServices();
$importService = $services['import'] ?? null;

$message = '';
$error = '';

if (isset($_POST['tmdb_importer_import_action']) && check_admin_referer('tmdb_importer_import')) {
    $type = sanitize_text_field($_POST['import_type'] ?? '');
    $tmdbId = intval($_POST['tmdb_id'] ?? 0);
    $skipExisting = isset($_POST['skip_existing']);
    $overwrite = isset($_POST['overwrite']);
    $importSeasons = isset($_POST['import_seasons']);

    if ($tmdbId > 0 && $importService) {
        try {
            if ($type === 'movie') {
                $movie = $importService->importMovie($tmdbId, compact('skipExisting', 'overwrite'));
                $message = sprintf('Movie "%s" imported successfully (Post ID: %d)', $movie->getTitle(), $movie->getId());
            } elseif ($type === 'tv_show') {
                $tvShow = $importService->importTVShow($tmdbId, compact('skipExisting', 'overwrite', 'importSeasons'));
                $message = sprintf('TV Show "%s" imported successfully (Post ID: %d)', $tvShow->getName(), $tvShow->getId());
            }
        } catch (Throwable $e) {
            $error = $e->getMessage();
        }
    } else {
        $error = 'Invalid TMDB ID';
    }
}

if (isset($_POST['tmdb_importer_bulk_import']) && check_admin_referer('tmdb_importer_bulk_import')) {
    $type = sanitize_text_field($_POST['bulk_type'] ?? '');
    $tmdbIds = array_map('intval', array_filter(explode(',', $_POST['bulk_tmdb_ids'] ?? '')));
    $skipExisting = isset($_POST['bulk_skip_existing']);

    if (!empty($tmdbIds) && $importService) {
        try {
            $job = new \TMDBImporter\Domain\ImportJob();
            $job->setType($type)
                ->setTmdbIds($tmdbIds)
                ->setImportOptions(['skip_existing' => $skipExisting])
                ->setStatus(\TMDBImporter\Domain\ImportJob::STATUS_PENDING)
                ->setScheduledAt(current_time('mysql', true));

            $importJobRepo = new \TMDBImporter\Repositories\Database\ImportJobRepository();
            $importJobRepo->save($job);

            $message = sprintf('Bulk import job created with %d items (Job ID: %d)', count($tmdbIds), $job->getId());
        } catch (Throwable $e) {
            $error = $e->getMessage();
        }
    } else {
        $error = 'No TMDB IDs provided';
    }
}
?>

<div class="wrap">
    <h1>Import from TMDB</h1>

    <?php if ($message): ?>
        <div class="notice notice-success is-dismissible"><p><?php echo esc_html($message); ?></p></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="notice notice-error is-dismissible"><p><?php echo esc_html($error); ?></p></div>
    <?php endif; ?>

    <div class="tmdb-importer-import-tabs">
        <nav class="nav-tab-wrapper">
            <a href="#single-import" class="nav-tab nav-tab-active">Single Import</a>
            <a href="#bulk-import" class="nav-tab">Bulk Import</a>
        </nav>
    </div>

    <div id="single-import" class="tab-content">
        <form method="post" action="">
            <?php wp_nonce_field('tmdb_importer_import'); ?>
            <input type="hidden" name="tmdb_importer_import_action" value="1" />

            <table class="form-table">
                <tr>
                    <th scope="row"><label for="import_type">Import Type</label></th>
                    <td>
                        <select name="import_type" id="import_type">
                            <option value="movie">Movie</option>
                            <option value="tv_show">TV Show</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="tmdb_id">TMDB ID</label></th>
                    <td>
                        <input type="number" name="tmdb_id" id="tmdb_id" min="1" required class="regular-text" />
                        <p class="description">Enter the TMDB ID (e.g., 550 for Fight Club)</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Options</th>
                    <td>
                        <label><input type="checkbox" name="skip_existing" value="1" checked /> Skip if already exists</label><br>
                        <label><input type="checkbox" name="overwrite" value="1" /> Overwrite existing content</label>
                    </td>
                </tr>
                <tr class="tv-show-options" style="display: none;">
                    <th scope="row"></th>
                    <td>
                        <label><input type="checkbox" name="import_seasons" value="1" checked /> Import all seasons and episodes</label>
                    </td>
                </tr>
            </table>
            <?php submit_button('Import', 'primary', 'submit_import', false); ?>
        </form>
    </div>

    <div id="bulk-import" class="tab-content" style="display: none;">
        <form method="post" action="">
            <?php wp_nonce_field('tmdb_importer_bulk_import'); ?>
            <input type="hidden" name="tmdb_importer_bulk_import" value="1" />

            <table class="form-table">
                <tr>
                    <th scope="row"><label for="bulk_type">Import Type</label></th>
                    <td>
                        <select name="bulk_type" id="bulk_type">
                            <option value="movie">Movies</option>
                            <option value="tv_show">TV Shows</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="bulk_tmdb_ids">TMDB IDs (comma-separated)</label></th>
                    <td>
                        <textarea name="bulk_tmdb_ids" id="bulk_tmdb_ids" rows="5" class="large-text" placeholder="550, 155, 680, 13"></textarea>
                        <p class="description">Enter multiple TMDB IDs separated by commas</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Options</th>
                    <td>
                        <label><input type="checkbox" name="bulk_skip_existing" value="1" checked /> Skip if already exists</label>
                    </td>
                </tr>
            </table>
            <?php submit_button('Create Bulk Import Job', 'primary', 'submit_bulk_import', false); ?>
        </form>
    </div>

    <h2>Search TMDB</h2>
    <form method="get" action="" class="tmdb-search-form">
        <input type="hidden" name="page" value="tmdb-importer-import" />
        <input type="text" name="tmdb_search" placeholder="Search for movies or TV shows..." class="regular-text" value="<?php echo esc_attr($_GET['tmdb_search'] ?? ''); ?>" />
        <select name="tmdb_search_type">
            <option value="movie" <?php selected(($_GET['tmdb_search_type'] ?? ''), 'movie'); ?>>Movies</option>
            <option value="tv" <?php selected(($_GET['tmdb_search_type'] ?? ''), 'tv'); ?>>TV Shows</option>
        </select>
        <?php submit_button('Search', 'secondary', 'tmdb_search_submit', false); ?>
    </form>

    <?php if (!empty($_GET['tmdb_search'])): ?>
        <?php
        $searchResults = [];
        try {
            $tmdbClient = $services['tmdb_client'] ?? null;
            if ($tmdbClient) {
                $type = $_GET['tmdb_search_type'] ?? 'movie';
                $searchResults = $type === 'movie'
                    ? $tmdbClient->searchMovie($_GET['tmdb_search'])
                    : $tmdbClient->searchTVShow($_GET['tmdb_search']);
            }
        } catch (Throwable $e) {
            $error = $e->getMessage();
        }
        ?>
        <?php if (!empty($searchResults['results'])): ?>
            <h3>Search Results</h3>
            <table class="widefat striped">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Release/First Air Date</th>
                        <th>TMDB ID</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($searchResults['results'] as $result): ?>
                        <tr>
                            <td><?php echo esc_html($result['title'] ?? $result['name'] ?? ''); ?></td>
                            <td><?php echo esc_html($result['release_date'] ?? $result['first_air_date'] ?? ''); ?></td>
                            <td><?php echo esc_html($result['id'] ?? ''); ?></td>
                            <td>
                                <button type="button" class="button button-small use-tmdb-id" data-id="<?php echo esc_attr($result['id']); ?>" data-type="<?php echo esc_attr($_GET['tmdb_search_type']); ?>">Use This ID</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No results found.</p>
        <?php endif; ?>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const tabs = document.querySelectorAll('.nav-tab');
    tabs.forEach(tab => {
        tab.addEventListener('click', function(e) {
            e.preventDefault();
            tabs.forEach(t => t.classList.remove('nav-tab-active'));
            this.classList.add('nav-tab-active');
            document.querySelectorAll('.tab-content').forEach(c => c.style.display = 'none');
            document.querySelector(this.getAttribute('href')).style.display = 'block';
        });
    });

    document.getElementById('import_type').addEventListener('change', function() {
        document.querySelector('.tv-show-options').style.display = this.value === 'tv_show' ? 'table-row' : 'none';
    });

    document.querySelectorAll('.use-tmdb-id').forEach(btn => {
        btn.addEventListener('click', function() {
            document.getElementById('tmdb_id').value = this.dataset.id;
            document.getElementById('import_type').value = this.dataset.type;
            document.querySelector('.tv-show-options').style.display = this.dataset.type === 'tv_show' ? 'table-row' : 'none';
            tabs.forEach(t => t.classList.remove('nav-tab-active'));
            document.querySelector('[href="#single-import"]').classList.add('nav-tab-active');
            document.querySelectorAll('.tab-content').forEach(c => c.style.display = 'none');
            document.querySelector('#single-import').style.display = 'block';
            window.scrollTo({top: 0, behavior: 'smooth'});
        });
    });
});
</script>