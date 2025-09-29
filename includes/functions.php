<?php
function hypeanimations_menu() {
    add_menu_page(
      'Hype Animations',
      'Hype Animations',
      'publish_posts',
      'hypeanimations_panel',
      'hypeanimations_panel',
      'dashicons-format-video'
    );
}
add_action("admin_menu", "hypeanimations_menu");

function hyperrmdir($dir) {
  if (is_dir($dir)) {
    $objects = scandir($dir);
    foreach ($objects as $object) {
      if ($object != "." && $object != "..") {
        if (filetype($dir."/".$object) == "dir") hyperrmdir($dir."/".$object); else wp_delete_file($dir."/".$object);
      }
    }
    reset($objects);
    rmdir($dir);
  }
}
function hypeanimations_admin_style() {
  wp_register_style('hypeanimations_admin_css', plugins_url( '../css/hypeanimations.css', __FILE__ ), false, '1.9.11' );
  wp_enqueue_style('hypeanimations_admin_css');
  wp_register_style('dataTables_admin_css', plugins_url( '../css/jquery.dataTables.min.css', __FILE__ ), false, '1.0.2' );
  wp_enqueue_style('dataTables_admin_css');
  wp_register_style('custom.modified,jquery-ui_admin_css', plugins_url( '../css/jquery-ui.css', __FILE__ ), false, '1.0.1' );
  wp_enqueue_style('custom.modified,jquery-ui_admin_css');
  wp_register_style('custom.modified,dropzone_css', plugins_url( '../css/dropzone.css', __FILE__ ), false, '1.0.3' );
  wp_enqueue_style('custom.modified,dropzone_css');
  wp_enqueue_script( 'jquery_datatable_hype', plugins_url( '../js/jquery.dataTables.min.js', __FILE__ ), false, '1.0' );
  wp_enqueue_script( 'dropzone_hype', plugins_url( '../js/dropzone.js', __FILE__ ), false, '1.0.0' );
}

function hypeanimations_generate_thumbnail($animation_id) {
    $upload_dir = wp_upload_dir();
    $animation_dir_path = $upload_dir['basedir'] . '/hypeanimations/' . $animation_id . '/';
    $index_html_path = $animation_dir_path . 'index.html';
    $thumbnail_path = $animation_dir_path . 'thumbnail.png';

    if (!file_exists($index_html_path)) {
        return new WP_Error('file_not_found', __('index.html not found.', 'tumult-hype-animations'));
    }

    // Check for wkhtmltoimage
    $command = 'wkhtmltoimage -V';
    $output = shell_exec($command);

    if (strpos($output, 'wkhtmltoimage') !== false) {
        // wkhtmltoimage is available
        $command = sprintf('wkhtmltoimage --quality 50 --width 200 -f png %s %s', escapeshellarg($index_html_path), escapeshellarg($thumbnail_path));
        shell_exec($command);

        if (file_exists($thumbnail_path)) {
            return true;
        } else {
            return new WP_Error('thumbnail_generation_failed', __('Thumbnail generation failed using wkhtmltoimage.', 'tumult-hype-animations'));
        }
    } else {
        return new WP_Error('wkhtmltoimage_not_found', __('wkhtmltoimage not found. Please install it to generate thumbnails.', 'tumult-hype-animations'));
    }
}

add_action( 'admin_enqueue_scripts', 'hypeanimations_admin_style' );
