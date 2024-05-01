<?php
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit();
}

// Drop the hypeanimations table from the database
global $wpdb;
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}hypeanimations");

// Delete the hypeanimations_db_version option
delete_option('hypeanimations_db_version');

/**
 * Delete a directory and its contents recursively
 *
 * @param string $dir The directory path to delete
 */
function hypeanimations_remove_dir($dir)
{
    if (is_dir($dir)) {
        $files = glob($dir . '/*');
        foreach ($files as $file) {
            if (is_dir($file)) {
                hypeanimations_remove_dir($file);
            } else {
                wp_delete_file($file);
            }
        }
        rmdir($dir);
    }
}

// Get the WordPress upload directory and set the hypeanimations directory path
$upload_dir = wp_upload_dir();
$anims_dir = $upload_dir['basedir'] . '/hypeanimations/';

// Check if the hypeanimations directory exists and remove it along with its contents
if (file_exists($anims_dir)) {
    hypeanimations_remove_dir($anims_dir);
}
