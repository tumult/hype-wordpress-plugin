<?php
add_action('plugins_loaded', 'hypeanimations_init');
function hypeanimations_init() {
    global $wpdb;
    global $hypeanimations_db_version;
    global $hypeanimations_table_name;
    $installed_ver = get_option("hypeanimations_db_version");
    $charset_collate = $wpdb->get_charset_collate();

    // run this if version is less than 1.9.14. Version 1.9.14 will be the first version to include the notes column. 
    if (version_compare($installed_ver, '1.9.14', '<')) {
    
        $sql = "CREATE TABLE $hypeanimations_table_name (
            id int(9) NOT NULL AUTO_INCREMENT,
            nom varchar(150) DEFAULT '' NOT NULL,
            slug varchar(150) DEFAULT '' NOT NULL,
            code text NOT NULL,
            updated INT(11) NOT NULL,
            container ENUM('none','div','iframe') NOT NULL,
            containerclass VARCHAR(150) NOT NULL,
            notes text NOT NULL,
            UNIQUE KEY id (id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        update_option("hypeanimations_db_version", $hypeanimations_db_version);
    }
}

function hypeanimations_install() {
    global $wpdb;
    global $hypeanimations_db_version;
    global $hypeanimations_table_name;
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $hypeanimations_table_name (
        id int(9) NOT NULL AUTO_INCREMENT,
        nom varchar(150) DEFAULT '' NOT NULL,
        slug varchar(150) DEFAULT '' NOT NULL,
        code text NOT NULL,
        updated INT(11) NOT NULL,
        container ENUM('none','div','iframe') NOT NULL,
        containerclass VARCHAR(150) NOT NULL,
        notes text NOT NULL,
        UNIQUE KEY id (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
    add_option('hypeanimations_db_version', $hypeanimations_db_version);
}

register_activation_hook(__FILE__, 'hypeanimations_install');