<?php
/**
 * Sync Page
 */

if (!defined('ABSPATH')) {
    exit;
}

$services = $this->getServices();
$syncService = $services['sync'] ?? null;

$message = '';
$error = '';

if (isset($_POST['tmdb_importer_sync_action']) && check_admin_referer('tmdb_importer_sync')) {
    $postId = intval($_POST['sync_post_id'] ?? 0);
    $options = [
        'metadata' => isset($_POST['sync_metadata']),
        'ratings' => isset($_POST['sync_ratings']),
        'images' => isset($_POST['sync_images']),
        'videos' => isset($_POST['sync_videos']),
        'taxonomies' => isset($_POST['sync_taxonomies']),
        'seasons' => isset($_POST['sync_seasons']),
    ];

    if ($postId > 0 && $syncService) {
        try {
            $post = get_post($postId);
            if ($post) {
                if ($post->post_type === 'tmdb_movie') {
                    $results = $syncService->syncMovie($postId, $options);
                    $message = sprintf('Movie "%s" synced: %s', $post->post_title, implode(', ', array_filter($results, fn($v) => $v, ARRAY_FILTER_USE_KEY)));
                } elseif ($post->post_type === 'tmdb_tv_show') {
                    $results = $syncService->syncTVShow($postId, $options);
                    $message = sprintf('TV Show "%s" synced: %s', $post->post_title, implode(', ', array_filter($results, fn($v) => $v, ARRAY_FILTER_USE_KEY)));
                }
            } else {
                $error = 'Post not found';
            }
        } catch (Throwable $e) {
            $error = $e->getMessage();
        }
    } else {
        $error = 'Invalid post ID';
    }
}

if (isset($_POST['tmdb_importer_bulk_sync']) && check_admin_referer('tmdb_importer_bulk_sync')) {
    $postIds = array_map('intval', array_filter(explode(',', $_POST['sync_post_ids'] ?? '')));
    $options = [
        'metadata' => isset($_POST['sync_metadata']),
        'ratings' => isset($_POST['sync_ratings']),
        'images' => isset($_POST['sync_images']),
        'videos' => isset($_POST['sync_videos']),
        'taxonomies' => isset($_POST['sync_taxonomies']),
        'seasons' => isset($_POST['sync_seasons']),
    ];

    if (!empty($postIds) && $syncService) {
        try {
            $synced = 0;
            foreach ($postIds as $postId) {
                $post = get_post($postId);
                if (!$post) continue;

                if ($post->post_type === 'tmdb_movie') {
                    $syncService->syncMovie($postId, $options);
                    $synced++;
                } elseif ($post->post_type === 'tmdb_tv_show') {
                    $syncService->syncTVShow($postId, $options);
                    $synced++;
                }
            }
            $message = sprintf('%d items synced successfully', $synced);
        } catch (Throwable $e) {
            $error = $e->getMessage();
        }
    } else {
        $error = 'No post IDs provided';
    }
}

if (isset($_POST['tmdb_importer_sync_ratings']) && check_admin_referer('tmdb_importer_sync_ratings')) {
    $postIds = array_map('intval', array_filter(explode(',', $_POST['ratings_post_ids'] ?? '')));

    if (!empty($postIds) && $syncService) {
        try {
            $results = $syncService->syncRatings($postIds);
            $message = sprintf('Ratings synced: %d updated, %d failed', $results['updated'], $results['failed']);
        } catch (Throwable $e) {
            $error = $e->getMessage();
        }
    } else {
        $error = 'No post IDs provided';
    }
}
?>

<div class="wrap">
    <h1>Sync with TMDB</h1>

    <?php if ($message): ?>
        <div class="notice notice-success is-dismissible"><p><?php echo esc_html($message); ?></p></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="notice notice-error is-dismissible"><p><?php echo esc_html($error); ?></p></div>
    <?php endif; ?>

    <div class="tmdb-importer-sync-tabs">
        <nav class="nav-tab-wrapper">
            <a href="#single-sync" class="nav-tab nav-tab-active">Single Sync</a>
            <a href="#bulk-sync" class="nav-tab">Bulk Sync</a>
            <a href="#ratings-sync" class="nav-tab">Ratings Sync</a>
        </nav>
    </div>

    <div id="single-sync" class="tab-content">
        <form method="post" action="">
            <?php wp_nonce_field('tmdb_importer_sync'); ?>
            <input type="hidden" name="tmdb_importer_sync_action" value="1" />

            <table class="form-table">
                <tr>
                    <th scope="row"><label for="sync_post_id">Post ID</label></th>
                    <td>
                        <input type="number" name="sync_post_id" id="sync_post_id" min="1" required class="regular-text" />
                        <p class="description">Enter the WordPress Post ID of the movie or TV show to sync</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Sync Options</th>
                    <td>
                        <label><input type="checkbox" name="sync_metadata" value="1" checked /> Metadata (overview, dates, etc.)</label><br>
                        <label><input type="checkbox" name="sync_ratings" value="1" checked /> Ratings</label><br>
                        <label><input type="checkbox" name="sync_images" value="1" /> Images</label><br>
                        <label><input type="checkbox" name="sync_videos" value="1" /> Videos/Trailers</label><br>
                        <label><input type="checkbox" name="sync_taxonomies" value="1" checked /> Taxonomies (genres, keywords, people, etc.)</label><br>
                        <label class="tv-show-sync-option" style="display: none;"><input type="checkbox" name="sync_seasons" value="1" checked /> Seasons & Episodes</label>
                    </td>
                </tr>
            </table>
            <?php submit_button('Sync Now', 'primary', 'submit_sync', false); ?>
        </form>
    </div>

    <div id="bulk-sync" class="tab-content" style="display: none;">
        <form method="post" action="">
            <?php wp_nonce_field('tmdb_importer_bulk_sync'); ?>
            <input type="hidden" name="tmdb_importer_bulk_sync" value="1" />

            <table class="form-table">
                <tr>
                    <th scope="row"><label for="sync_post_ids">Post IDs (comma-separated)</label></th>
                    <td>
                        <textarea name="sync_post_ids" id="sync_post_ids" rows="5" class="large-text" placeholder="123, 456, 789"></textarea>
                        <p class="description">Enter WordPress Post IDs separated by commas</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Sync Options</th>
                    <td>
                        <label><input type="checkbox" name="sync_metadata" value="1" checked /> Metadata</label><br>
                        <label><input type="checkbox" name="sync_ratings" value="1" checked /> Ratings</label><br>
                        <label><input type="checkbox" name="sync_images" value="1" /> Images</label><br>
                        <label><input type="checkbox" name="sync_videos" value="1" /> Videos</label><br>
                        <label><input type="checkbox" name="sync_taxonomies" value="1" checked /> Taxonomies</label><br>
                        <label class="tv-show-sync-option" style="display: inline-block;"><input type="checkbox" name="sync_seasons" value="1" checked /> Seasons & Episodes</label>
                    </td>
                </tr>
            </table>
            <?php submit_button('Bulk Sync', 'primary', 'submit_bulk_sync', false); ?>
        </form>
    </div>

    <div id="ratings-sync" class="tab-content" style="display: none;">
        <form method="post" action="">
            <?php wp_nonce_field('tmdb_importer_sync_ratings'); ?>
            <input type="hidden" name="tmdb_importer_sync_ratings" value="1" />

            <table class="form-table">
                <tr>
                    <th scope="row"><label for="ratings_post_ids">Post IDs (comma-separated)</label></th>
                    <td>
                        <textarea name="ratings_post_ids" id="ratings_post_ids" rows="5" class="large-text" placeholder="123, 456, 789"></textarea>
                        <p class="description">Enter WordPress Post IDs (movies or TV shows) separated by commas. Only ratings will be updated.</p>
                    </td>
                </tr>
            </table>
            <?php submit_button('Sync Ratings', 'primary', 'submit_ratings_sync', false); ?>
        </form>
    </div>

    <h2>Content Browser</h2>
    <form method="get" action="" class="tmdb-content-browser">
        <input type="hidden" name="page" value="tmdb-importer-sync" />
        <select name="content_type">
            <option value="tmdb_movie" <?php selected(($_GET['content_type'] ?? ''), 'tmdb_movie'); ?>>Movies</option>
            <option value="tmdb_tv_show" <?php selected(($_GET['content_type'] ?? ''), 'tmdb_tv_show'); ?>>TV Shows</option>
        </select>
        <select name="taxonomy_filter">
            <option value="">All Genres</option>
            <?php
            $genres = get_terms(['taxonomy' => 'tmdb_genre', 'hide_empty' => false]);
            foreach ($genres as $genre) {
                echo '<option value="' . esc_attr($genre->term_id) . '" ' . selected(($_GET['taxonomy_filter'] ?? ''), $genre->term_id, false) . '>' . esc_html($genre->name) . '</option>';
            }
            ?>
        </select>
        <input type="number" name="year" placeholder="Year" value="<?php echo esc_attr($_GET['year'] ?? ''); ?>" style="width: 80px;" />
        <?php submit_button('Filter', 'secondary', 'filter_content', false); ?>
    </form>

    <?php
    $queryArgs = [
        'post_type' => $_GET['content_type'] ?? 'tmdb_movie',
        'posts_per_page' => 20,
        'post_status' => 'publish',
    ];

    if (!empty($_GET['taxonomy_filter'])) {
        $queryArgs['tax_query'] = [[
            'taxonomy' => 'tmdb_genre',
            'field' => 'term_id',
            'terms' => intval($_GET['taxonomy_filter']),
        ]];
    }

    if (!empty($_GET['year'])) {
        $queryArgs['tax_query'][] = [
            'taxonomy' => 'tmdb_release_year',
            'field' => 'slug',
            'terms' => sanitize_text_field($_GET['year']),
        ];
    }

    $content = new WP_Query($queryArgs);
    ?>
    <?php if ($content->have_posts()): ?>
        <table class="widefat striped">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Type</th>
                    <th>TMDB ID</th>
                    <th>Rating</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($content->have_posts()): $content->the_post(); ?>
                    <?php
                    $tmdbId = get_post_meta(get_the_ID(), '_tmdb_id', true);
                    $rating = get_post_meta(get_the_ID(), '_tmdb_rating', true);
                    $syncStatus = get_post_meta(get_the_ID(), '_tmdb_last_sync', true);
                    ?>
                    <tr>
                        <td><strong><?php the_title(); ?></strong></td>
                        <td><?php echo get_post_type() === 'tmdb_movie' ? 'Movie' : 'TV Show'; ?></td>
                        <td><?php echo esc_html($tmdbId); ?></td>
                        <td><?php echo esc_html($rating); ?></td>
                        <td>
                            <?php if ($syncStatus): ?>
                                <span class="dashicons dashicons-yes-alt" style="color: green;" title="Last synced: <?php echo esc_attr($syncStatus); ?>"></span>
                            <?php else: ?>
                                <span class="dashicons dashicons-clock" style="color: #999;" title="Never synced"></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <button type="button" class="button button-small sync-single" data-post-id="<?php echo get_the_ID(); ?>" data-post-type="<?php echo get_post_type(); ?>">Sync</button>
                            <button type="button" class="button button-small sync-ratings-single" data-post-id="<?php echo get_the_ID(); ?>">Sync Ratings</button>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>No content found matching your criteria.</p>
    <?php endif; ?>
    <?php wp_reset_postdata(); ?>
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

    const postIdInput = document.getElementById('sync_post_id');
    if (postIdInput) {
        postIdInput.addEventListener('blur', function() {
            const postId = this.value;
            if (postId) {
                fetch(ajaxurl + '?action=tmdb_importer_get_post_type&post_id=' + postId)
                    .then(r => r.json())
                    .then(data => {
                        if (data.post_type) {
                            document.querySelectorAll('.tv-show-sync-option').forEach(el => {
                                el.style.display = data.post_type === 'tmdb_tv_show' ? 'inline-block' : 'none';
                            });
                        }
                    });
            }
        });
    }

    document.querySelectorAll('.sync-single').forEach(btn => {
        btn.addEventListener('click', function() {
            const postId = this.dataset.postId;
            const postType = this.dataset.postType;
            const btn = this;
            btn.disabled = true;
            btn.textContent = 'Syncing...';

            fetch(ajaxurl + '?action=tmdb_importer_ajax_sync&post_id=' + postId + '&post_type=' + postType + '&_wpnonce=' + tmdbImporterNonce)
                .then(r => r.json())
                .then(data => {
                    btn.disabled = false;
                    btn.textContent = 'Sync';
                    if (data.success) {
                        alert('Synced successfully!');
                        location.reload();
                    } else {
                        alert('Error: ' + data.error);
                    }
                });
        });
    });

    document.querySelectorAll('.sync-ratings-single').forEach(btn => {
        btn.addEventListener('click', function() {
            const postId = this.dataset.postId;
            const btn = this;
            btn.disabled = true;
            btn.textContent = 'Syncing...';

            fetch(ajaxurl + '?action=tmdb_importer_ajax_sync_ratings&post_ids=' + postId + '&_wpnonce=' + tmdbImporterNonce)
                .then(r => r.json())
                .then(data => {
                    btn.disabled = false;
                    btn.textContent = 'Sync Ratings';
                    if (data.success) {
                        alert('Ratings synced!');
                        location.reload();
                    } else {
                        alert('Error: ' + data.error);
                    }
                });
        });
    });
});
</script>