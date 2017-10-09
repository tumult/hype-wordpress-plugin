<?php
/*
Plugin Name: Tumult Hype Animations
Version: 1.6
Description: Insert your Tumult Hype animations
Plugin URI: https://forums.tumult.com/t/hype-animations-wordpress-plugin/11074
Author URI: <a href="http://tumult.com" target="_blank">Tumult</a>
Text Domain: hype-animations
Domain Path: /languages
License: GPL2
License URL: https://www.gnu.org/licenses/gpl-2.0.html
*/
#---------------------------------------------------------------------------#
add_action( 'plugins_loaded', 'hypeanimations_init_lang' );
function hypeanimations_init_lang() {
	load_plugin_textdomain( 'hype-animations', false, basename( dirname( __FILE__ ) ) . '/languages/' );
}
include('includes/variables.php');
include('includes/init.php');
include('includes/functions.php');
include('includes/adminpanel.php');
include('includes/shortcode.php');
include('includes/iframe.php');
include('includes/tinymcetool.php');
?>
