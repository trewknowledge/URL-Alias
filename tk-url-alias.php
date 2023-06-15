<?php
/**
 * TK URL Alias
 *
 * @package   TK\UrlAlias
 * @author    Trew Knowledge Inc.
 * @license   GPL v2 or later <http://www.gnu.org/licenses/gpl-2.0.txt>
 *
 * @wordpress-plugin
 * Plugin Name:       TK URL Alias
 * Plugin URI:        https://trewknowledge.com/
 * Description:       Specify an alternative URL for your posts and pages.
 * Version:           1.0.0
 * Requires at least: 5.2
 * Requires PHP:      7.4
 * Author:            Trew Knowledge Inc.
 * Author URI:        https://trewknowledge.com/
 * Text Domain:       tk-url-alias
 * Domain Path:       /languages
 * License:           GPL v2 or later
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 */

declare( strict_types = 1 );
namespace TK\UrlAlias;

defined( 'ABSPATH' ) || exit;
defined( 'TK_URL_ALIAS_ASSETS_DIR' ) || define( 'TK_URL_ALIAS_ASSETS_DIR', __DIR__ . '/build/' );
defined( 'TK_URL_ALIAS_ASSETS_URI' ) || define( 'TK_URL_ALIAS_ASSETS_URI', plugin_dir_url( __FILE__ ) . 'build/' );
defined( 'TK_URL_ALIAS_FILE' ) || define( 'TK_URL_ALIAS_FILE', __FILE__ );

require_once plugin_dir_path( __FILE__ ) . 'vendor/autoload.php';

if ( class_exists( UrlAlias::class ) ) {
    /**
     * Kick off the plugin.
     */
    UrlAlias::init();
}
