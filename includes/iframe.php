<?
add_action('wp_loaded','check_hypeanimation_iframe',1);
function check_hypeanimation_iframe(){
	global $wpdb;
	global $table_name;
	$queryURL = parse_url( html_entity_decode( esc_url( add_query_arg( $arr_params ) ) ) );
	parse_str( $queryURL['query'], $getVar );
	$just_hypeanimations = $getVar['just_hypeanimations'];
	if ($just_hypeanimations>0) {
		$animcode = $wpdb->get_var("SELECT code FROM ".$table_name." WHERE id='".ceil($just_hypeanimations)."' LIMIT 1");
		$animcode = str_replace("https://", "//", html_entity_decode($animcode));
		$animcode = str_replace("http://", "//", html_entity_decode($animcode));
		echo '
<!DOCTYPE html>
<html>
  <head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<meta http-equiv="X-UA-Compatible" content="chrome=1,IE=edge" />
	<title>imageGallerySlide</title>
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
?>