<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function cf_cleaner_find( $prefix, $filter ) {
	global $wpdb;

	if ( ! current_user_can( 'manage_options' ) ) {
		return new WP_Error( 'forbidden', __( 'Access denied.', 'cf_cleaner' ) );
	}
	if ( ! is_string( $prefix ) || strlen( $prefix ) < 3 || strlen( $prefix ) > 191 || ! in_array( $filter, array( '1', '2', '3' ), true ) ) {
		return new WP_Error( 'invalid_input', __( 'Choose a field prefix of 3–191 bytes and a valid filter.', 'cf_cleaner' ) );
	}

	$conditions = array(
		'1' => 'p.ID IS NULL',
		'2' => "p.ID IS NOT NULL AND BINARY pm.meta_value = ''",
		'3' => "(p.ID IS NULL OR BINARY pm.meta_value = '')",
	);
	$rows = $wpdb->get_results( $wpdb->prepare(
		"SELECT pm.* FROM {$wpdb->postmeta} pm LEFT JOIN {$wpdb->posts} p ON p.ID = pm.post_id
		 WHERE UPPER(pm.meta_key) LIKE %s AND {$conditions[$filter]} ORDER BY pm.meta_id",
		$wpdb->esc_like( strtoupper( $prefix ) ) . '%'
	) );

	return $wpdb->last_error ? new WP_Error( 'database_error', __( 'Database query failed. Nothing was cleaned.', 'cf_cleaner' ) ) : $rows;
}

function cf_cleaner_clean( $prefix, $filter ) {
	$rows = cf_cleaner_find( $prefix, $filter );
	if ( is_wp_error( $rows ) ) {
		return $rows;
	}

	$deleted = 0;
	foreach ( $rows as $row ) {
		// Metadata API invalidates WordPress caches and runs deletion hooks.
		if ( ! delete_metadata_by_mid( 'post', (int) $row->meta_id ) ) {
			return new WP_Error( 'delete_failed', sprintf( __( 'Cleaning stopped after %d deletions. Refresh and check the database.', 'cf_cleaner' ), $deleted ) );
		}
		++$deleted;
	}
	return $deleted;
}
