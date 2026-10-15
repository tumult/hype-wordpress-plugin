<?php
add_action('template_redirect','check_hypeanimation_iframe');
function check_hypeanimation_iframe() {
	global $wpdb;
	global $hypeanimations_table_name;
	// Arrays (?just_hypeanimations[]=1) are ignored instead of cast to 1.
	$just_hypeanimations = (isset($_GET['just_hypeanimations']) && !is_array($_GET['just_hypeanimations'])) ? absint($_GET['just_hypeanimations']) : 0;
	if (!$just_hypeanimations) {
		return;
	}

	$animationdata = $wpdb->get_row($wpdb->prepare("SELECT code, nom FROM " . $hypeanimations_table_name . " WHERE id=%d LIMIT 1", $just_hypeanimations), OBJECT);
	if (!$animationdata) {
		status_header(404);
		exit();
	}
	$animcode = $animationdata->code;
	$animcode = str_replace("https://", "//", html_entity_decode($animcode));
	$animcode = str_replace("http://", "//", html_entity_decode($animcode));
	$animationname = $animationdata->nom;

	header('Content-Type: text/html; charset=utf-8');
	header('X-Robots-Tag: noindex');
	echo '<!DOCTYPE html>
					<html>
					  <head>
						<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
						<meta http-equiv="X-UA-Compatible" content="chrome=1,IE=edge" />
						<title>' . esc_html(sanitize_text_field($animationname)) . '</title>
						<style>
							html {
								height:100%;
							}
							body {
								background-color:#000000;
								margin:0;
								height:100%;
							}
						</style>
						<meta name="viewport" content="user-scalable=yes, width=600" />
					  </head>
					  <body>
						'.html_entity_decode($animcode).'
					  </body>
					</html>
				';
	exit();
}
