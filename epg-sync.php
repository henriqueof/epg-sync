<?php
/**
 * Plugin Name: EPG Sync plugin
 * Description: Multi source EPG information aggregator plugin for WordPress
 * Author:      Carlos Henrique
 */

require_once 'admin/sources_table.php';

global $jal_db_version;
$jal_db_version = '1.0';

function jal_install()
{
    global $wpdb;
 
    $table_name = $wpdb->prefix . 'epg_sources';
    $charset_collate = $wpdb->get_charset_collate();
    
    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        source_name tinytext NOT NULL,
        source_url varchar(55) DEFAULT '' NOT NULL,
        source_registered datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
        source_updated datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
        source_status mediumint(3) DEFAULT 1 NOT NULL,
        PRIMARY KEY  (id)
        ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);

    add_option('jal_db_version', $jal_db_version);
}

function sources_table()
{
    ?>
    <div class='wrap'>
        <h2>EPG Sources</h2>
        <?php
            $sources_table = new Sources_List_Table();
    $sources_table->prepare_items();
    $sources_table->display(); ?>
    </div>
    <?php
}

function epg_options_page()
{
    add_menu_page(
        'EPG Sync',
        'EPG Sync',
        'manage_options',
        'epg_sync',
        'sources_table',
        plugin_dir_url(__FILE__) . 'images/null.png',
        20
    );
}

add_action('admin_menu', 'epg_options_page');
register_activation_hook(__FILE__, 'jal_install');
