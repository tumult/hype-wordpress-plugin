<?php
add_shortcode( 'hypeanimations_anim', 'hypeanimations_anim');
function hypeanimations_anim($args){
	global $wpdb;
	global $hypeanimations_table_name;
	$actid = intval($args['id']);
	$upload_dir = wp_upload_dir();
	$uploadfinaldir = $upload_dir['baseurl'].'/hypeanimations/';
	$output = '';
	
	 
	
	// Handle optional parameters with defaults
	$custom_width = isset($args['width']) ? $args['width'] : null;
	$custom_height = isset($args['height']) ? $args['height'] : null;
	$min_height = isset($args['min_height']) ? $args['min_height'] : null;
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

	// Sanitize the provided min height value, allowing common CSS units
	if ($min_height !== null) {
		$min_height = trim($min_height);
		if ($min_height === '') {
			$min_height = null;
		} else if (!preg_match('/^\d+(?:\.\d+)?(px|em|rem|vh|vw|vmin|vmax|%)?$/i', $min_height)) {
			$min_height = null;
		}
	}
	
	// Handle embedmode parameter to override the container type
	$embed_mode = isset($args['embedmode']) ? strtolower(trim($args['embedmode'])) : null;

	// Modified query to remove container_id which doesn't exist in the table
	$result = $wpdb->get_results($wpdb->prepare("SELECT code, slug, container, containerclass FROM $hypeanimations_table_name WHERE id=%d", $actid));

	// If no results, log and return empty
	if (empty($result)) {
		 
		return '';
	}

	foreach( $result as $results ) {
		$width = "";
		$height = "";
		$type = "";
		$results->containerclass = sanitize_html_class( $results->containerclass );
		$decoded = html_entity_decode($results->code);
		
		// Check if containerclass already has hype-auto-height - if so, enqueue the script immediately
		if (strpos($results->containerclass, 'hype-auto-height') !== false) {
			wp_enqueue_script(
				'hypeanimations-auto-height',
				plugins_url('/js/hype-auto-height.js', dirname(__FILE__)),
				array(),
				filemtime(plugin_dir_path(dirname(__FILE__)) . '/js/hype-auto-height.js'),
				false
			);
		}

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
		
		// Use custom width/height if provided
		if ($custom_width !== null) {
			$width = $custom_width;
		}
		if ($custom_height !== null && !$auto_height) {
			$height = $custom_height;
		}
		if ($min_height === null && $custom_height !== null) {
			$min_height = $custom_height;
		}
		
		// Enhanced dimension extraction from the Hype document
		$original_width = "";
		$original_height = "";
		
		// Search for HYPE_document div in the code to extract dimensions
		if (preg_match('/<div id="[^"]*_hype_container" class="HYPE_document" style="[^"]*width:(\d+)px;height:(\d+)px;[^"]*">/i', $code, $matches)) {
			$original_width = $matches[1] . 'px';
			$original_height = $matches[2] . 'px';
			
			// Only use the original dimensions if custom values weren't provided
			if ($custom_width === null) {
				$width = $original_width;
			}
			if ($custom_height === null && !$auto_height) {
				$height = $original_height;
				
				// Enable auto height if height is set to 100%
				if ($height === "100%") {
					$auto_height = true;
				}
			}
		}
		
		 // Get numeric values for width/height calculations
		$numeric_width = intval(preg_replace('/[^0-9]/', '', $original_width));
		$numeric_height = intval(preg_replace('/[^0-9]/', '', $original_height));
		// Calculate aspect ratio if we have valid dimensions
		$aspect_ratio = 0;
		if ($numeric_width > 0 && $numeric_height > 0) {
			$aspect_ratio = $numeric_height / $numeric_width;
		}
		
		if ($min_height === null) {
			// Default min-height for proportional layouts
			if ($auto_height && $numeric_height > 0) {
				$min_height = $numeric_height . 'px';
			} elseif ($custom_height === null && $numeric_height > 0 && (strpos((string) $height, '%') !== false || $height === '' || $height === null)) {
				$min_height = $numeric_height . 'px';
			}
		}

		// Ensure auto-height is enabled when no explicit height is provided but we have original dimensions
		if ($auto_height === false && $custom_height === null && $numeric_height > 0 && strpos((string) $original_height, '%') !== false) {
			$auto_height = true;
			if ($min_height === null) {
				$min_height = $numeric_height . 'px';
			}
		}

		$min_height_attr = $min_height !== null ? esc_attr($min_height) : '';

		// Add auto-height class if enabled
		$container_class = $results->containerclass;
		if ($auto_height) {
			$container_class .= ' hype-auto-height';
			
			// Enqueue the auto height script in the head (false = in header, not footer)
			wp_enqueue_script(
				'hypeanimations-auto-height',
				plugins_url('/js/hype-auto-height.js', dirname(__FILE__)),
				array(),
				filemtime(plugin_dir_path(dirname(__FILE__)) . '/js/hype-auto-height.js'),
				false
			);
			
			// Add inline style for better responsive behavior
			$inline_style = '
			.hype-auto-height {
				overflow: hidden;
				position: relative;
			}
			.hype-auto-height .HYPE_document {
				margin: 0 !important;
				position: relative !important;
				width: 100% !important;
			}';
			
			// Register a base style if needed, then add our inline style
			if (!wp_style_is('hypeanimations-base-style', 'registered')) {
				wp_register_style('hypeanimations-base-style', false);
				wp_enqueue_style('hypeanimations-base-style');
			}
			wp_add_inline_style('hypeanimations-base-style', $inline_style);
		}
		
		// Determine container type (div or iframe) based on embedmode parameter or default setting
		$container_type = $results->container;
		if ($embed_mode === 'div') {
			$container_type = 'div';
		} else if ($embed_mode === 'iframe') {
			$container_type = 'iframe';
		}
		
		// Setup dimensions and styles differently based on container type
		if ($container_type == 'iframe') {
			// For iframes, handle responsive and auto-height directly on the iframe element
			$iframe_attrs = '';
			$iframe_style = 'border:none;';
			
			// Width handling for iframe
			if ($width) {
				if ($is_responsive) {
					// For responsive iframes, use inline style with 100% width
					$iframe_style .= 'width:100%;';
				} else {
					// For non-responsive, use width attribute
					$iframe_attrs .= ' width="'.esc_attr($width).'"';
				}
			}
			
			 // Calculate default height
			$default_height = '300px'; // Fallback if no height info available
			
			// Calculate default height based on aspect ratio if available
			if ($aspect_ratio > 0 && $is_responsive) {
				// For responsive iframes, we'll add a wrapper with padding-bottom technique
				$padding_percent = round($aspect_ratio * 100, 2);
				
				// We'll wrap the iframe in a responsive container
				$wrapper_style = 'position:relative;width:100%;padding-bottom:' . $padding_percent . '%;';
				if ($min_height_attr !== '') {
					$wrapper_style .= 'min-height:' . $min_height_attr . ';';
				}
				$iframe_style .= 'position:absolute;top:0;left:0;width:100%;height:100%;';
			}
			
			// Height handling for iframe
			if ($height && !$auto_height) {
				// For explicit height, use the provided height
				$iframe_attrs .= ' height="'.esc_attr($height).'"';
			} else if ($auto_height) {
				// For auto-height with no explicit height, set a reasonable default height
				// that will be adjusted by the script
				if ($numeric_height > 0) {
					$iframe_style .= 'height:' . $numeric_height . 'px;';
				} else {
					$iframe_style .= 'height:' . $default_height . ';';
				}
			} else if (!$height && $numeric_height > 0) {
				// If no height specified but we have original dimensions, use them
				$iframe_style .= 'height:' . $numeric_height . 'px;';
			} else {
				// Last resort default
				$iframe_style .= 'height:' . $default_height . ';';
			}

			if ($min_height_attr !== '' && strpos($iframe_style, 'min-height') === false) {
				$iframe_style .= 'min-height:' . $min_height_attr . ';';
			}
			
			// Build the complete style attribute
			$iframe_style_attr = ' style="' . $iframe_style . '"';
			
			// Generate a unique ID for this animation instance if using auto height or responsive
			$container_id = '';
			if ($auto_height || $is_responsive) {
				$container_id = 'hype-container-' . $actid . '-' . uniqid();
			}
			
			// Build iframe HTML with all attributes properly applied
			if (file_exists($upload_dir['basedir'].'/hypeanimations/'.$actid.'/index.html')) {
				$_src = esc_url_raw($upload_dir['baseurl']."/hypeanimations/".$actid."/index.html");
				
				// If we're using responsive with known aspect ratio, wrap in container
				if ($is_responsive && $aspect_ratio > 0) {
					$output .= '<div style="' . $wrapper_style . '">';
					$output .= '<iframe frameborder="0"' . $iframe_attrs . $iframe_style_attr . ' ' .
						($container_class != '' ? 'class="' . $container_class . '"' : '') .
						($container_id != '' ? ' id="' . $container_id . '"' : '') .
						' src="' . $_src . '"></iframe>';
					$output .= '</div>';
				} else {
					// Standard iframe without wrapper
					$output .= '<iframe frameborder="0"' . $iframe_attrs . $iframe_style_attr . ' ' .
						($container_class != '' ? 'class="' . $container_class . '"' : '') .
						($container_id != '' ? ' id="' . $container_id . '"' : '') .
						' src="' . $_src . '"></iframe>';
				}
			} else {
				// Similar handling for alternative source URL
				if ($is_responsive && $aspect_ratio > 0) {
					$output .= '<div style="' . $wrapper_style . '">';
					$output .= '<iframe frameborder="0"' . $iframe_attrs . $iframe_style_attr . ' ' .
						($container_class != '' ? 'class="' . $container_class . '"' : '') .
						($container_id != '' ? ' id="' . $container_id . '"' : '') .
						' src="' . esc_url_raw(site_url()) . '?just_hypeanimations=' . $actid . '"></iframe>';
					$output .= '</div>';
				} else {
					$output .= '<iframe frameborder="0"' . $iframe_attrs . $iframe_style_attr . ' ' .
						($container_class != '' ? 'class="' . $container_class . '"' : '') .
						($container_id != '' ? ' id="' . $container_id . '"' : '') .
						' src="' . esc_url_raw(site_url()) . '?just_hypeanimations=' . $actid . '"></iframe>';
				}
			}
		} else {
			// For div containers, use the traditional approach
			if ($type == 'fixed') {
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
			
			// Apply width to the container div if specified and responsive/auto-height is enabled
			$container_styles = array();
			if ($custom_width !== null) {
				$container_styles[] = 'width:' . esc_attr($custom_width);
			}
			if ($min_height_attr !== '') {
				$container_styles[] = 'min-height:' . $min_height_attr;
			}
			$container_style = '';
			if (!empty($container_styles)) {
				$container_style = ' style="' . implode(';', $container_styles) . ';"';
			}
			
			$output .= '<div' .
				($container_class != '' ? ' class="' . $container_class . '"' : '') .
				($container_id != '' ? ' id="' . $container_id . '"' : '') .
				$container_style .
				'>' . $code . '</div>';
		}
	}
	
	 
	
	return $output;
}
