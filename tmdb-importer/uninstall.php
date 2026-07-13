<?php
/**
 * Uninstall script
 */

defined('ABSPATH') || exit;

if (!defined('WP_UNINSTALL_PLUGIN')) {
    die;
}

define('TMDB_IMPORTER_UNINSTALL', true);

global $wpdb;

// Drop custom tables
$tables = [
    $wpdb->prefix . 'tmdb_seasons',
    $wpdb->prefix . 'tmdb_episodes',
    $wpdb->prefix . 'tmdb_person_relationships',
    $wpdb->prefix . 'tmdb_links',
    $wpdb->prefix . 'tmdb_import_jobs',
    $wpdb->prefix . 'tmdb_sync_jobs',
    $wpdb->prefix . 'tmdb_logs',
];

foreach ($tables as $table) {
    $wpdb->query("DROP TABLE IF EXISTS {$table}");
}

// Delete options
$options = [
    'tmdb_importer_api',
    'tmdb_importer_api_key',
    'tmdb_importer_language',
    'tmdb_importer_region',
    'tmdb_importer_skip_existing',
    'tmdb_importer_overwrite',
    'tmdb_importer_import_seasons',
    'tmdb_importer_import_episodes',
    'tmdb_importer_download_images',
    'tmdb_importer_image_size',
    'tmdb_importer_replace_images',
    'tmdb_importer_download_trailers',
    'tmdb_importer_log_level',
    'tmdb_importer_db_version',
];

foreach ($options as $option) {
    delete_option($option);
}

// Delete all tmdb_movie and tmdb_tv_show posts
$posts = get_posts([
    'post_type' => ['tmdb_movie', 'tmdb_tv_show'],
    'posts_per_page' => -1,
    'post_status' => 'any',
    'fields' => 'ids',
]);

foreach ($posts as $postId) {
    wp_delete_post($postId, true);
}

// Delete custom taxonomies
$taxonomies = [
    'tmdb_genre',
    'tmdb_release_year',
    'tmdb_keyword',
    'tmdb_person',
    'tmdb_network',
    'tmdb_production_company',
];

foreach ($taxonomies as $taxonomy) {
    if (taxonomy_exists($taxonomy)) {
        $terms = get_terms(['taxonomy' => $taxonomy, 'hide_empty' => false]);
        foreach ($terms as $term) {
            wp_delete_term($term->term_id, $taxonomy);
        }
        unregister_taxonomy($taxonomy);
    }
}

// Unregister post types
unregister_post_type('tmdb_movie');
unregister_post_type('tmdb_tv_show');

flush_rewrite_rules();