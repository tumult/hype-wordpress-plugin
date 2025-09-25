<?php
add_shortcode( 'hypeanimations_anim', 'hypeanimations_anim');
function hypeanimations_anim($args){
	global $wpdb;
	global $hypeanimations_table_name;
	$actid = intval($args['id']);
	$upload_dir = wp_upload_dir();
	$uploadfinaldir = $upload_dir['baseurl'].'/hypeanimations/';
	$output='';

	$result = $wpdb->get_results($wpdb->prepare("SELECT code,slug,container,containerclass FROM $hypeanimations_table_name WHERE id=%d",$actid));

	foreach( $result as $results ) {
		$width = "";
		$height = "";
		$type = "";
		$results->containerclass = sanitize_html_class( $results->containerclass );
		$decoded = html_entity_decode($results->code);

		// Determine actual .hyperesources folder on disk for this animation ID (prefer exact filesystem name)
		$final_basedir = $upload_dir['basedir'] . '/hypeanimations/' . $actid . '/';
		$fs_folder_name = null;
		if (is_dir($final_basedir)) {
			$items = scandir($final_basedir);
			foreach ($items as $it) {
				if ($it === '.' || $it === '..') continue;
				if (is_dir($final_basedir . $it) && preg_match('/\.hyperesources$/', $it)) {
					$fs_folder_name = $it;
					break;
				}
			}
		}

		$decoded = preg_replace_callback(
			'#(src=(?:"|\\\'))([^"\\\']+?\.hyperesources/[^"\\\']*)#i',
			function ( $m ) use ( $upload_dir, $actid, $fs_folder_name ) {
				$attr = $m[1];
				$url = $m[2];
				// Leave absolute or protocol-relative or root paths alone
				if ( preg_match('#^(?:https?:)?//#i', $url) || strpos( $url, '/' ) === 0 ) {
					return $attr . $url;
				}
				// Split folder from remainder
				$parts = explode('/', $url, 2);
				$folderRef = rawurldecode($parts[0]);
				$rest = isset($parts[1]) ? $parts[1] : '';
				// Choose filesystem folder if detected, otherwise use the folderRef from HTML
				$folderToUse = $fs_folder_name !== null ? $fs_folder_name : $folderRef;
				$upload_base = rtrim( $upload_dir['baseurl'], '/' ) . '/hypeanimations/' . $actid . '/';
				$full = $upload_base . rawurlencode($folderToUse) . '/' . $rest;
				return $attr . $full;
			},
			$decoded
		);

		// Normalize to protocol-relative URLs
		$decoded = str_replace( array( 'https://', 'http://' ), array( '//', '//' ), $decoded );

		$code = $decoded;
		list($before, $after) = array_pad(explode('x', $results->slug, 2), -2, null);
		if($before != ""){
			$width = preg_replace('/\D/', '', $before);
		}
		if($after != ""){
			$height = preg_replace('/\D/', '', $after);
			$type = preg_replace('/[0-9]/', '', $after);
		}
		if($type == 'fixed'){
			$temp = ($width != "" ? 'width="'.$width.'"' : '').' '.($width != "" ? 'height="'.$height.'"' : '');
		}else{
			$style_explode_width = explode('width', $results->code);
			$style_explode_height = explode('height', $results->code);
			$style_explode_width = explode(';', $style_explode_width[1]);
			$style_explode_height = explode(';', $style_explode_height[1]);
			$width = str_replace(":", "", $style_explode_width[0]);
			$height = str_replace(":", "", $style_explode_height[0]);
			$temp = ($width != "" ? 'width="'.$width.'"' : '').' '.($width != "" ? 'height="'.$height.'"' : '');
		}
		if ($results->container=='div') { $output.='<div'.($results->containerclass!='' ? ' class="'.$results->containerclass.'"' : '').'>'; }
		// Build filesystem path to index.html and check existence correctly
		$index_fs_path = $upload_dir['basedir'] . '/hypeanimations/' . $actid . '/index.html';
		if ($results->container == 'iframe' && file_exists($index_fs_path)) {
			// Use public upload URL for iframe src
			$upload_baseurl = rtrim($upload_dir['baseurl'], '/') . '/hypeanimations/' . $actid . '/index.html';
			$_src = esc_url_raw($upload_baseurl);
			$iframe_attr_parts = array();
			if ('' !== trim($temp)) {
				$iframe_attr_parts[] = trim($temp);
			}
			if ($results->containerclass != '') {
				$iframe_attr_parts[] = 'class="' . $results->containerclass . '"';
			}
			$iframe_attrs = '';
			if (!empty($iframe_attr_parts)) {
				$iframe_attrs = ' ' . implode(' ', $iframe_attr_parts);
			}
			$output .= '<iframe style="border:none;" frameborder="0"' . $iframe_attrs . ' src="' . $_src . '">';
		} elseif ($results->container == 'iframe') {
			// Fallback to the site URL handler which serves the index when requested
			$iframe_attr_parts = array();
			if ('' !== trim($temp)) {
				$iframe_attr_parts[] = trim($temp);
			}
			if ($results->containerclass != '') {
				$iframe_attr_parts[] = 'class="' . $results->containerclass . '"';
			}
			$iframe_attrs = '';
			if (!empty($iframe_attr_parts)) {
				$iframe_attrs = ' ' . implode(' ', $iframe_attr_parts);
			}
			$output .= '<iframe' . ($iframe_attrs !== '' ? $iframe_attrs : '') . ' src="' . esc_url_raw(site_url()) . '?just_hypeanimations=' . $actid . '">';
		}
		if ($results->container!='iframe') { $output.=$code; }
		if ($results->container=='div') { $output.='</div>'; }
		if ($results->container=='iframe') { $output.='</iframe>'; }
	}
	return $output;
}
