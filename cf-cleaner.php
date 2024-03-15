<?php
/*
  Plugin Name: Custom Fields Cleaner
  Plugin URI: https://github.com/DcBexter/custom-fields-cleaner
  Description: Remove empty and orphaned Custom Field entries.
  Version: 1.5.0
  Author: DcBexter
  Author URI: https://github.com/DcBexter/
*/
define( 'CF_CLEANER_PAGE', plugin_basename( dirname( __FILE__ ) ) . '/cf-cleaner-admin.php' );

function cf_cleaner_admin_menu(): void {
	add_management_page( __( 'Custom Fields Cleaner | Manage', 'cf_cleaner' ),
		__( 'Custom Fields Cleaner', 'cf_cleaner' ),
		'manage_options',
		CF_CLEANER_PAGE );
}

add_action( 'admin_menu', 'cf_cleaner_admin_menu' );

function cf_cleaner_manage_link( $links, $file ) {
	static $this_plugin;

	if ( ! $this_plugin ) {
		$this_plugin = plugin_basename( __FILE__ );
	}

	if ( $file == $this_plugin ) {
		$settings_link = '<a href="tools.php?page=' . CF_CLEANER_PAGE . '" title="' . __( 'Use this plugin', 'cf_cleaner' ) . '">' . __( 'Manage', 'cf_cleaner' ) . '</a>';
		array_unshift( $links, $settings_link );
	}

	return $links;
}

add_filter( 'plugin_action_links', 'cf_cleaner_manage_link', 10, 2 );

function cf_cleaner_meta_links( $links, $file ) {
	$plugin = plugin_basename( __FILE__ );

	if ( $file == $plugin ) {
		$links[] = '<a href="tools.php?page=' . CF_CLEANER_PAGE . '" title="' . __( 'Use this plugin', 'cf_cleaner' ) . '">' . __( 'Manage', 'cf_cleaner' ) . '</a>';

		return $links;
	}

	return $links;
}

add_filter( 'plugin_row_meta', 'cf_cleaner_meta_links', 10, 2 );
