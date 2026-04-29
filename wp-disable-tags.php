<?php

/*
 * Plugin Name:     Wvnderlab - Disable Tags
 * Plugin URI:      https://github.com/wvnderlab-agency/disable-posts/
 * Author:          Wvnderlab Agency
 * Author URI:      https://wvnderlab.com
 * Text Domain:     wvnderlab-disable-tags
 * Version:         0.1.0
 */

/*
 *  ################
 *  ##            ##    Copyright (c) 2026 Wvnderlab Agency
 *  ##
 *  ##   ##  ###  ##    ✉️ moin@wvnderlab.com
 *  ##    #### ####     🔗 https://wvnderlab.com
 *  #####  ##  ###
 */

declare(strict_types=1);

namespace WvnderlabAgency\DisableTags;

use WP_Admin_Bar;
use WP_Query;

defined( 'ABSPATH' ) || die;

// Return early if running in WP-CLI context.
if ( defined( 'WP_CLI' ) && WP_CLI ) {
	return;
}

/**
 * Filter: Disable Tags Enabled
 *
 * @param bool $enabled Whether to enable the disable tags functionality. Default true.
 * @return bool
 */
if ( ! apply_filters( 'wvnderlab/disable-tags/enabled', true ) ) {
	return;
}

/**
 * Disable Tags Taxonomy Support for Posts
 *
 * @hooked action init
 * @return void
 */
function disable_tags_taxonomy(): void {
	unregister_taxonomy_for_object_type( 'post_tag', 'post' );
}

add_action( 'init', __NAMESPACE__ . '\\disable_tags_taxonomy', PHP_INT_MAX );

/**
 * Exclude Tags from Frontend Queries
 *
 * @link   https://developer.wordpress.org/reference/hooks/pre_get_posts/
 * @hooked action pre_get_posts
 *
 * @param WP_Query $query The WP_Query instance (passed by reference).
 * @return void
 */
function exclude_tags_from_queries( WP_Query $query ): void {
	// return early if in admin or not main query.
	if ( is_admin() || ! $query->is_main_query() ) {

		return;
	}

	if ( $query->is_tag() ) {
		$query->set_404();
		status_header( 404 );
		nocache_headers();
	}
}

add_action( 'pre_get_posts', __NAMESPACE__ . '\\exclude_tags_from_queries', PHP_INT_MAX );

/**
 * Prevent assigning tags to posts
 *
 * @hooked filter wp_insert_post_data
 * @param array $data An array of slashed, sanitized, and processed post data.
 * @param array $post An array of sanitized (and slashed) but otherwise unmodified post data.
 * @return array
 */
function prevent_tags_assignment( array $data, array $post ): array {
	if ( isset( $post['tags_input'] ) ) {
		$data['tax_input']['post_tag'] = array();
	}

	return $data;
}

add_filter( 'wp_insert_post_data', __NAMESPACE__ . '\\prevent_tags_assignment', PHP_INT_MAX, 2 );

/**
 * Remove Tags Metabox
 *
 * @hooked action admin_menu
 * @return void
 */
function remove_tags_metabox(): void {
	remove_meta_box( 'tagsdiv-post_tag', 'post', 'side' );
}

add_action( 'admin_menu', __NAMESPACE__ . '\\remove_tags_metabox', PHP_INT_MAX );

/**
 * Remove Tags Menu Page
 *
 * @link   https://developer.wordpress.org/reference/hooks/admin_menu/
 * @hooked action admin_menu
 *
 * @return void
 */
function remove_tags_admin_menu_page(): void {
	remove_menu_page( 'edit-tags.php?taxonomy=post_tag' );
}

add_action( 'admin_menu', __NAMESPACE__ . '\\remove_tags_admin_menu_page', PHP_INT_MAX );

/**
 * Remove REST Posts and Post Taxonomies Endpoints
 *
 * @link   https://developer.wordpress.org/reference/hooks/rest_endpoints/
 * @hooked filter rest_endpoints
 *
 * @param array<string,mixed> $endpoints The REST API endpoints.
 * @return array<string,mixed>
 */
function remove_tags_endpoint( array $endpoints ): array {
	if ( isset( $endpoints['/wp/v2/tags'] ) ) {
		unset( $endpoints['/wp/v2/tags'] );
	}
	if ( isset( $endpoints['/wp/v2/tags/(?P<id>[\d]+)'] ) ) {
		unset( $endpoints['/wp/v2/tags/(?P<id>[\d]+)'] );
	}

	return $endpoints;
}

add_filter( 'rest_endpoints', __NAMESPACE__ . '\\remove_tags_endpoint' );

/**
 * Unregister Tags Widgets
 *
 * @link   https://developer.wordpress.org/reference/hooks/widgets_init/
 * @hooked action widgets_init
 *
 * @return void
 */
function unregister_tags_widgets(): void {
	unregister_widget( 'WP_Widget_Tag_Cloud' );
}

add_action( 'widgets_init', __NAMESPACE__ . '\\unregister_tags_widgets', PHP_INT_MAX );
