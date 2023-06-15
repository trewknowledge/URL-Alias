<?php
/**
 * Handle URL Alias.
 *
 * @package  TK\UrlAlias\Handle
 */

declare( strict_types = 1 );
namespace TK\UrlAlias;

use WP_Post;

defined( 'ABSPATH' ) || exit;

/**
 * Handle URL Alias.
 */
class Handle {

    /**
     * Handle constructor.
     */
	public function __construct() {
		add_filter( 'do_parse_request', [ $this, 'request' ], PHP_INT_MAX );
		add_filter( 'post_type_link', [ $this, 'filter_link' ], 10, 2 );
		add_filter( 'post_link', [ $this, 'filter_link' ], 10, 2 );
		add_filter( 'page_link', [ $this, 'page_link' ], 10, 2 );
	}

	/**
	 * Parse the request and register rewrite rules if needed.
	 *
	 * This purpose of this function is to register rewrite rules for the URL alias
	 * Just before WordPress parses the request.
	 *
	 * If the URL alias is not found in the rewrite rules, then it will be added.
	 *
	 * @param bool $filter_request Whether to parse the request or not.
	 *
	 * @return bool
	 */
	public function request( bool $filter_request ): bool {
		if ( is_admin() ) {
			return $filter_request;
		}

		$requested_post_uri = explode( get_home_url(), get_self_link() )[1] ?? '';
		$requested_post_uri = ltrim( untrailingslashit( $requested_post_uri ), '/\\' );

        $post_id = Helper::query_post( $requested_post_uri );

		if ( $post_id ) {
			$this->register_rules_for_post( $post_id );
		}

		return $filter_request;
	}

	/**
	 * Register rewrite rules for post.
	 *
	 * @param int $post_id Post ID.
	 *
	 * @return void
	 */
	private function register_rules_for_post( int $post_id ): void {
		$url_alias = get_post_meta( $post_id, Helper::URL_ALIAS, true );
		$url_alias = trim( $url_alias );

		if ( ! $url_alias ) {
			return;
		}

		$rewrite_rules = get_option( 'rewrite_rules' );
		$rules         = '^' . $url_alias . '/?$';

		if ( ! empty( $rewrite_rules[ $rules ] ) ) {
			return;
		}

		$post_type = get_post_type( $post_id );

		if ( 'page' === $post_type ) {
			add_rewrite_rule( $rules, 'index.php?page_id=' . $post_id, 'top' );
		} else {
			// For post and custom post types.
			add_rewrite_rule( $rules, 'index.php?post_type=' . $post_type . '&p=' . $post_id, 'top' );
		}

		// Just a soft flush to update the `rewrite_rules` option.
		flush_rewrite_rules( false );
	}

	/**
	 * Page link filter.
	 *
	 * @param   string $permalink The post's permalink.
	 * @param   int    $id The post ID.
	 *
	 * @return string
	 */
	public function page_link( string $permalink, int $id ): string {
		$post = get_post( $id );

		if ( ! $post instanceof WP_Post ) {
			return $permalink;
		}

		return $this->filter_link( $permalink, $post );
	}

    /**
     * Filter post link
     *
     * @param   string  $permalink The post's permalink.
     * @param   WP_Post $post      The post object.
     *
     * @return string
     */
	public function filter_link( string $permalink, WP_Post $post ): string {
		if ( 'publish' !== $post->post_status ) {
			return $permalink;
		}

		$url_alias = get_post_meta( $post->ID, Helper::URL_ALIAS, true );
		$url_alias = trim( $url_alias );

		if ( empty( $url_alias ) ) {
			return $permalink;
		}

		return trailingslashit( get_home_url() . '/' . $url_alias );
	}
}
