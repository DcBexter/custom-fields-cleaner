<?php
// Run only against a disposable WordPress database: wp eval-file tests/integration.php.
global $wpdb;
function cf_test( $condition, $message ) {
	if ( ! $condition ) {
		throw new RuntimeException( $message );
	}
}
function cf_ids( $rows ) {
	cf_test( ! is_wp_error( $rows ), 'Query failed' );
	return array_map( 'intval', wp_list_pluck( $rows, 'meta_id' ) );
}
function cf_page( $post = array(), $method = 'POST' ) {
	$_SERVER['REQUEST_METHOD'] = $method;
	$_POST = $_REQUEST = wp_slash( $post );
	ob_start();
	try {
		cf_cleaner_render_page();
		return ob_get_contents();
	} finally {
		ob_end_clean();
	}
}
add_filter( 'wp_die_handler', function () {
	return function ( $message ) { throw new RuntimeException( 'wp_die' ); };
} );

$admin = get_users( array( 'role' => 'administrator', 'number' => 1 ) )[0];
wp_set_current_user( $admin->ID );
$post = wp_insert_post( array( 'post_title' => 'CF Cleaner integration fixture', 'post_status' => 'publish' ) );
cf_test( $post > 0, 'Post fixture missing' );
$empty = add_post_meta( $post, 'CF_empty', '' );
$value = add_post_meta( $post, 'CF_title', '<script>alert(1)</script>' );
$zero = add_post_meta( $post, 'CF_zero', '0' );
$space = add_post_meta( $post, 'CF_space', ' ' );
$other = add_post_meta( $post, 'other_empty', '' );
$wildcard = add_post_meta( $post, 'CFXempty', '' );
$ref = add_post_meta( $post, '_CF_empty', 'field_123' );
$orphan = add_post_meta( 987654321, 'cf_orphan', '<script>alert(1)</script>' );
$orphan_empty = add_post_meta( 987654321, 'CF_orphan_empty', '' );
$other_orphan = add_post_meta( 987654321, 'other_orphan', 'keep' );
$percent = add_post_meta( $post, 'CF%_empty', '' );
$quote = add_post_meta( $post, "CF'empty", '' );
$trash = wp_insert_post( array( 'post_title' => 'Trashed parent', 'post_status' => 'trash' ) );
$trash_meta = add_post_meta( $trash, 'CF_trash', 'keep' );
$revision = wp_insert_post( array( 'post_type' => 'revision', 'post_parent' => $post, 'post_status' => 'inherit' ) );
$revision_meta = add_metadata( 'post', $revision, 'CF_revision', 'keep' );

cf_test( cf_ids( cf_cleaner_find( 'CF_', '1' ) ) === array( $orphan, $orphan_empty ), 'Orphans must respect prefix' );
cf_test( cf_ids( cf_cleaner_find( 'CF_', '2' ) ) === array( $empty ), 'Only exact empty string on existing posts' );
cf_test( count( cf_cleaner_find( 'cf_', '3' ) ) === 3, 'Combined filter / case folding' );
cf_test( cf_ids( cf_cleaner_find( 'CF%_', '2' ) ) === array( $percent ), 'LIKE percent must be literal' );
cf_test( cf_ids( cf_cleaner_find( "CF'", '2' ) ) === array( $quote ), 'Quotes must be parameterized' );
cf_test( is_wp_error( cf_cleaner_clean( '', '3' ) ), 'Empty prefix rejected' );
cf_test( is_wp_error( cf_cleaner_clean( array(), '3' ) ), 'Array prefix rejected' );
cf_test( is_wp_error( cf_cleaner_clean( str_repeat( 'x', 192 ), '3' ) ), 'Overlong prefix rejected' );
cf_test( is_wp_error( cf_cleaner_clean( 'CF_', '9' ) ), 'Invalid filter rejected' );

$before = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->postmeta}" );
$html = cf_page( array(), 'GET' );
cf_test( str_contains( $html, 'name="_wpnonce"' ), 'Nonce missing from form' );
cf_test( ! str_contains( $html, '<script>alert(1)</script>' ), 'Unescaped metadata output' );
cf_test( str_contains( $html, '&lt;script&gt;alert(1)&lt;/script&gt;' ), 'Preview must show escaped values' );
$nonce = wp_create_nonce( 'cf_cleaner_clean' );
$request = array( '_wpnonce' => $nonce, 'cf_prefix' => 'CF_', 'cf_filter' => '3', 'cf_action' => 'check', 'cf_clean' => 'clean' );
cf_page( $request );
cf_test( $before === (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->postmeta}" ), 'Preview must not delete' );
$request['cf_action'] = 'clean';
foreach ( array( '', 'invalid' ) as $bad_nonce ) {
	$request['_wpnonce'] = $bad_nonce;
	$blocked = false;
	try { cf_page( $request ); } catch ( RuntimeException $e ) { $blocked = 'wp_die' === $e->getMessage(); }
	cf_test( $blocked, 'Missing / invalid nonce must be blocked' );
}
$request['_wpnonce'] = $nonce;
$request['cf_clean'] = 'wrong';
cf_test( str_contains( cf_page( $request ), 'Type clean' ), 'Passphrase must match' );
$request['cf_clean'] = 'clean';
$request['cf_prefix'] = array( 'CF_' );
cf_test( str_contains( cf_page( $request ), 'valid filter' ), 'Malformed prefix must be blocked' );
cf_test( $before === (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->postmeta}" ), 'Rejected requests must preserve data' );
$request['cf_prefix'] = 'CF_';

$subscriber = wp_insert_user( array( 'user_login' => 'cf_subscriber', 'user_pass' => wp_generate_password(), 'role' => 'subscriber' ) );
cf_test( ! is_wp_error( $subscriber ), 'Subscriber fixture missing' );
wp_set_current_user( $subscriber );
cf_test( is_wp_error( cf_cleaner_clean( 'CF_', '3' ) ), 'Subscriber must not clean' );
$blocked = false;
try { cf_page( $request ); } catch ( RuntimeException $e ) { $blocked = 'wp_die' === $e->getMessage(); }
cf_test( $blocked, 'Subscriber admin page blocked' );
wp_set_current_user( 0 );
cf_test( is_wp_error( cf_cleaner_find( 'CF_', '3' ) ), 'Anonymous scan blocked' );
wp_set_current_user( $admin->ID );

get_post_meta( $post ); // Prime cache before deletion.
cf_test( cf_cleaner_clean( 'CF_', '2' ) === 1, 'Empty-only delete count' );
cf_test( ! metadata_exists( 'post', $post, 'CF_empty' ), 'Metadata cache must be invalidated' );
cf_test( false === get_metadata_by_mid( 'post', $empty ), 'Empty value deleted' );
cf_test( false !== get_metadata_by_mid( 'post', $orphan ), 'Empty-only keeps orphan values' );
$html = cf_page( $request );
cf_test( str_contains( $html, 'Removed 2 entries.' ), 'Authorized combined request must clean remaining orphans' );
cf_test( cf_cleaner_clean( 'CF_', '1' ) === 0, 'Repeated cleaning must be idempotent' );
foreach ( array( $value, $zero, $space, $other, $wildcard, $ref, $other_orphan, $percent, $quote, $trash_meta, $revision_meta ) as $id ) {
	cf_test( false !== get_metadata_by_mid( 'post', $id ), 'Legitimate metadata was removed: ' . $id );
}
cf_test( cf_cleaner_find( 'CF_', '3' ) === array(), 'No candidates remain' );

// SQL failure must stop cleanup; suppress only expected fixture error output.
$old_table = $wpdb->postmeta;
$wpdb->postmeta = 'cf_cleaner_missing_table';
$old_suppress = $wpdb->suppress_errors( true );
cf_test( is_wp_error( cf_cleaner_clean( 'CF_', '3' ) ), 'Database failure must not report success' );
$wpdb->postmeta = $old_table;
$wpdb->suppress_errors( $old_suppress );

echo 'PASS: WordPress ' . get_bloginfo( 'version' ) . ' / PHP ' . PHP_VERSION . " — prefix scope, literal wildcards, empty/orphan filters, preservation, nonce, capabilities, preview, escaping, cache and database failure.\n";
