<?php
add_action( 'plugins_loaded', 'hypeanimations_init' );
add_action('plugins_loaded', 'hypeanimations_init');
function hypeanimations_init() {
    global $wpdb;
    global $hypeanimations_db_version;
    global $hypeanimations_table_name;
    $installed_ver = get_option("hypeanimations_db_version");
    $charset_collate = $wpdb->get_charset_collate();

    if (version_compare($installed_ver, '1.9.15', '<')) {
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

        // Update existing rows with an empty string for the 'notes' column
        //$wpdb->query("ALTER TABLE $hypeanimations_table_name MODIFY notes text DEFAULT '' NOT NULL");

        
        // $null_notes_rows = $wpdb->get_results("SELECT id, nom, slug, notes FROM $hypeanimations_table_name WHERE notes IS NULL", ARRAY_A);

        // if (!empty($null_notes_rows)) {
        //     error_log('Rows with null values in the "notes" column:');
        //     foreach ($null_notes_rows as $row) {
        //         $log_message = sprintf(
        //             'ID: %d, Name: %s, Slug: %s, Notes: %s',
        //             $row['id'],
        //             $row['nom'],
        //             $row['slug'],
        //             $row['notes']
        //         );
        //         error_log($log_message);
        //     }
        // } else {
        //     error_log('No rows found with null values in the "notes" column.');
        // }


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