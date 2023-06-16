<?php
/**
 * URL Alias Assets.
 *
 * @package TK\UrlAlias
 */

declare( strict_types = 1 );
namespace TK\UrlAlias;

defined( 'ABSPATH' ) || exit;

/**
 * Assets Class.
 */
class Assets {
    private const ASSET_HANDLE     = 'tk-url-alias';
    private const LOCALIZED_OBJECT = 'tkUrlAlias';

    /**
     * Assets constructor.
     */
    public function __construct() {
        add_action( 'enqueue_block_editor_assets', [ $this, 'enqueue_block_editor_assets' ], 1 );
    }

    /**
     * Enqueue block editor assets.
     *
     * @return void
     */
    public function enqueue_block_editor_assets(): void {
        $script     = require TK_URL_ALIAS_ASSETS_DIR . 'index.asset.php'; // phpcs:ignore WordPressVIPMinimum.Files.IncludingFile.UsingVariable
        $dependency = $script['dependencies'] ?? [];
        $version    = $script['version'] ?? filemtime( $script );

        wp_register_script( self::ASSET_HANDLE, TK_URL_ALIAS_ASSETS_URI . 'index.js', $dependency, $version, true );
        wp_enqueue_script( self::ASSET_HANDLE );
        wp_localize_script(
            self::ASSET_HANDLE,
            self::LOCALIZED_OBJECT,
            [
                'postTypes' => Helper::get_post_types(),
            ]
        );
    }
}
