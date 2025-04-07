<?php
add_shortcode( 'hypeanimations_anim', 'hypeanimations_anim');
function hypeanimations_anim($args){
	global $wpdb;
	global $hypeanimations_table_name;
	$actid = intval($args['id']);
	$upload_dir = wp_upload_dir();
	$uploadfinaldir = $upload_dir['baseurl'].'/hypeanimations/';
	$output = '';
	
	// For debugging
	error_log('Hype Animations Plugin: Rendering animation with ID ' . $actid . ' using table ' . $hypeanimations_table_name);
	
	// Handle optional parameters with defaults
	$custom_width = isset($args['width']) ? $args['width'] : null;
	$custom_height = isset($args['height']) ? $args['height'] : null;
	$is_responsive = isset($args['responsive']) ? filter_var($args['responsive'], FILTER_VALIDATE_BOOLEAN) : false;
	
	// Handle auto_height parameter - it can be used with or without a value
	// These will all work: auto_height="1", auto_height="true", auto_height
	$auto_height = false;
	if (isset($args['auto_height'])) {
		if ($args['auto_height'] === '' || $args['auto_height'] === '1' || filter_var($args['auto_height'], FILTER_VALIDATE_BOOLEAN)) {
			$auto_height = true;
		}
	} else if (array_key_exists('auto_height', $args)) {
		// This handles the case where auto_height is present but has no value
		$auto_height = true;
	}

	// Modified query to remove container_id which doesn't exist in the table
	$result = $wpdb->get_results($wpdb->prepare("SELECT code, slug, container, containerclass FROM $hypeanimations_table_name WHERE id=%d", $actid));

	// If no results, log and return empty
	if (empty($result)) {
		error_log('Hype Animations Plugin: No animation found with ID ' . $actid);
		return '';
	}

	foreach( $result as $results ) {
		$width = "";
		$height = "";
		$type = "";
		$results->containerclass = sanitize_html_class( $results->containerclass );
		
		// Add auto-height class if enabled
		$container_class = $results->containerclass;
		if ($auto_height) {
			$container_class .= ' hype-auto-height';
			
			// Enqueue the auto height script
			wp_enqueue_script(
				'hypeanimations-auto-height',
				plugins_url('/js/hype-auto-height.js', dirname(__FILE__)),
				array(),
				filemtime(plugin_dir_path(dirname(__FILE__)) . '/js/hype-auto-height.js'),
				true
			);
		}
		
		$code = str_replace("https://", "//", html_entity_decode($results->code));
		$code = str_replace("http://", "//", html_entity_decode($results->code));
		list($before, $after) = array_pad(explode('x', $results->slug, 2), -2, null);
		if($before != ""){
			$width = preg_replace('/\D/', '', $before);
		}
		if($after != ""){
			$height = preg_replace('/\D/', '', $after);
			$type = preg_replace('/[0-9]/', '', $after);
		}
		
		// Use custom width/height if provided
		if ($custom_width !== null) {
			$width = $custom_width;
		}
		if ($custom_height !== null && !$auto_height) {
			$height = $custom_height;
		}
		
		if($type == 'fixed'){
			$temp = ($width != "" ? 'width="'.$width.'"' : '').' '.($height != "" && !$auto_height ? 'height="'.$height.'"' : '');
		} else {
			$style_explode_width = explode('width', $results->code);
			$style_explode_height = explode('height', $results->code);
			
			if (count($style_explode_width) > 1) {
				$style_explode_width = explode(';', $style_explode_width[1]);
				$width = str_replace(":", "", $style_explode_width[0]);
			}
			
			if (count($style_explode_height) > 1) {
				$style_explode_height = explode(';', $style_explode_height[1]);
				$height = str_replace(":", "", $style_explode_height[0]);
			}
			
			// Use custom width/height if provided, overriding extracted values
			if ($custom_width !== null) {
				$width = $custom_width;
			}
			if ($custom_height !== null && !$auto_height) {
				$height = $custom_height;
			}
			
			$temp = ($width != "" ? 'width="'.$width.'"' : '').' '.($height != "" && !$auto_height ? 'height="'.$height.'"' : '');
		}
		
		// Generate a unique ID for this animation instance if using auto height
		$container_id = '';
		if ($auto_height) {
			$container_id = 'hype-container-' . $actid . '-' . uniqid();
		}
		
		// Rest of the rendering code remains unchanged
		if ($results->container=='div') { 
			$output .= '<div' . 
				($container_class != '' ? ' class="' . $container_class . '"' : '') . 
				($container_id != '' ? ' id="' . $container_id . '"' : '') . 
				'>'; 
		}
		
		if ($results->container=='iframe' && file_exists(esc_url_raw($upload_dir['basedir'].'/hypeanimations/'.$actid.'/index.html'))){
			$_src = esc_url_raw($upload_dir['baseurl']."/hypeanimations/".$actid."/index.html");
			$output .= '<iframe style="border:none;" frameborder="0" ' . $temp . ' ' .
				($container_class != '' ? 'class="' . $container_class . '"' : '') .
				($container_id != '' ? ' id="' . $container_id . '"' : '') .
				' src="' . $_src . '">';
		} elseif ($results->container=='iframe') {
			$output .= '<iframe ' . $temp . ' ' .
				($container_class != '' ? 'class="' . $container_class . '"' : '') .
				($container_id != '' ? ' id="' . $container_id . '"' : '') .
				' src="' . esc_url_raw(site_url()) . '?just_hypeanimations=' . $actid . '">';
		}
		
		if ($results->container != 'iframe') { $output .= $code; }
		if ($results->container == 'div') { $output .= '</div>'; }
		if ($results->container == 'iframe') { $output .= '</iframe>'; }
	}
	
	// Log output status for debugging
	if (!empty($output)) {
		error_log('Hype Animations Plugin: Successfully generated output for animation ID ' . $actid);
	}
	
	return $output;
}
