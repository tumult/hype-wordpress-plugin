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
function hypeanimations_admin_style($hook_suffix) {
  if ('toplevel_page_hypeanimations_panel' !== $hook_suffix) {
    return;
  }

  global $version;
  $asset_version = isset($version) ? $version : '1.9.19';

  wp_register_style('dataTables_admin_css', plugins_url( '../css/jquery.dataTables.min.css', __FILE__ ), array(), '1.0.2' );
  wp_enqueue_style('dataTables_admin_css');
  wp_register_style('custom.modified,jquery-ui_admin_css', plugins_url( '../css/jquery-ui.css', __FILE__ ), array(), '1.0.1' );
  wp_enqueue_style('custom.modified,jquery-ui_admin_css');
  wp_register_style('custom.modified,dropzone_css', plugins_url( '../css/dropzone.css', __FILE__ ), array(), '1.0.3' );
  wp_enqueue_style('custom.modified,dropzone_css');
  wp_register_style('hypeanimations_admin_css', plugins_url( '../css/hypeanimations.css', __FILE__ ), array( 'dataTables_admin_css', 'custom.modified,jquery-ui_admin_css', 'custom.modified,dropzone_css' ), $asset_version );
  wp_enqueue_style('hypeanimations_admin_css');
  wp_enqueue_script( 'jquery_datatable_hype', plugins_url( '../js/jquery.dataTables.min.js', __FILE__ ), array( 'jquery' ), '1.0' );
  wp_enqueue_script( 'dropzone_hype', plugins_url( '../js/dropzone.js', __FILE__ ), array(), '1.0.0' );
}

add_action( 'admin_enqueue_scripts', 'hypeanimations_admin_style' );
