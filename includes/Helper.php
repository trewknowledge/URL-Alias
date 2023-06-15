<?php
/**
 * URL Alias Helper.
 *
 * @package  TK\UrlAlias\Helper
 */

declare( strict_types=1 );

namespace TK\UrlAlias;

defined( 'ABSPATH' ) || exit;

/**
 * Helper class.
 */
class Helper {
    public const URL_ALIAS = 'tk_url_alias';
    public const CACHE_KEY = 'tk_url_alias_cache';

    /**
     * Query post ID by requested URL.
     *
     * @param   string $requested_url  Requested URL.
     *
     * @return int
     */
    public static function query_post( string $requested_url ): int {
        global $wpdb;
        $requested_url  = ltrim( untrailingslashit( $requested_url ), '/\\' );
        $cached_post_id = (int) wp_cache_get( self::CACHE_KEY . $requested_url );

        if ( ! $requested_url ) {
            return 0;
        }

        if ( $cached_post_id ) {
            return $cached_post_id;
        }

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
        $post = $wpdb->get_row(
            $wpdb->prepare(
                'SELECT p.ID ' .
                " FROM $wpdb->posts AS p INNER JOIN $wpdb->postmeta AS pm ON (pm.post_id = p.ID) " .
                ' WHERE pm.meta_key = %s ' .
                ' AND pm.meta_value = %s ' .
                " AND p.post_status != 'trash' AND p.post_type != 'nav_menu_item' " .
                ' LIMIT 1',
                self::URL_ALIAS,
                $requested_url
            )
        );

        $post_id = absint( $post->ID ?? 0 );

        wp_cache_set( self::CACHE_KEY . $requested_url, $post_id );

        return $post_id;
    }

    /**
     * Get supported post types.
     *
     * @return array
     */
    public static function get_post_types(): array {
        $custom_post_types = get_post_types(
            [
                'public'   => true,
                '_builtin' => false,
            ],
        );

        $post_types = array_merge( [ 'post', 'page' ], $custom_post_types );

        return apply_filters( 'tk_url_alias_post_types', $post_types );
    }
}
