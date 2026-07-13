<?php
/**
 * Plugin Name: TMDB Importer
 * Plugin URI: https://github.com/tmdb-importer/tmdb-importer
 * Description: Import and synchronize Movies, TV Shows, Seasons, Episodes, metadata, taxonomies, images and videos from TMDB.
 * Version: 1.0.0
 * Author: TMDB Importer Team
 * Author URI: https://github.com/tmdb-importer/tmdb-importer
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: tmdb-importer
 * Domain Path: /languages
 */

defined('ABSPATH') || exit;

define('TMDB_IMPORTER_VERSION', '1.0.0');
define('TMDB_IMPORTER_PATH', plugin_dir_path(__FILE__));
define('TMDB_IMPORTER_URL', plugin_dir_url(__FILE__));

require_once TMDB_IMPORTER_PATH . 'includes/Autoloader.php';
\TMDBImporter\Core\Autoloader::register();

\TMDBImporter\Core\Bootstrap::init();

register_activation_hook(__FILE__, ['\\TMDBImporter\\Core\\Bootstrap', 'activate']);
register_deactivation_hook(__FILE__, ['\\TMDBImporter\\Core\\Bootstrap', 'deactivate']);
register_uninstall_hook(__FILE__, ['\\TMDBImporter\\Core\\Bootstrap', 'uninstall']);