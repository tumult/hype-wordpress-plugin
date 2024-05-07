<?php
add_action('wp_loaded','check_hypeanimation_iframe');
function check_hypeanimation_iframe() {
	global $wpdb;
	global $hypeanimations_table_name;
	$arr_params = array();
	$queryURL = parse_url(html_entity_decode(esc_url(add_query_arg($arr_params))));

	if (isset($queryURL['query'])) {
		parse_str($queryURL['query'], $getVar);
		$just_hypeanimations = isset($getVar['just_hypeanimations']) ? $getVar['just_hypeanimations'] : 0;

		if ($just_hypeanimations > 0) {
			$animationdata = $wpdb->get_row($wpdb->prepare("SELECT code, nom FROM " . $hypeanimations_table_name . " WHERE id=%d LIMIT 1", ceil($just_hypeanimations)), OBJECT);
			$animcode = $animationdata->code;
			$animcode = str_replace("https://", "//", html_entity_decode($animcode));
			$animcode = str_replace("http://", "//", html_entity_decode($animcode));
			$animationname = $animationdata->nom;

			echo '<!DOCTYPE html>
					<html>
					  <head>
						<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
						<meta http-equiv="X-UA-Compatible" content="chrome=1,IE=edge" />
						<title>' . sanitize_text_field($animationname) . '</title>
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
	}
}
