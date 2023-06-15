<?php
/**
 * URL Aliass setup.
 *
 * @package CustomPermalinks
 */

declare( strict_types = 1 );
namespace TK\UrlAlias;

defined( 'ABSPATH' ) || exit;

/**
 * Main URL Alias class.
 */
class UrlAlias {
	/**
	 * Class constructor.
	 */
	private function __construct() {
		add_action( 'plugins_loaded', [ $this, 'includes' ] );
	}

    /**
     * Include Services.
     *
     * @return void
     */
	public function includes(): void {
		new Form();
		new Handle();
	}

	/**
	 * Initialize the plugin.
	 *
	 * @return void
	 */
	public static function init(): void {
		new self();
	}
}
