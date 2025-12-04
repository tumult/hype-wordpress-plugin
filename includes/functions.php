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

/**
 * Determine whether plugin logging should be enabled.
 *
 * @return bool
 */
function hypeanimations_should_log() {
  $should_log = defined('WP_DEBUG') && WP_DEBUG;

  return (bool) apply_filters('hypeanimations_should_log', $should_log);
}

/**
 * Trigger an observable log event with optional context.
 *
 * @param string $message Human readable message.
 * @param array  $context Structured context data.
 * @param string $level   Log severity (info, warning, error).
 *
 * @return void
 */
function hypeanimations_log_event($message, $context = array(), $level = 'info') {
  /**
   * Fires whenever the plugin records a log event.
   *
   * @param string $message
   * @param array  $context
   * @param string $level
   */
  do_action('hypeanimations_log_event', $message, $context, $level);

  if (!hypeanimations_should_log()) {
    return;
  }

  $prefix = sprintf('[hypeanimations][%s] ', strtoupper($level));
  $payload = $message;

  if (!empty($context)) {
    $payload .= ' ' . wp_json_encode($context);
  }

  error_log($prefix . $payload);
}

function hyperrmdir($dir) {
  if (is_dir($dir)) {
    $objects = scandir($dir);
    foreach ($objects as $object) {
      if ($object != "." && $object != "..") {
        if (filetype($dir."/".$object) == "dir") hyperrmdir($dir."/".$object); else wp_delete_file($dir."/".$object);
      }
    }
    reset($objects);
    rmdir($dir);
  }
}
function hypeanimations_admin_style() {
  wp_register_style('hypeanimations_admin_css', plugins_url( '../css/hypeanimations.css', __FILE__ ), false, '1.9.11' );
  wp_enqueue_style('hypeanimations_admin_css');
  wp_register_style('dataTables_admin_css', plugins_url( '../css/jquery.dataTables.min.css', __FILE__ ), false, '1.0.2' );
  wp_enqueue_style('dataTables_admin_css');
  wp_register_style('custom.modified,jquery-ui_admin_css', plugins_url( '../css/jquery-ui.css', __FILE__ ), false, '1.0.1' );
  wp_enqueue_style('custom.modified,jquery-ui_admin_css');
  wp_register_style('custom.modified,dropzone_css', plugins_url( '../css/dropzone.css', __FILE__ ), false, '1.0.3' );
  wp_enqueue_style('custom.modified,dropzone_css');
  wp_enqueue_script( 'jquery_datatable_hype', plugins_url( '../js/jquery.dataTables.min.js', __FILE__ ), false, '1.0' );
  wp_enqueue_script( 'dropzone_hype', plugins_url( '../js/dropzone.js', __FILE__ ), false, '1.0.0' );
}

function hypeanimations_generate_thumbnail($animation_id) {
    $upload_dir = wp_upload_dir();
    $animation_dir_path = $upload_dir['basedir'] . '/hypeanimations/' . $animation_id . '/';
    $index_html_path = $animation_dir_path . 'index.html';
    $thumbnail_path = $animation_dir_path . 'thumbnail.png';

    if (!file_exists($index_html_path)) {
        return new WP_Error('file_not_found', __('index.html not found.', 'tumult-hype-animations'));
    }

    // Check for wkhtmltoimage
    $command = 'wkhtmltoimage -V';
    $output = shell_exec($command);

    if (strpos($output, 'wkhtmltoimage') !== false) {
        // wkhtmltoimage is available
        $command = sprintf('wkhtmltoimage --quality 50 --width 200 -f png %s %s', escapeshellarg($index_html_path), escapeshellarg($thumbnail_path));
        shell_exec($command);

        if (file_exists($thumbnail_path)) {
            return true;
        } else {
            return new WP_Error('thumbnail_generation_failed', __('Thumbnail generation failed using wkhtmltoimage.', 'tumult-hype-animations'));
        }
    } else {
        return new WP_Error('wkhtmltoimage_not_found', __('wkhtmltoimage not found. Please install it to generate thumbnails.', 'tumult-hype-animations'));
    }
}

/**
 * Detect if an animation needs autoheight based on its layout dimensions
 * Returns true if height is 100% or not explicitly set
 *
 * @param int $animation_id The animation ID to check
 * @return bool True if animation would benefit from autoheight, false otherwise
 */
function hypeanimations_needs_autoheight($animation_id) {
    $upload_dir = wp_upload_dir();
    $index_html_path = $upload_dir['basedir'] . '/hypeanimations/' . $animation_id . '/index.html';
    
    if (!file_exists($index_html_path)) {
        return false;
    }
    
    $index_html_content = file_get_contents($index_html_path);
    
    // Look for the Hype container style attribute (multiple possible patterns)
    // Pattern 1: _hype_container with HYPE_document class
    if (preg_match('/<div[^>]*id="[^"]*_hype_container"[^>]*class="HYPE_document"[^>]*style="([^"]+)">/i', $index_html_content, $style_match)) {
        $style_block = $style_match[1];
        
        // Check if height is set to 100% (responsive)
        if (preg_match('/height:\s*100%/i', $style_block)) {
            return true;
        }
        
        // Check if height attribute is missing entirely (also responsive)
        if (!preg_match('/height:/i', $style_block)) {
            return true;
        }
    }
    
    // Pattern 2: Alternative pattern - HYPE_document first
    if (preg_match('/<div[^>]*class="HYPE_document"[^>]*id="[^"]*_hype_container"[^>]*style="([^"]+)">/i', $index_html_content, $style_match)) {
        $style_block = $style_match[1];
        
        if (preg_match('/height:\s*100%/i', $style_block)) {
            return true;
        }
        
        if (!preg_match('/height:/i', $style_block)) {
            return true;
        }
    }
    
    return false;
}

add_action( 'admin_enqueue_scripts', 'hypeanimations_admin_style' );

/**
 * Enqueue auto-height script on frontend
 * This ensures the script is available for any animations with the hype-auto-height class
 */
function hypeanimations_enqueue_frontend_assets() {
    // Only enqueue on frontend, not admin
    if (is_admin()) {
        return;
    }
    
    // Check if any posts/pages contain animations with hype-auto-height class
    // For now, we'll always enqueue it since it's lightweight and doesn't interfere
    wp_enqueue_script(
        'hypeanimations-auto-height',
        plugins_url('/js/hype-auto-height.js', dirname(__FILE__)),
        array(),
        filemtime(plugin_dir_path(__FILE__) . '/hype-auto-height.js'),
        false // Load in header to ensure it's available early
    );
}
add_action('wp_enqueue_scripts', 'hypeanimations_enqueue_frontend_assets');
