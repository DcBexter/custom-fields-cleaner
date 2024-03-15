<?php

/* Exit if accessed directly */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
global $wpdb;

$cf_ajax      = str_contains( $_SERVER['HTTP_ACCEPT'], 'application' ) ? 'false' : 'true';
$cf_clean_1   = false;
$cf_clean_2   = false;
$cf_filter    = 0;
$cf_prefix    = 'CF_';
$cf_db_prefix = $wpdb->prefix;
if ( $cf_ajax == 'true' ) {
	_e( '<h2>Access denied.</h2>', 'cf_cleaner' );
} else if ( is_user_logged_in() && is_admin() ) {
	?>

	<div class="wrap">
		<h2><?php _e( 'CF Cleaner', 'cf_cleaner' ); ?></h2>
		<?php
		/* Allow custom prefixes for CF names */
		if ( ! empty( $_COOKIE['cf_prefix'] ) && strlen( $_COOKIE['cf_prefix'] ) > 2 ) {
			$cf_prefix = $_COOKIE['cf_prefix'];
		}

		/* Allow custom prefixes for wp-database-tables */
		if ( ! empty( $_COOKIE['cf_db_prefix'] ) && strlen( $_COOKIE['cf_db_prefix'] ) > 1 ) {
			$cf_db_prefix = $_COOKIE['cf_db_prefix'];
		}

		/* Check or clean the database */
		if ( isset( $_POST['cf_clean'] ) ) {
			if ( $_POST['cf_clean'] === 'clean' ) {
				$cf_filter = $_POST['cf_filter'];

				switch ( $cf_filter ) {
					case '1':
						$cf_clean_1 = true;
						break;
					case '2':
						$cf_clean_2 = true;
						break;
					case '3':
						$cf_clean_1 = true;
						$cf_clean_2 = true;
						break;
					default:
						// TODO
				}
			} else {
				?>
				<div class="notice notice-error is-dismissible">
					<p>
						<?php printf( __( '<b>Error</b> : passphrase doesn\'t match "<code>%1$s</code>".', 'cf_cleaner' ), 'clean' ); ?>
					</p>
				</div>
				<?php
			}
		} else {
			?>
			<div class="notice notice-warning is-dismissible">
				<p>
					<?php printf( __( 'Before you do any cleaning <a href="%1$s" target="%2$s" title="view plug-in (external)">backup</a> your database first.<br>This tool only proceeds (ACF) fieldnames with a consistent prefix, like <code>CF__</code> (case-insensitive).<br><b>Notice :</b> when restoring CF-entries from trash, <i>false positives</i> can show up as orphans; (re-) save all pages (containing those entries) before cleaning.', 'cf_cleaner' ), 'https://wordpress.org/plugins/wp-dbmanager/', '_blank' ); ?>
				</p>
			</div>
			<?php
		}
		?>
		<script>
			/* Save alternative prefixes as a cookie (don't mesh the database...) */
			function cf_prefix_save() {
				try {
					document.cookie = "cf_prefix=" + document.getElementById("cf_prefix").value + ";";
					document.cookie = "cf_db_prefix=" + document.getElementById("cf_db_prefix").value + ";";
				} catch (error) {
					alert(error.message);
				}
			}

		</script>

		<form method="post" action="#" onsubmit="cf_prefix_save();">
			<table class="form-table">
				<tr>
					<th scope="row"><label for="cf_filter"><?php _e( 'What to clean :', 'cf_cleaner' ); ?></label></th>
					<td>
						<select style="min-width:160px;" name="cf_filter" id="cf_filter">
							<option value="3"<?php if ( $cf_filter == '3' || $cf_filter == '0' ) {
								echo ' selected';
							} ?>><?php _e( 'Clean all', 'cf_cleaner' ); ?></option>
							<option value="1"<?php if ( $cf_filter == '1' ) {
								echo ' selected';
							} ?>><?php _e( 'Orphans only', 'cf_cleaner' ); ?></option>
							<option value="2"<?php if ( $cf_filter == '2' ) {
								echo ' selected';
							} ?>><?php _e( 'Empty only', 'cf_cleaner' ); ?></option>
						</select>
					</td>
				</tr>

				<tr>
					<th scope="row"><label
							for="cf_db_prefix"><?php _e( 'Database-table prefix :', 'cf_cleaner' ); ?></label></th>
					<td><input type="text" name="cf_db_prefix" id="cf_db_prefix" value="<?php echo $cf_db_prefix; ?>"
					           placeholder="wp_" style="min-width:160px;"></td>
				</tr>

				<tr>
					<th scope="row"><label
							for="cf_prefix"><?php _e( 'Field prefix (at least 3 chrs.) :', 'cf_cleaner' ); ?></label>
					</th>
					<td><input type="text" name="cf_prefix" id="cf_prefix" value="<?php echo $cf_prefix; ?>"
					           placeholder="cf_" style="min-width:160px;"></td>
				</tr>

				<tr>
					<th scope="row"><?php printf( __( 'Type <code>%1$s</code> before submit :', 'cf_cleaner' ), 'clean' ); ?></th>
					<td><label>
							<input type="text" name="cf_clean" value="" placeholder="..." style="min-width:160px;">
						</label></td>
				</tr>

			</table>

			<p>
				<button data-action="cf-clean" class="button button-primary"
				        type="submit"><?php _e( 'CF <b>clean</b>', 'cf_cleaner' ); ?></button>
				<button data-action="cf-check" class="button button-secondary" type="button"
				        onclick="cf_prefix_save();window.location = location.href;"><?php _e( 'CF <b>check</b>', 'cf_cleaner' ); ?></button>
			</p>

		</form>

		<br>
		<hr>

		<?php
		define( 'CF_CLEANER', true );
		require __DIR__ . '/cf-cleaner-queries.php'
		?>
	</div>

	<?php
} else {
	_e( '<h2>Access denied.</h2>', 'cf_cleaner' );
}
?>
