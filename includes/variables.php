<?php
global $wpdb;
$version = '1.9.14';
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

    // Check if there is no limit
    if ($upload_mb === 0 || $upload_mb === PHP_INT_MAX) {
        return 'unlimited';
    } elseif ($upload_mb < 0) {
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
    $unit = preg_match('/(k|m|g)$/i', $size, $matches) ? strtolower($matches[1]) : '';
    $size = (float) $size;

    if (!is_numeric($size)) {
        // Default value of 20MB in bytes
        return 20 * 1024 * 1024;
    }

    switch ($unit) {
        case 'g':
            $size *= 1024 * 1024 * 1024;
            break;
        case 'm':
            $size *= 1024 * 1024;
            break;
        case 'k':
            $size *= 1024;
            break;
    }

    return is_numeric($size) ? round($size) : null;
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
