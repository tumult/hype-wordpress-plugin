<?
if ( !defined( 'WP_UNINSTALL_PLUGIN' ) ) 
    exit();

global $wpdb;
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}hypeanimations" );

delete_option( 'hypeanimations_db_version' );

function hyperrmdir($dir) {
	if (is_dir($dir)) {
		$objects = scandir($dir);
		foreach ($objects as $object) {
			if ($object != "." && $object != "..") {
				if (filetype($dir."/".$object) == "dir") hyperrmdir($dir."/".$object); else unlink($dir."/".$object);
			}
		}
		reset($objects);
		rmdir($dir);
	}
} 
$upload_dir = wp_upload_dir();
$anims_dir=$upload_dir['basedir'].'/hypeanimations/';
hyperrmdir($anims_dir);
?>