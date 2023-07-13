<?php
global $wpdb;
$version = '1.9.8';
$hypeanimations_db_version = $version;
$hypeanimations_table_name = $wpdb->prefix . 'hypeanimations';
$upload_mb = upload_mb();

/**
 * Calculate the maximum file upload size
 *
 * @return string Formatted size string
 */
function upload_mb() {
    $max_upload = parse_size(ini_get('upload_max_filesize'));
    $max_post = parse_size(ini_get('post_max_size'));
    $memory_limit = parse_size(ini_get('memory_limit'));

    // Check for null values and handle gracefully
    if ($max_upload === null || $max_post === null || $memory_limit === null) {
        return 'unknown';
    }

    $upload_mb = min($max_upload, $max_post, $memory_limit);
    
    if ($upload_mb < 0) {
        return 'unknown';
    } else {
        return format_size($upload_mb);
    }
}

/**
 * Convert size string to bytes
 *
 * @param string $size Size string with a unit like '2M' or '512K'
 * @return float Size in bytes
 */
function parse_size($size) {
    $unit = strtolower(substr($size, -1));
    $size = substr($size, 0, -1);

    // Check if size is a valid number
    if (!is_numeric($size)) {
        return null;
    }

    $size = (float) $size;
    switch ($unit) {
        case 'g':
            $size *= 1024;
        case 'm':
            $size *= 1024;
        case 'k':
            $size *= 1024;
            break;
        default:
            // Invalid unit, assuming size is already in bytes
            break;
    }
    return round($size);
}

/**
 * Format the size value into a human-readable string
 *
 * @param float $size Size value in bytes
 * @return string Formatted size string
 */
function format_size($size) {
    if ($size >= 1073741824) {
        return round($size / 1073741824, 2) . 'GB';
    } elseif ($size >= 1048576) {
        return round($size / 1048576, 2) . 'MB';
    } elseif ($size >= 1024) {
        return round($size / 1024, 2) . 'KB';
    } else {
        return round($size) . 'B';
    }
}
