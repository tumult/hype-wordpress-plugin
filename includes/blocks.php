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

    // Localize script with animation data
    wp_register_script(
        'hypeanimations-block-editor-script',
        plugins_url('../build/index.js', __FILE__),
        array('wp-blocks', 'wp-element', 'wp-editor', 'wp-components', 'wp-i18n', 'wp-data', 'wp-server-side-render'),
        filemtime(plugin_dir_path(__FILE__) . '../build/index.js')
    );

    // Register block styles
    wp_register_style(
        'hypeanimations-block-editor-style',
        plugins_url('../build/editor.css', __FILE__),
        array('wp-edit-blocks'),
        filemtime(plugin_dir_path(__FILE__) . '../build/editor.css')
    );

    // Localize script with animation data - IMPORTANT: this is what passes data to the block editor
    wp_localize_script('hypeanimations-block-editor-script', 'hypeAnimationsData', array(
        'animations' => $animations_data,
        'defaultImage' => plugins_url('../images/hype-placeholder.png', __FILE__),
    ));

    // Make sure the script is enqueued in the editor
    wp_enqueue_script('hypeanimations-block-editor-script');
    wp_enqueue_style('hypeanimations-block-editor-style');

    // Register the block with block.json
    register_block_type(
        plugin_dir_path(dirname(__FILE__)) . 'blocks/animation',
        array(
            'render_callback' => 'hypeanimations_render_block',
            'editor_script' => 'hypeanimations-block-editor-script',
            'editor_style' => 'hypeanimations-block-editor-style',
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
    
    // Build the animations array
    foreach ($results as $animation) {
        $thumbnail_url = plugins_url('../images/hype-placeholder.png', __FILE__);
        
        // Only try to get thumbnail if container_id exists
        if ($has_container_id && !empty($animation->container_id)) {
            $upload_dir = wp_upload_dir();
            $potential_thumbnail = $upload_dir['basedir'] . '/hypeanimations/' . $animation->container_id . '/thumbnail.jpg';
            
            if (file_exists($potential_thumbnail)) {
                $thumbnail_url = $upload_dir['baseurl'] . '/hypeanimations/' . $animation->container_id . '/thumbnail.jpg';
            }
        }
        
        $animations[] = array(
            'id' => (int)$animation->id,
            'name' => $animation->nom,
            'thumbnail' => $thumbnail_url
        );
    }
    
    // error_log('Hype Animations Plugin: Found ' . count($animations) . ' animations');
    
    return $animations;
}

/**
 * Render the Hype Animation block on the frontend
 */
function hypeanimations_render_block($attributes) {
    if (empty($attributes['animationId'])) {
        return '';
    }
    
    // Debug log animation rendering
    // error_log('Hype Animations Plugin: Rendering block with animation ID ' . $attributes['animationId']);
    
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
    
    // Generate the output using the existing shortcode function
    $output = hypeanimations_anim($shortcode_atts);
    
    // Verify output was generated
    // if (empty($output)) {
    //     error_log('Hype Animations Plugin: No output generated for animation ID ' . $attributes['animationId']);
    // } else {
    //     error_log('Hype Animations Plugin: Successfully generated output for animation ID ' . $attributes['animationId']);
    // }
    
    return $output;
}

/**
 * Add block transform support
 */
function hypeanimations_block_transform_support() {
    if (!function_exists('wp_enqueue_script')) {
        return;
    }

    // Register the shortcode transform script
    wp_enqueue_script(
        'hypeanimations-shortcode-transform',
        plugins_url('../build/transform.js', __FILE__),
        array('wp-blocks', 'wp-element', 'wp-editor'),
        filemtime(plugin_dir_path(__FILE__) . '../build/transform.js'),
        true
    );
}
add_action('enqueue_block_editor_assets', 'hypeanimations_block_transform_support');

/**
 * Register block pattern - COMMENTED OUT FOR VERSION 2.0
 * Will be added back in future versions
 */
/* 
function hypeanimations_register_block_patterns() {
    if (!function_exists('register_block_pattern')) {
        return;
    }
    
    register_block_pattern(
        'tumult-hype-animations/featured-animation',
        array(
            'title' => __('Featured Hype Animation', 'tumult-hype-animations'),
            'description' => __('A Tumult Hype animation with a heading and description', 'tumult-hype-animations'),
            'content' => '<!-- wp:group {"className":"featured-hype-animation"} -->
                <div class="wp-block-group featured-hype-animation">
                    <!-- wp:heading {"textAlign":"center"} -->
                    <h2 class="has-text-align-center">' . __('Animation Title', 'tumult-hype-animations') . '</h2>
                    <!-- /wp:heading -->
                    
                    <!-- wp:tumult-hype-animations/animation {"animationId":1} /-->
                    
                    <!-- wp:paragraph {"align":"center"} -->
                    <p class="has-text-align-center">' . __('Add your animation description here', 'tumult-hype-animations') . '</p>
                    <!-- /wp:paragraph -->
                </div>
                <!-- /wp:group -->',
            'categories' => array('text'),
        )
    );
}
add_action('init', 'hypeanimations_register_block_patterns');
*/

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