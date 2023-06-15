<?php
/**
 * URL Alias Form.
 *
 * @package TK\UrlAlias
 */

declare( strict_types = 1 );
namespace TK\UrlAlias;

defined( 'ABSPATH' ) || exit;

/**
 * URL Alias Form.
 */
class Form {
    private const ASSET_HANDLE     = 'tk-url-alias';
    private const LOCALIZED_OBJECT = 'tkUrlAlias';

    /**
     * Form constructor.
     */
	public function __construct() {
        add_action( 'init', [ $this, 'register_post_meta' ] );
        add_filter( 'is_protected_meta', [ $this, 'protect_meta' ], 10, 2 );
		add_action( 'delete_post', [ $this, 'delete_permalink' ] );
        add_action( 'save_post', [ $this, 'delete_cache' ] );
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

	/**
	 * Set the meta_keys to protected which is created by the plugin.
	 *
	 * @param   bool   $protected Whether the key is protected or not.
	 * @param   string $meta_key  Meta key.
	 *
	 * @return bool `true` for the url_alias key.
	 */
	public function protect_meta( bool $protected, string $meta_key ): bool {
		if ( Helper::URL_ALIAS === $meta_key ) {
			$protected = true;
		}

		return $protected;
	}

	/**
	 * Sanitize given string to make it standard URL. It's a copy of default
	 * `sanitize_title_with_dashes` function with few changes.
	 *
	 * @param   string $permalink     String that needs to be sanitized.
	 * @param   string $language_code Language code.
	 *
	 * @return string
	 */
	private function sanitize_permalink( string $permalink, string $language_code = '' ): string {
		// Remove front and trailing slashes.
		$permalink = ltrim( untrailingslashit( $permalink ), '/\\' );

		/*
		 * Add Capability to allow Accents letter (if required). By default, It is
		 * disabled.
		 */
		$check_accents_filter = apply_filters( 'tk_url_alias_allow_accents', false );

		/*
		 * Add Capability to allow Capital letter (if required). By default, It is
		 * disabled.
		 */
		$check_caps_filter = apply_filters( 'tk_url_alias_allow_caps', false );

		$allow_accents = false;
		$allow_caps    = false;

		if ( is_bool( $check_accents_filter ) && $check_accents_filter ) {
			$allow_accents = $check_accents_filter;
		}

		if ( is_bool( $check_caps_filter ) && $check_caps_filter ) {
			$allow_caps = $check_caps_filter;
		}

		if ( ! $allow_accents ) {
			$permalink = remove_accents( $permalink );
		}

		if ( empty( $language_code ) ) {
			$language_code = get_locale();
		}

		$permalink = wp_strip_all_tags( $permalink );
		// Preserve escaped octets.
		$permalink = preg_replace( '|%([a-fA-F0-9][a-fA-F0-9])|', '---$1---', $permalink );
		// Remove percent signs that are not part of an octet.
		$permalink = str_replace( '%', '', $permalink );
		// Restore octets.
		$permalink = preg_replace( '|---([a-fA-F0-9][a-fA-F0-9])---|', '%$1', $permalink );

		if ( 'en' === $language_code || strpos( $language_code, 'en_' ) === 0 ) {
			if ( seems_utf8( $permalink ) ) {
				if ( ! $allow_accents ) {
					if ( function_exists( 'mb_strtolower' ) ) {
						if ( ! $allow_caps ) {
							$permalink = mb_strtolower( $permalink, 'UTF-8' );
						}
					}
					$permalink = utf8_uri_encode( $permalink );
				}
			}
		}

		if ( ! $allow_caps ) {
			$permalink = strtolower( $permalink );
		}

		// Convert &nbsp, &ndash, and &mdash to hyphens.
		$permalink = str_replace( [ '%c2%a0', '%e2%80%93', '%e2%80%94' ], '-', $permalink );

		// Convert &nbsp, &ndash, and &mdash HTML entities to hyphens.
        $permalink = str_replace(
            [
                '&nbsp;',
                '&#160;',
                '&ndash;',
                '&#8211;',
                '&mdash;',
                '&#8212;',
            ],
            '-',
            $permalink
        );

		// Strip these characters entirely.
		$permalink = str_replace(
			[
				'%c2%ad',
				'%c2%a1',
				'%c2%bf',
				'%c2%ab',
				'%c2%bb',
				'%e2%80%b9',
				'%e2%80%ba',
				'%e2%80%98',
				'%e2%80%99',
				'%e2%80%9c',
				'%e2%80%9d',
				'%e2%80%9a',
				'%e2%80%9b',
				'%e2%80%9e',
				'%e2%80%9f',
				'%e2%80%a2',
				'%c2%a9',
				'%c2%ae',
				'%c2%b0',
				'%e2%80%a6',
				'%e2%84%a2',
				'%c2%b4',
				'%cb%8a',
				'%cc%81',
				'%cd%81',
				'%cc%80',
				'%cc%84',
				'%cc%8c',
			],
			'',
			$permalink
		);

		// Convert &times to 'x'.
		$permalink = str_replace( '%c3%97', 'x', $permalink );
		// Kill entities.
		$permalink = preg_replace( '/&.+?;/', '', $permalink );

		// Avoid removing characters of other languages like persian etc.
		if ( 'en' === $language_code || strpos( $language_code, 'en_' ) === 0 ) {
			// Allow Alphanumeric and few symbols only.
			if ( ! $allow_caps ) {
				$permalink = preg_replace( '/[^%a-z0-9 \.\/_-]/', '', $permalink );
			} else {
				// Allow Capital letters.
				$permalink = preg_replace( '/[^%a-zA-Z0-9 \.\/_-]/', '', $permalink );
			}
		} else {
			$reserved_chars = [
				'(',
				')',
				'[',
				']',
			];
			$unsafe_chars   = [
				'<',
				'>',
				'{',
				'}',
				'|',
				'`',
				'^',
				'\\',
			];

			$permalink = str_replace( $reserved_chars, '', $permalink );
			$permalink = str_replace( $unsafe_chars, '', $permalink );
			// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.urlencode_urlencode
			$permalink = urlencode( $permalink );
			// Replace encoded slash input with slash.
			$permalink = str_replace( '%2F', '/', $permalink );

			$replace_hyphen = [ '%20', '%2B', '+' ];
			$split_path     = explode( '%3F', $permalink );
			if ( 1 < count( $split_path ) ) {
				// Replace encoded space and plus input with hyphen.
				$replaced_path = str_replace( $replace_hyphen, '-', $split_path[0] );
				$replaced_path = preg_replace( '/(\-+)/', '-', $replaced_path );
				$permalink     = str_replace(
					$split_path[0],
					$replaced_path,
					$permalink
				);
			} else {
				// Replace encoded space and plus input with hyphen.
				$permalink = str_replace( $replace_hyphen, '-', $permalink );
				$permalink = preg_replace( '/(\-+)/', '-', $permalink );
			}
		}

		$permalink = preg_replace( '/\s+/', '-', $permalink );
		$permalink = preg_replace( '|-+|', '-', $permalink );
		$permalink = str_replace( '-/', '/', $permalink );
		$permalink = str_replace( '/-', '/', $permalink );

		/*
		 * Avoid trimming hyphens if filter returns `false`.
		 */
		$trim_hyphen = apply_filters( 'tk_url_alias_redundant_hyphens', false );

		if ( ! is_bool( $trim_hyphen ) || ! $trim_hyphen ) {
			$permalink = trim( $permalink, '-' );
		}

		return $permalink;
	}

	/**
	 * Delete Post Permalink.
	 *
	 * @access public
	 *
	 * @param   int $post_id  Post ID.
	 *
	 * @return void
	 */
	public function delete_permalink( int $post_id ): void {
		delete_metadata( 'post', $post_id, Helper::URL_ALIAS );
	}

    /**
     * Delete cached url_alias.
     *
     * @param   int $post_id Post ID.
     *
     * @return void
     */
    public function delete_cache( int $post_id ): void {
        $url_alias = get_post_meta( $post_id, Helper::URL_ALIAS, true );
        $url_alias = ltrim( untrailingslashit( $url_alias ), '/\\' );
        $url_alias = trim( $url_alias );

        if ( ! $url_alias ) {
            return;
        }

        wp_cache_delete( Helper::CACHE_KEY . $url_alias );
    }

	/**
	 * Register url_alias post meta.
	 *
	 * @return void
	 */
	public function register_post_meta(): void {
		foreach ( Helper::get_post_types() as $post_type ) {
			/**
			 * Add support for custom fields.
			 * Otherwise, custom meta-fields will not be displayed in the REST API.
			 */
			add_post_type_support( $post_type, 'custom-fields' );

			register_post_meta(
				$post_type,
				Helper::URL_ALIAS,
				[
					'show_in_rest'      => true,
					'single'            => true,
					'type'              => 'string',
					'sanitize_callback' => function ( $value ) {
						return $this->sanitize_permalink( $value );
					},
					'auth_callback'     => function () {
						return current_user_can( 'edit_posts' );
					},
				]
			);
		}
	}
}
