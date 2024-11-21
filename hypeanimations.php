<?php
/*
Plugin Name: Tumult Hype Animations
Version: 1.9.16
Description: Easily embed your Tumult Hype animations using a shortcode into posts and pages. 
Plugin URI: https://forums.tumult.com/t/hype-animations-wordpress-plugin/11074
Author URI: <a href="https://tumult.com" target="_blank">Tumult</a>
Text Domain: tumult-hype-animations
Domain Path: /languages
License: GPL2
License URL: https://www.gnu.org/licenses/gpl-2.0.html
*/
#---------------------------------------------------------------------------#
add_action('init', 'hypeanimations_init_textdomain');

function hypeanimations_init_textdomain() {
		load_plugin_textdomain( 'tumult-hype-animations', false, basename( dirname( __FILE__ ) ) . '/languages/' );
}
include('includes/init.php');
include('includes/variables.php');
include('includes/functions.php');
include('includes/adminpanel.php');
include('includes/shortcode.php');
include('includes/iframe.php');
