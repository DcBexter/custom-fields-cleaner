<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function cf_cleaner_condition( $filter ) {
	return array(
		'1' => 'p.ID IS NULL',
		'2' => "p.ID IS NOT NULL AND BINARY pm.meta_value = ''",
		'3' => "(p.ID IS NULL OR BINARY pm.meta_value = '')",
	)[ $filter ];
}

function cf_cleaner_find( $prefix, $filter ) {
	global $wpdb;

	if ( ! current_user_can( 'manage_options' ) ) {
		return new WP_Error( 'forbidden', __( 'Access denied.', 'cf_cleaner' ) );
	}
	if ( ! is_string( $prefix ) || strlen( $prefix ) < 3 || strlen( $prefix ) > 191 || ! in_array( $filter, array( '1', '2', '3' ), true ) ) {
		return new WP_Error( 'invalid_input', __( 'Choose a field prefix of 3–191 bytes and a valid filter.', 'cf_cleaner' ) );
	}

	$condition = cf_cleaner_condition( $filter );
	$rows = $wpdb->get_results( $wpdb->prepare(
		"SELECT pm.* FROM {$wpdb->postmeta} pm LEFT JOIN {$wpdb->posts} p ON p.ID = pm.post_id
		 WHERE UPPER(pm.meta_key) LIKE %s AND {$condition} ORDER BY pm.meta_id",
		$wpdb->esc_like( strtoupper( $prefix ) ) . '%'
	) );

	return $wpdb->last_error ? new WP_Error( 'database_error', __( 'Database query failed. Nothing was cleaned.', 'cf_cleaner' ) ) : $rows;
}

function cf_cleaner_clean( $prefix, $filter ) {
	global $wpdb;

	$rows = cf_cleaner_find( $prefix, $filter );
	if ( is_wp_error( $rows ) ) {
		return $rows;
	}

	$deleted = 0;
	$condition = cf_cleaner_condition( $filter );
	foreach ( $rows as $row ) {
		$meta_id = (int) $row->meta_id;
		$post_id = (int) $row->post_id;
		$result = apply_filters( 'delete_post_metadata_by_mid', null, $meta_id );
		if ( null !== $result ) {
			$result = (bool) $result;
		} else {
			$value = maybe_unserialize( $row->meta_value );
			do_action( 'delete_post_meta', array( $meta_id ), $post_id, $row->meta_key, $value );
			do_action( 'delete_postmeta', $meta_id );
			// Match the scan snapshot and current eligibility in the DELETE, after hooks too.
			$value_condition = null === $row->meta_value ? 'pm.meta_value IS NULL' : $wpdb->prepare( 'BINARY pm.meta_value = BINARY %s', $row->meta_value );
			$result = $wpdb->query( $wpdb->prepare(
				"DELETE pm FROM {$wpdb->postmeta} pm LEFT JOIN {$wpdb->posts} p ON p.ID = pm.post_id
				 WHERE pm.meta_id = %d AND pm.post_id = %d AND BINARY pm.meta_key = BINARY %s
				 AND {$value_condition} AND {$condition}",
				$meta_id, $post_id, $row->meta_key
			) );
			wp_cache_delete( $post_id, 'post_meta' );
			if ( $result > 0 ) {
				do_action( 'deleted_post_meta', array( $meta_id ), $post_id, $row->meta_key, $value );
				do_action( 'deleted_postmeta', $meta_id );
			}
		}
		if ( false === $result ) {
			return new WP_Error( 'delete_failed', sprintf( __( 'Cleaning stopped after %d deletions. Refresh and check the database.', 'cf_cleaner' ), $deleted ) );
		}
		if ( $result ) {
			++$deleted;
		}
	}
	return $deleted;
}
