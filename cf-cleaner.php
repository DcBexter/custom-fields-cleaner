<?php
/*
  Plugin Name: Custom Fields Cleaner
  Plugin URI: https://github.com/DcBexter/custom-fields-cleaner
  Description: Remove empty and orphaned Custom Field entries.
  Version: 1.5.1
  Requires at least: 7.1
  Requires PHP: 8.2
  License: MIT
  Text Domain: cf_cleaner
  Author: DcBexter
  Author URI: https://github.com/DcBexter/
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/cf-cleaner-queries.php';

define( 'CF_CLEANER_PAGE', plugin_basename( dirname( __FILE__ ) ) . '/cf-cleaner-admin.php' );

function cf_cleaner_admin_menu(): void {
	add_management_page( __( 'Custom Fields Cleaner | Manage', 'cf_cleaner' ),
		__( 'Custom Fields Cleaner', 'cf_cleaner' ),
		'manage_options',
		CF_CLEANER_PAGE,
		'cf_cleaner_render_page' );
}

add_action( 'admin_menu', 'cf_cleaner_admin_menu' );

function cf_cleaner_render_page(): void {
	require __DIR__ . '/cf-cleaner-admin.php';
}

function cf_cleaner_manage_link( $links, $file ) {
	if ( plugin_basename( __FILE__ ) === $file ) {
		array_unshift( $links, '<a href="' . esc_url( admin_url( 'tools.php?page=' . CF_CLEANER_PAGE ) ) . '">' . esc_html__( 'Manage', 'cf_cleaner' ) . '</a>' );
	}
	return $links;
}

add_filter( 'plugin_action_links', 'cf_cleaner_manage_link', 10, 2 );
add_filter( 'plugin_row_meta', 'cf_cleaner_manage_link', 10, 2 );
