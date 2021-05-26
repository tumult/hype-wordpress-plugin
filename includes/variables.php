<?php
$version='1.9.5';
$hypeanimations_db_version = $version;
$hypeanimations_table_name = $wpdb->prefix . 'hypeanimations';
$max_upload = (int)(ini_get('upload_max_filesize'));
$max_post = (int)(ini_get('post_max_size'));
$memory_limit = (int)(ini_get('memory_limit'));
$upload_mb = min($max_upload, $max_post, $memory_limit) . "MB";