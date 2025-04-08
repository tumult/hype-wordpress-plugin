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
 */
function hypeanimations_register_blocks() {
    // Skip block registration if Gutenberg is not available
    if (!function_exists('register_block_type')) {
        return;
    }

    // Get animations data for debugging
    $animations_data = hypeanimations_get_animations_for_gutenberg();
    
    // Log the actual data that will be passed to JavaScript
    error_log('Hype Animations Data for Block Editor: ' . json_encode($animations_data));

    // Check if build files exist
    $index_js_path = plugin_dir_path(__FILE__) . '../build/index.js';
    $index_css_path = plugin_dir_path(__FILE__) . '../build/index.css';
    
    if (!file_exists($index_js_path)) {
        error_log('Hype Animations Plugin: index.js build file not found at ' . $index_js_path);
    }
    
    if (!file_exists($index_css_path)) {
        error_log('Hype Animations Plugin: index.css build file not found at ' . $index_css_path);
    }
    
    // Register block script
    wp_register_script(
        'tumult-hype-animations-editor',
        plugins_url('../build/index.js', __FILE__),
        array('wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n', 'wp-data', 'wp-server-side-render'),
        filemtime($index_js_path)
    );

    // Register block styles - use index.css which includes the editor styles
    wp_register_style(
        'tumult-hype-animations-editor',
        plugins_url('../build/index.css', __FILE__),
        array('wp-edit-blocks'),
        filemtime($index_css_path)
    );

    // Localize script with animation data - IMPORTANT: this is what passes data to the block editor
    wp_localize_script('tumult-hype-animations-editor', 'hypeAnimationsData', array(
        'animations' => $animations_data,
        'defaultImage' => plugins_url('../images/hype-placeholder.png', __FILE__),
    ));

    // Register the block with block.json - this is the recommended method per WP guidelines
    register_block_type(
        plugin_dir_path(dirname(__FILE__)) . 'blocks/animation',
        array(
            'render_callback' => 'hypeanimations_render_block',
            'editor_script' => 'tumult-hype-animations-editor',
            'editor_style' => 'tumult-hype-animations-editor',
        )
    );
}
add_action('init', 'hypeanimations_register_blocks');

/**
 * Get animations for Gutenberg selector
 */
function hypeanimations_get_animations_for_gutenberg() {
    global $wpdb, $hypeanimations_table_name;
    
    $animations = array();
    
    // Check if the table exists first
    if ($wpdb->get_var("SHOW TABLES LIKE '$hypeanimations_table_name'") != $hypeanimations_table_name) {
        error_log('Hype Animations Plugin: Table does not exist');
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
        error_log('Hype Animations Plugin: No animations found in database');
        return $animations;
    }
    
    error_log('Hype Animations Plugin: Found ' . count($results) . ' animations');
    
    // Build the animations array
    foreach ($results as $animation) {
        // Default placeholder image
        $thumbnail_url = plugins_url('../images/hype-placeholder.png', __FILE__);
        
        $animation_id = (int)$animation->id;
        $upload_dir = wp_upload_dir();
        
        // Check for our new Default_[ID].png thumbnail first
        $default_thumbnail = $upload_dir['basedir'] . '/hypeanimations/' . $animation_id . '/Default_' . $animation_id . '.png';
        error_log('Checking for thumbnail at: ' . $default_thumbnail);
        
        if (file_exists($default_thumbnail)) {
            $thumbnail_url = $upload_dir['baseurl'] . '/hypeanimations/' . $animation_id . '/Default_' . $animation_id . '.png';
            error_log('Found thumbnail: ' . $thumbnail_url);
        }
        // If not found, fall back to older methods
        else {
            error_log('Thumbnail not found at: ' . $default_thumbnail);
            
            // Check for container_id based thumbnail (from older versions)
            if ($has_container_id && !empty($animation->container_id)) {
                $potential_thumbnail = $upload_dir['basedir'] . '/hypeanimations/' . $animation->container_id . '/thumbnail.jpg';
                error_log('Checking for legacy thumbnail at: ' . $potential_thumbnail);
                
                if (file_exists($potential_thumbnail)) {
                    $thumbnail_url = $upload_dir['baseurl'] . '/hypeanimations/' . $animation->container_id . '/thumbnail.jpg';
                    error_log('Found legacy thumbnail: ' . $thumbnail_url);
                }
            }
            
            // Check for a generic thumbnail.jpg in animation folder
            $generic_thumbnail = $upload_dir['basedir'] . '/hypeanimations/' . $animation_id . '/thumbnail.jpg';
            error_log('Checking for generic thumbnail at: ' . $generic_thumbnail);
            
            if (file_exists($generic_thumbnail)) {
                $thumbnail_url = $upload_dir['baseurl'] . '/hypeanimations/' . $animation_id . '/thumbnail.jpg';
                error_log('Found generic thumbnail: ' . $thumbnail_url);
            }
        }
        
        // Get original dimensions from the index.html file
        $original_width = '';
        $original_height = '';
        $index_html_path = $upload_dir['basedir'] . '/hypeanimations/' . $animation_id . '/index.html';
        
        if (file_exists($index_html_path)) {
            $index_html_content = file_get_contents($index_html_path);
            if (preg_match('/<div id="[^"]*_hype_container" class="HYPE_document" style="[^"]*width:(\d+)px;height:(\d+)px;[^"]*">/i', $index_html_content, $matches)) {
                $original_width = $matches[1] . 'px';
                $original_height = $matches[2] . 'px';
                error_log('Found original dimensions for animation ' . $animation_id . ': ' . $original_width . ' x ' . $original_height);
            }
        }
        
        $animations[] = array(
            'id' => $animation_id,
            'name' => $animation->nom,
            'thumbnail' => $thumbnail_url,
            'originalWidth' => $original_width,
            'originalHeight' => $original_height
        );
        
        error_log('Animation ' . $animation_id . ' using thumbnail: ' . $thumbnail_url);
    }
    
    return $animations;
}

/**
 * Render the Hype Animation block on the frontend
 */
function hypeanimations_render_block($attributes) {
    if (empty($attributes['animationId'])) {
        return '';
    }
    
    // Create shortcode attributes from block attributes
    $shortcode_atts = array(
        'id' => $attributes['animationId']
    );
    
    if (!empty($attributes['width'])) {
        $shortcode_atts['width'] = $attributes['width'];
    }
    
    if (!empty($attributes['height']) && !(isset($attributes['autoHeight']) && $attributes['autoHeight'])) {
        $shortcode_atts['height'] = $attributes['height'];
    }
    
    if (isset($attributes['isResponsive'])) {
        $shortcode_atts['responsive'] = $attributes['isResponsive'] ? '1' : '0';
    }
    
    // If auto height is enabled, add the auto_height attribute
    if (isset($attributes['autoHeight']) && $attributes['autoHeight']) {
        $shortcode_atts['auto_height'] = '1';
    }
    
    // If embedMode is specified, pass it to the shortcode
    if (isset($attributes['embedMode'])) {
        $shortcode_atts['embedmode'] = $attributes['embedMode'];
    }
    
    // Generate the output using the existing shortcode function
    $output = hypeanimations_anim($shortcode_atts);
    
    return $output;
}

/**
 * Add block transform support
 */
function hypeanimations_block_transform_support() {
    if (!function_exists('wp_enqueue_script')) {
        return;
    }

    // Check if transform file exists
    $transform_js_path = plugin_dir_path(__FILE__) . '../build/transform.js';
    if (!file_exists($transform_js_path)) {
        error_log('Hype Animations Plugin: transform.js build file not found at ' . $transform_js_path);
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
    <p class="has-text-align-center">' . __('Add your animation description here', 'tumult-hype-animations') . '</p>
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