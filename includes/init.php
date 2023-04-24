<?php
add_action( 'plugins_loaded', 'hypeanimations_init' );
function hypeanimations_init() {
	global $wpdb;
	global $hypeanimations_db_version;
	global $hypeanimations_table_name;
	$installed_ver = get_option( "hypeanimations_db_version" );
	if ( $installed_ver < 1.3 ) {
		$charset_collate = $wpdb->get_charset_collate();
		$sql = "CREATE TABLE $hypeanimations_table_name (id int(9) NOT NULL AUTO_INCREMENT, nom varchar(150) DEFAULT '' NOT NULL, slug varchar(150) DEFAULT '' NOT NULL, code text NOT NULL, updated INT(11) NOT NULL, container ENUM('none','div','iframe') NOT NULL, containerclass VARCHAR(150) NOT NULL, UNIQUE KEY id (id) ) $charset_collate;";
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );
		if ($installed_ver=='') { add_option( 'hypeanimations_db_version', $hypeanimations_db_version ); }
		else { update_option( "hypeanimations_db_version", $hypeanimations_db_version ); }
		$update = $wpdb -> query($wpdb->prepare("UPDATE $hypeanimations_table_name SET container=%s",'none'));
	}
	$upload_dir = wp_upload_dir();
	if (!file_exists($upload_dir['basedir'].'/hypeanimations/')) {
		mkdir($upload_dir['basedir'].'/hypeanimations/');
	}
	if (!file_exists($upload_dir['basedir'].'/hypeanimations/tmp/')) {
		mkdir($upload_dir['basedir'].'/hypeanimations/tmp/');
	}
}
function hypeanimations_install() {
	global $wpdb;
	global $hypeanimations_db_version;
	global $hypeanimations_table_name;
	$charset_collate = $wpdb->get_charset_collate();
	$sql = "CREATE TABLE $hypeanimations_table_name (id int(9) NOT NULL AUTO_INCREMENT, nom varchar(150) DEFAULT '' NOT NULL, slug varchar(150) DEFAULT '' NOT NULL, code text NOT NULL, updated INT(11) NOT NULL, container ENUM('none','div','iframe') NOT NULL, containerclass VARCHAR(150) NOT NULL, UNIQUE KEY id (id) ) $charset_collate;";
	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	dbDelta( $sql );

	// Add the provided SQL query to add a new column for 2.0
	$sql_add_column = "ALTER TABLE $hypeanimations_table_name ADD htmlcode_full TEXT NOT NULL AFTER code";
	$wpdb->query($sql_add_column);

	add_option( 'hypeanimations_db_version', $hypeanimations_db_version );
}
register_activation_hook(__FILE__,'hypeanimations_install');
