<?php
/**
 * Development helper: force reload of plugin translations when visiting
 * an admin URL with ?reloadtranslations=1 (or ?reloadtranslations).
 *
 * Safety: only available to users with 'manage_options'. This file is intended
 * for development only. Do not expose to untrusted users on production.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Abort when not in WP context.
}

add_action( 'admin_init', function() {
    // Only trigger when the query param is present (value doesn't matter)
    if ( ! isset( $_GET['reloadtranslations'] ) ) {
        return;
    }

    // Require capability to avoid accidental/untrusted reloads
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    $domain = 'tumult-hype-animations';

    // Unload existing translations (allow reload)
    if ( function_exists( 'is_textdomain_loaded' ) && is_textdomain_loaded( $domain ) ) {
        unload_textdomain( $domain, true );
    }

    // Determine best .mo path: prefer WP_LANG_DIR/plugins, fallback to plugin languages
    $locale = function_exists( 'determine_locale' ) ? determine_locale() : get_locale();
    $mofile = sprintf( '%s-%s.mo', $domain, $locale );

    $wp_lang_mofile = WP_LANG_DIR . '/plugins/' . $mofile;

    // Resolve plugin root languages folder (this file lives in includes/)
    $plugin_mofile = dirname( dirname( __FILE__ ) ) . '/languages/' . $mofile;

    $loaded = false;
    if ( file_exists( $wp_lang_mofile ) ) {
        $loaded = load_textdomain( $domain, $wp_lang_mofile );
    }

    if ( ! $loaded && file_exists( $plugin_mofile ) ) {
        $loaded = load_textdomain( $domain, $plugin_mofile );
    }

    // Admin notice via transient which will be printed on the next admin page load
    if ( $loaded ) {
        $source = file_exists( $wp_lang_mofile ) ? $wp_lang_mofile : $plugin_mofile;
        set_transient( 'hype_reload_translations_notice', sprintf( /* translators: %s: file path */ __( 'Translations reloaded from: %s', 'tumult-hype-animations' ), $source ), 10 );
    } else {
        set_transient( 'hype_reload_translations_notice', __( 'Translations reload attempted but failed (no .mo found)', 'tumult-hype-animations' ), 10 );
    }

    // Redirect to same URL without the param to avoid loops
    $redirect = remove_query_arg( 'reloadtranslations' );
    wp_safe_redirect( $redirect );
    exit;
} );

// Display the transient notice on next admin page load
add_action( 'admin_notices', function() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }
    $msg = get_transient( 'hype_reload_translations_notice' );
    if ( $msg ) {
        delete_transient( 'hype_reload_translations_notice' );
        echo '<div class="notice notice-success is-dismissible"><p>' . esc_html( $msg ) . '</p></div>';
    }
} );
