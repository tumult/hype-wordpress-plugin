<?php
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit();
}

global $wpdb;

// Remove the hypeanimations table
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}hypeanimations");

// Remove the 'hypeanimations_db_version' option
delete_option('hypeanimations_db_version');

// Remove the wp-content/uploads/hypeanimations folder and all its contents
function removeHypeAnimationsDir($dir)
{
    if (is_dir($dir)) {
        $objects = scandir($dir);
        foreach ($objects as $object) {
            if ($object !== "." && $object !== "..") {
                $filePath = $dir . "/" . $object;
                if (is_dir($filePath)) {
                    removeHypeAnimationsDir($filePath);
                } else {
                    unlink($filePath);
                }
            }
        }
        rmdir($dir);
    }
}

$uploadDir = wp_upload_dir();
$animsDir = $uploadDir['basedir'] . '/hypeanimations/';
removeHypeAnimationsDir($animsDir);
?>
