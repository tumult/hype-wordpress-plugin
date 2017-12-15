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
				if (filetype($dir."/".$object) == "dir") hyperrmdir($dir."/".$object); else unlink($dir."/".$object);
			}
		}
		reset($objects);
		rmdir($dir);
	}
}
function hypeanimations_admin_style() {
    wp_register_style('hypeanimations_admin_css', plugins_url( '../css/hypeanimations.css', __FILE__ ), false, '1.0.0' );
    wp_enqueue_style('hypeanimations_admin_css');
    wp_register_style('dataTables_admin_css', plugins_url( '../css/jquery.dataTables.min.css', __FILE__ ), false, '1.0.0' );
    wp_enqueue_style('dataTables_admin_css');
    wp_register_style('jquery-ui_admin_css', plugins_url( '../css/jquery-ui.css', __FILE__ ), false, '1.0.0' );
    wp_enqueue_style('jquery-ui_admin_css');
    wp_register_style('dropzone_css', plugins_url( '../css/dropzone.css', __FILE__ ), false, '1.0.0' );
    wp_enqueue_style('dropzone_css');
	wp_enqueue_script( 'jquery_datatable', plugins_url( '../js/jquery.dataTables.min.js', __FILE__ ) );
	wp_enqueue_script( 'dropzone', plugins_url( '../js/dropzone.js', __FILE__ ) );
}
add_action( 'admin_enqueue_scripts', 'hypeanimations_admin_style' );
