<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
if ( ! current_user_can( 'manage_options' ) ) {
	wp_die( esc_html__( 'Access denied.', 'cf_cleaner' ), '', array( 'response' => 403 ) );
}

$cf_prefix = 'CF_';
$cf_filter = '3';
$cf_result = null;
if ( 'POST' === ( $_SERVER['REQUEST_METHOD'] ?? '' ) ) {
	check_admin_referer( 'cf_cleaner_clean' );
	$cf_prefix = isset( $_POST['cf_prefix'] ) && is_string( $_POST['cf_prefix'] ) ? wp_unslash( $_POST['cf_prefix'] ) : '';
	$cf_filter = isset( $_POST['cf_filter'] ) && is_string( $_POST['cf_filter'] ) ? wp_unslash( $_POST['cf_filter'] ) : '';
	if ( isset( $_POST['cf_action'] ) && 'clean' === $_POST['cf_action'] ) {
		$cf_result = isset( $_POST['cf_clean'] ) && 'clean' === $_POST['cf_clean']
			? cf_cleaner_clean( $cf_prefix, $cf_filter )
			: new WP_Error( 'passphrase', __( 'Type clean to confirm deletion.', 'cf_cleaner' ) );
	}
}
$cf_rows = cf_cleaner_find( $cf_prefix, $cf_filter );
?>
<div class="wrap">
	<h1><?php esc_html_e( 'Custom Fields Cleaner', 'cf_cleaner' ); ?></h1>
	<p><?php esc_html_e( 'Back up your database first. Deletion cannot be undone. Only matching field prefixes in this site’s post metadata are processed. Orphan means the parent post no longer exists; a deleted ACF field definition alone does not make its values orphaned.', 'cf_cleaner' ); ?></p>
	<?php if ( null !== $cf_result ) : ?>
		<div class="notice <?php echo is_wp_error( $cf_result ) ? 'notice-error' : 'notice-success'; ?>"><p><?php
			echo esc_html( is_wp_error( $cf_result ) ? $cf_result->get_error_message() : sprintf( __( 'Removed %d entries.', 'cf_cleaner' ), $cf_result ) );
		?></p></div>
	<?php endif; ?>
	<form method="post">
		<?php wp_nonce_field( 'cf_cleaner_clean' ); ?>
		<table class="form-table">
			<tr><th><label for="cf_filter"><?php esc_html_e( 'What to clean', 'cf_cleaner' ); ?></label></th><td>
				<select name="cf_filter" id="cf_filter">
					<?php foreach ( array( '3' => __( 'Clean all', 'cf_cleaner' ), '1' => __( 'Orphans only', 'cf_cleaner' ), '2' => __( 'Empty only', 'cf_cleaner' ) ) as $cf_value => $cf_label ) : ?>
						<option value="<?php echo esc_attr( $cf_value ); ?>" <?php selected( $cf_filter, (string) $cf_value ); ?>><?php echo esc_html( $cf_label ); ?></option>
					<?php endforeach; ?>
				</select>
			</td></tr>
			<tr><th><label for="cf_prefix"><?php esc_html_e( 'Field prefix', 'cf_cleaner' ); ?></label></th><td><input name="cf_prefix" id="cf_prefix" value="<?php echo esc_attr( $cf_prefix ); ?>" minlength="3" maxlength="191" required> <span><?php esc_html_e( 'Case insensitive; % and _ are literal characters.', 'cf_cleaner' ); ?></span></td></tr>
			<tr><th><label for="cf_clean"><?php esc_html_e( 'Type clean to delete', 'cf_cleaner' ); ?></label></th><td><input name="cf_clean" id="cf_clean" autocomplete="off"></td></tr>
		</table>
		<p><button class="button button-secondary" name="cf_action" value="check"><?php esc_html_e( 'Check', 'cf_cleaner' ); ?></button> <button class="button button-primary" name="cf_action" value="clean"><?php esc_html_e( 'Clean', 'cf_cleaner' ); ?></button></p>
	</form>
	<?php if ( is_wp_error( $cf_rows ) ) : ?>
		<div class="notice notice-error"><p><?php echo esc_html( $cf_rows->get_error_message() ); ?></p></div>
	<?php else : ?>
		<h2><?php echo esc_html( sprintf( __( '%d matching entries', 'cf_cleaner' ), count( $cf_rows ) ) ); ?></h2>
		<table class="widefat striped"><thead><tr><th>meta_id</th><th>post_id</th><th>meta_key</th><th>meta_value</th></tr></thead><tbody>
			<?php foreach ( $cf_rows as $cf_row ) : ?>
				<tr><td><?php echo esc_html( $cf_row->meta_id ); ?></td><td><?php echo esc_html( $cf_row->post_id ); ?></td><td><?php echo esc_html( $cf_row->meta_key ); ?></td><td><?php echo esc_html( $cf_row->meta_value ); ?></td></tr>
			<?php endforeach; ?>
		</tbody></table>
	<?php endif; ?>
</div>
