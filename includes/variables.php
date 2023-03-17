<?php
$version = '1.9.8';
$hypeanimations_db_version = $version;
$hypeanimations_table_name = $wpdb->prefix . 'hypeanimations';
$upload_mb = upload_mb();

function upload_mb() {
    $max_upload = parse_size(ini_get('upload_max_filesize'));
    $max_post = parse_size(ini_get('post_max_size'));
    $memory_limit = parse_size(ini_get('memory_limit'));

    $upload_mb = min($max_upload, $max_post, $memory_limit);
    
    if ($upload_mb < 0) {
        return 'unknown';
    } else {
        return format_size($upload_mb);
    }
}

function parse_size($size) {
    $unit = strtoupper(substr($size, -1));
    $size = substr($size, 0, -1);
    switch ($unit) {
        case 'G':
            $size *= 1024;
        case 'M':
            $size *= 1024;
        case 'K':
            $size *= 1024;
    }
    return round($size);
}

function format_size($size) {
    if ($size >= 1073741824) {
        return round($size / 1073741824, 2) . 'GB';
    } elseif ($size >= 1048576) {
        return round($size / 1048576, 2) . 'MB';
    } elseif ($size >= 1024) {
        return round($size / 1024, 2) . 'KB';
    } else {
        return $size . 'B';
    }
}
