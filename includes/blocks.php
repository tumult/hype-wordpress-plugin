<?php
/**
 * Register Gutenberg blocks for Tumult Hype Animations
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register the Tumult Hype Animation block
 * 
 * This function registers the block and ensures it has the proper metadata
 * and render callback. It follows WordPress block best practices.
 */
function hypeanimations_register_blocks() {
    if (!function_exists('register_block_type')) {
        return;
    }

    $block_json_path = plugin_dir_path(dirname(__FILE__)) . 'blocks/animation/block.json';
    
    // Verify block.json exists
    if (!file_exists($block_json_path)) {
        error_log('Hype Animations: block.json not found at ' . $block_json_path);
        return;
    }

    register_block_type(
        $block_json_path,
        array(
            'render_callback' => 'hypeanimations_render_block',
        )
    );
}
add_action('init', 'hypeanimations_register_blocks');

/**
 * Enqueue block editor assets
 * 
 * Registers and enqueues the editor script, styles, and block data.
 * This follows WordPress best practices by:
 * - Checking for user capabilities
 * - Using proper asset versioning
 * - Localizing block data
 * - Providing fallback messages for missing build files
 */
function hypeanimations_enqueue_block_editor_assets() {
    // Check user capability
    if (!current_user_can('edit_posts')) {
        return;
    }

    $index_js_path = plugin_dir_path(__FILE__) . '../build/index.js';
    $index_css_path = plugin_dir_path(__FILE__) . '../build/index.css';

    // Check for required build files
    if (!file_exists($index_js_path) || !file_exists($index_css_path)) {
        hypeanimations_log_event('block_editor_assets_missing', array(
            'index_js' => $index_js_path,
            'index_css' => $index_css_path,
        ), 'warning');

        hypeanimations_register_block_editor_fallback();
        static $notice_registered = false;
        if (!$notice_registered) {
            add_action('admin_notices', 'hypeanimations_missing_build_files_notice');
            $notice_registered = true;
        }
        return;
    }

    // Define script dependencies
    $script_dependencies = array(
        'wp-blocks',
        'wp-element',
        'wp-block-editor',
        'wp-components',
        'wp-i18n',
        'wp-data',
    );

    // Register editor script
    if (!wp_script_is('tumult-hype-animations-editor', 'registered')) {
        wp_register_script(
            'tumult-hype-animations-editor',
            plugins_url('../build/index.js', __FILE__),
            $script_dependencies,
            filemtime($index_js_path),
            true
        );
    }

    // Register editor style
    if (!wp_style_is('tumult-hype-animations-editor', 'registered')) {
        wp_register_style(
            'tumult-hype-animations-editor',
            plugins_url('../build/index.css', __FILE__),
            array('wp-edit-blocks'),
            filemtime($index_css_path)
        );
    }

    // Get animation data for the editor
    $animations_data = hypeanimations_get_animations_for_gutenberg();

    hypeanimations_log_event('block_editor_data_loaded', array(
        'count' => count($animations_data),
    ));

    // Localize script with animation data
    wp_localize_script(
        'tumult-hype-animations-editor',
        'hypeAnimationsData',
        array(
            'animations' => $animations_data,
            'defaultImage' => plugins_url('../images/hype-placeholder.png', __FILE__),
            'dashboardUrl' => admin_url('admin.php?page=hypeanimations_panel'),
            'debug' => hypeanimations_should_log(),
            'version' => '2.0.0',
            'apiVersion' => 3,
        )
    );

    // Enqueue the script and style
    wp_enqueue_script('tumult-hype-animations-editor');
    wp_enqueue_style('tumult-hype-animations-editor');
}
add_action('enqueue_block_editor_assets', 'hypeanimations_enqueue_block_editor_assets');

function hypeanimations_missing_build_files_notice() {
    if (current_user_can('manage_options')) {
        echo '<div class="error"><p>' . esc_html__('Hype Animations plugin: Build files are missing. Please run `npm install` and `npm run build` in the plugin directory.', 'tumult-hype-animations') . '</p></div>';
    }
}

/**
 * Register a lightweight fallback script when build assets are missing.
 */
function hypeanimations_register_block_editor_fallback() {
    static $registered = false;

    if ($registered) {
        return;
    }

    $registered = true;

    $dependencies = array('wp-blocks', 'wp-element', 'wp-i18n', 'wp-data');

    if (!wp_script_is('tumult-hype-animations-editor', 'registered')) {
        wp_register_script(
            'tumult-hype-animations-editor',
            '',
            $dependencies,
            false,
            true
        );
    }

    $notice_message = esc_js(
        __('Tumult Hype Animations build assets are missing. Please run npm run build before editing this block.', 'tumult-hype-animations')
    );

    $inline_script = "window.hypeAnimationsData = window.hypeAnimationsData || { animations: [], defaultImage: '', debug: false };\n\n    wp.domReady(function() {\n        if (window.wp && wp.data && wp.data.dispatch) {\n            var dispatcher = wp.data.dispatch('core/notices');\n            if (dispatcher && dispatcher.createNotice) {\n                dispatcher.createNotice('error', '" . $notice_message . "', {\n                    id: 'tumult-hype-animations-missing-build',\n                    isDismissible: false\n                });\n            }\n        }\n\n        if (window.wp && wp.blocks && wp.blocks.unregisterBlockType) {\n            wp.blocks.unregisterBlockType('tumult-hype-animations/animation');\n        }\n    });";

    wp_add_inline_script('tumult-hype-animations-editor', $inline_script);

    if (!wp_style_is('tumult-hype-animations-editor', 'registered')) {
        wp_register_style('tumult-hype-animations-editor', false, array(), false);
    }

    wp_enqueue_script('tumult-hype-animations-editor');
    wp_enqueue_style('tumult-hype-animations-editor');
}

/**
 * Get animations for Gutenberg selector
 */
function hypeanimations_get_animations_for_gutenberg() {
    global $wpdb, $hypeanimations_table_name;
    
    $animations = array();
    
    // Check if the table exists first
    if ($wpdb->get_var("SHOW TABLES LIKE '$hypeanimations_table_name'") != $hypeanimations_table_name) {
        hypeanimations_log_event('block_table_missing', array(
            'table' => $hypeanimations_table_name,
        ), 'warning');
        return $animations;
    }
    
    // Get the actual columns from the table
    $table_columns = $wpdb->get_col("DESC $hypeanimations_table_name", 0);
    
    // Base query with default needed columns
    $query = "SELECT id, nom";
    
    // Check if the container_id column exists
    $has_container_id = in_array('container_id', $table_columns);
    if ($has_container_id) {
        $query .= ", container_id";
    }
    
    // Check if updated column exists
    $has_updated = in_array('updated', $table_columns);
    if ($has_updated) {
        $query .= ", updated";
    }
    
    // Add sorting and finalize query
    $query .= " FROM $hypeanimations_table_name";
    if ($has_updated) {
        $query .= " ORDER BY updated DESC, id DESC";
    } else {
        $query .= " ORDER BY id DESC";
    }
    
    // Execute the query
    $results = $wpdb->get_results($query);
    
    if (empty($results)) {
        hypeanimations_log_event('block_no_animations_found', array(
            'table' => $hypeanimations_table_name,
        ), 'notice');
        return $animations;
    }
    
     
    
    // Build the animations array
    foreach ($results as $animation) {
        // Default placeholder image
        $thumbnail_url = plugins_url('../images/hype-placeholder.png', __FILE__);
        
        $animation_id = (int)$animation->id;
        $upload_dir = wp_upload_dir();
        
        // Check for our new Default_[ID].png thumbnail first
        $default_thumbnail = $upload_dir['basedir'] . '/hypeanimations/' . $animation_id . '/Default_' . $animation_id . '.png';
         
        
        if (file_exists($default_thumbnail)) {
            $thumbnail_url = $upload_dir['baseurl'] . '/hypeanimations/' . $animation_id . '/Default_' . $animation_id . '.png';
             
        }
        // If not found, fall back to older methods
        else {
            hypeanimations_log_event('thumbnail_not_found', array(
                'path' => $default_thumbnail,
                'animation_id' => $animation_id,
            ), 'notice');
            
            // Check for container_id based thumbnail (from older versions)
            if ($has_container_id && !empty($animation->container_id)) {
                $potential_thumbnail = $upload_dir['basedir'] . '/hypeanimations/' . $animation->container_id . '/thumbnail.jpg';
                hypeanimations_log_event('thumbnail_legacy_lookup', array(
                    'path' => $potential_thumbnail,
                    'animation_id' => $animation_id,
                    'container_id' => $animation->container_id,
                ), 'info');
                
                if (file_exists($potential_thumbnail)) {
                    $thumbnail_url = $upload_dir['baseurl'] . '/hypeanimations/' . $animation->container_id . '/thumbnail.jpg';
                    hypeanimations_log_event('thumbnail_legacy_found', array(
                        'url' => $thumbnail_url,
                        'animation_id' => $animation_id,
                        'container_id' => $animation->container_id,
                    ));
                }
            }
            
            // Check for a generic thumbnail.jpg in animation folder
            $generic_thumbnail = $upload_dir['basedir'] . '/hypeanimations/' . $animation_id . '/thumbnail.jpg';
            hypeanimations_log_event('thumbnail_generic_lookup', array(
                'path' => $generic_thumbnail,
                'animation_id' => $animation_id,
            ), 'info');
            
            if (file_exists($generic_thumbnail)) {
                $thumbnail_url = $upload_dir['baseurl'] . '/hypeanimations/' . $animation_id . '/thumbnail.jpg';
                hypeanimations_log_event('thumbnail_generic_found', array(
                    'url' => $thumbnail_url,
                    'animation_id' => $animation_id,
                ));
            }
        }
        
        // Get original dimensions from the index.html file
        $original_width = '';
        $original_height = '';
        $original_width_unit = '';
        $original_height_unit = '';
        $original_width_value = 0;
        $original_height_value = 0;
        $default_min_height = '';
        $index_html_path = $upload_dir['basedir'] . '/hypeanimations/' . $animation_id . '/index.html';
        
        if (file_exists($index_html_path)) {
            $index_html_content = file_get_contents($index_html_path);
            if (preg_match('/<div id="[^"]*_hype_container" class="HYPE_document" style="([^"]+)">/i', $index_html_content, $style_match)) {
                $style_block = $style_match[1];

                if (preg_match('/width:\s*([0-9.]+)\s*(px|%)/i', $style_block, $width_match)) {
                    $original_width_value = (float) $width_match[1];
                    $original_width_unit = strtolower($width_match[2]);
                    $original_width = $original_width_value . $original_width_unit;
                }

                if (preg_match('/height:\s*([0-9.]+)\s*(px|%)/i', $style_block, $height_match)) {
                    $original_height_value = (float) $height_match[1];
                    $original_height_unit = strtolower($height_match[2]);
                    $original_height = $original_height_value . $original_height_unit;
                }

                if ($original_height_unit === 'px' && $original_height_value > 0) {
                    $default_min_height = $original_height_value . 'px';
                }
            }
        }
        
        $animations[] = array(
            'id' => $animation_id,
            'name' => sanitize_text_field($animation->nom),
            'thumbnail' => $thumbnail_url,
            'originalWidth' => $original_width,
            'originalHeight' => $original_height,
            'originalWidthUnit' => $original_width_unit,
            'originalHeightUnit' => $original_height_unit,
            'originalWidthValue' => $original_width_value,
            'originalHeightValue' => $original_height_value,
            'defaultMinHeight' => $default_min_height,
        );
        
         
    }
    
    return $animations;
}

/**
 * Render the Hype Animation block on the frontend
 * 
 * This function converts block attributes to shortcode attributes and renders
 * the animation using the existing shortcode handler. This follows WordPress
 * best practices by leveraging existing rendering logic.
 * 
 * @param array $attributes The block attributes.
 * @return string The rendered HTML output, or empty string if invalid.
 */
function hypeanimations_render_block($attributes) {
    // Validate the animation ID
    $animation_id = isset($attributes['animationId']) ? absint($attributes['animationId']) : 0;
    if (empty($animation_id)) {
        return '';
    }
    
    // Build shortcode attributes from block attributes
    $shortcode_atts = array(
        'id' => $animation_id
    );
    
    // Optional: width
    if (!empty($attributes['width'])) {
        $shortcode_atts['width'] = sanitize_text_field($attributes['width']);
    }
    
    // Optional: height (unless autoHeight is enabled)
    if (!empty($attributes['height']) && !(isset($attributes['autoHeight']) && $attributes['autoHeight'])) {
        $shortcode_atts['height'] = sanitize_text_field($attributes['height']);
    }
    
    // Optional: auto height
    if (isset($attributes['autoHeight']) && $attributes['autoHeight']) {
        $shortcode_atts['auto_height'] = '1';
    }
    
    // Optional: minimum height
    if (!empty($attributes['minHeight'])) {
        $shortcode_atts['min_height'] = sanitize_text_field($attributes['minHeight']);
    }
    
    // Optional: embed mode (div or iframe)
    if (isset($attributes['embedMode'])) {
        $valid_modes = array('div', 'iframe');
        $embed_mode = sanitize_text_field($attributes['embedMode']);
        if (in_array($embed_mode, $valid_modes, true)) {
            $shortcode_atts['embedmode'] = $embed_mode;
        }
    }
    
    /**
     * Filter the shortcode attributes generated from the block attributes.
     *
     * @param array $shortcode_atts The shortcode attributes.
     * @param array $attributes     Original block attributes.
     */
    $shortcode_atts = apply_filters(
        'hypeanimations_render_block_shortcode_atts',
        $shortcode_atts,
        $attributes
    );

    /**
     * Filter the shortcode handler used to render the block output.
     *
     * @param callable|string $handler        The shortcode handler callable.
     * @param array            $shortcode_atts The shortcode attributes.
     * @param array            $attributes     Original block attributes.
     */
    $shortcode_handler = apply_filters(
        'hypeanimations_render_block_shortcode_handler',
        'hypeanimations_anim',
        $shortcode_atts,
        $attributes
    );

    // Verify the handler is callable
    if (!is_callable($shortcode_handler)) {
        return '';
    }

    // Generate the output using the shortcode handler
    $output = call_user_func($shortcode_handler, $shortcode_atts);

    /**
     * Filter the rendered block output before it is returned.
     *
     * @param string $output         Rendered markup.
     * @param array  $shortcode_atts Shortcode attributes.
     * @param array  $attributes     Original block attributes.
     */
    $output = apply_filters(
        'hypeanimations_render_block_output',
        $output,
        $shortcode_atts,
        $attributes
    );

    return $output;
}

/**
 * Add block transform support
 */
function hypeanimations_block_transform_support() {
    if (!function_exists('wp_enqueue_script')) {
        return;
    }

    if (!current_user_can('edit_posts')) {
        return;
    }

    // Check if transform file exists
    $transform_js_path = plugin_dir_path(__FILE__) . '../build/transform.js';
    if (!file_exists($transform_js_path)) {
        hypeanimations_log_event('block_transform_asset_missing', array(
            'transform_js' => $transform_js_path,
        ), 'warning');
        return;
    }

    // Register the shortcode transform script
    wp_enqueue_script(
        'tumult-hype-animations-transform',
        plugins_url('../build/transform.js', __FILE__),
        array('wp-blocks', 'wp-element', 'wp-block-editor'),
        filemtime($transform_js_path),
        true
    );
}
add_action('enqueue_block_editor_assets', 'hypeanimations_block_transform_support');

/**
 * Register block patterns
 */
function hypeanimations_register_block_patterns() {
    // Block patterns require WP 5.5+
    if (!function_exists('register_block_pattern')) {
        return;
    }
    
    // Register the patterns category first
    if (function_exists('register_block_pattern_category')) {
        register_block_pattern_category(
            'tumult-hype-animations',
            array('label' => __('Tumult Hype Animations', 'tumult-hype-animations'))
        );
    }
    
    // Register a featured animation pattern
    register_block_pattern(
        'tumult-hype-animations/featured-animation',
        array(
            'title' => __('Featured Hype Animation', 'tumult-hype-animations'),
            'description' => __('A Tumult Hype animation with a heading and description', 'tumult-hype-animations'),
            'categories' => array('tumult-hype-animations'),
            'keywords' => array('animation', 'hype', 'tumult'),
            'content' => '<!-- wp:group {"className":"featured-hype-animation","layout":{"type":"constrained"}} -->
<div class="wp-block-group featured-hype-animation">
    <!-- wp:heading {"textAlign":"center"} -->
    <h2 class="has-text-align-center">' . __('Animation Title', 'tumult-hype-animations') . '</h2>
    <!-- /wp:heading -->
    
    <!-- wp:tumult-hype-animations/animation {"animationId":1,"width":"100%"} /-->
    
    <!-- wp:paragraph {"align":"center"} -->
    <p class="has-text-align-center">' . __('Animation description', 'tumult-hype-animations') . '</p>
    <!-- /wp:paragraph -->
</div>
<!-- /wp:group -->',
        )
    );
    
    // Register a sidebar animation pattern
    register_block_pattern(
        'tumult-hype-animations/sidebar-animation',
        array(
            'title' => __('Sidebar Hype Animation', 'tumult-hype-animations'),
            'description' => __('A Tumult Hype animation designed for sidebars', 'tumult-hype-animations'),
            'categories' => array('tumult-hype-animations'),
            'keywords' => array('animation', 'hype', 'sidebar', 'widget'),
            'content' => '<!-- wp:group {"className":"sidebar-hype-animation","layout":{"type":"constrained"}} -->
<div class="wp-block-group sidebar-hype-animation">
    <!-- wp:heading {"level":3,"textAlign":"center"} -->
    <h3 class="has-text-align-center">' . __('Sidebar Animation', 'tumult-hype-animations') . '</h3>
    <!-- /wp:heading -->

    <!-- wp:tumult-hype-animations/animation {"animationId":1,"width":"100%","autoHeight":true} /-->
    
    <!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
    <div class="wp-block-buttons">
        <!-- wp:button -->
        <div class="wp-block-button"><a class="wp-block-button__link">' . __('Learn More', 'tumult-hype-animations') . '</a></div>
        <!-- /wp:button -->
    </div>
    <!-- /wp:buttons -->
</div>
<!-- /wp:group -->'
        )
    );
}
add_action('init', 'hypeanimations_register_block_patterns');

/**
 * Register block collection
 */
function hypeanimations_register_block_collection() {
    // This function requires WordPress 5.8 or later
    if (!function_exists('register_block_collection') || version_compare($GLOBALS['wp_version'], '5.8', '<')) {
        return;
    }
    
    register_block_collection(
        'tumult-hype-animations',
        array(
            'title' => 'Tumult Hype',
            'icon' => 'video-alt2',
        )
    );
}
add_action('init', 'hypeanimations_register_block_collection');
