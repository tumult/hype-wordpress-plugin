<?php
/*
Plugin Name: Tumult Hype Animations
Version: 1.9.14
Description: Easily embed your Tumult Hype animations using a shortcode into posts and pages. 
Plugin URI: https://forums.tumult.com/t/hype-animations-wordpress-plugin/11074
Author URI: <a href="https://tumult.com" target="_blank">Tumult</a>
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
include('includes/init.php');
include('includes/variables.php');
include('includes/functions.php');
include('includes/adminpanel.php');
include('includes/shortcode.php');
include('includes/iframe.php');
