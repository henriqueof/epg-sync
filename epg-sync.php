<?php
/**
 * Plugin Name: EPG Sync plugin
 * Description: Multi source EPG information aggregator plugin for WordPress
 * Author:      Carlos Henrique
 */

require_once 'admin/sources_table.php';
require_once 'admin/subscribers_table.php';
require_once 'admin/log_table.php';

global $jal_db_version;
$jal_db_version = '1.0';

function jal_install()
{
    global $wpdb;
 
    $table_name = $wpdb->prefix . 'epg_sources';
    $charset_collate = $wpdb->get_charset_collate();
    
    // TODO: add othe tables migration
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

function run_epg_grabber()
{
    global $wpdb;
    $wg_path = '/home/henriqueof/.wg++/run.sh';
    $table_name = $wpdb->prefix . 'epg_wg_log';
    $output;
    $data = array();

    $data['start_date'] = date("Y-m-d H:i:s");

    exec($wg_path, $output);

    $data['end_date'] = date("Y-m-d H:i:s");
    $data['log'] = implode("\n", $output);

    // Store capture information to database
    if (strpos(end($output), 'Job finished') !== false) {
        $data['result'] = 'SUCCESS';
    } else {
        $data['result'] = 'FAILURE';
    }
    
    $wpdb->insert($table_name, $data);
}

function load_config_data()
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'epg_channel';
    $data = array();

    $base_dir = '/home/henriqueof/.wg++/siteini.pack';
    $countries = array_diff(scandir($base_dir), array('..', '.'));

    foreach ($countries as $country) {
        if (is_dir($base_dir . DIRECTORY_SEPARATOR . $country)) {
            $sites = preg_grep('~\.(channels.xml)$~', scandir($base_dir . DIRECTORY_SEPARATOR  . $country));
            
            foreach ($sites as $site) {
                $xml = simplexml_load_file($base_dir . DIRECTORY_SEPARATOR . $country . DIRECTORY_SEPARATOR . $site);
                
                if (is_null($xml->channels)) {
                    continue;
                }
                
                foreach ($xml->channels->children() as $channel) {
                    $data['xmltv_id'] = $channel['xmltv_id'];
                    $data['display_name'] = $channel;
                    $data['site'] = $channel['site'];
                    $data['site_id'] = $channel['site_id'];
                    $data['country'] = $country;

                    $wpdb->replace($table_name, $data);
                }
            }
        }
    }
}

function sources_table()
{
    // load_config_data();
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

function subscribers_table()
{
    ?>
    <div class='wrap'>
        <h2>Subscribers</h2>
        <?php
            $subscribers_table = new Subscribers_List_Table();
    $subscribers_table->prepare_items();
    $subscribers_table->display(); ?>
    </div>
    <?php
}

function log_table()
{
    ?>
    <div class='wrap'>
        <h2>WebGrabber+ logs</h2>
        <?php
            $log_table = new Log_List_Table();
    $log_table->prepare_items();
    $log_table->display(); ?>
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

    add_submenu_page(
        'epg_sync',
        'Subscribers',
        'Subscribers',
        'manage_options',
        'epg_subescriber',
        'subscribers_table'
    );
    
    add_submenu_page(
        'epg_sync',
        'Programme data',
        'Programme data',
        'manage_options',
        'epg_programme',
        'sources_table'
    );

    add_submenu_page(
        'epg_sync',
        'WebGrabber log',
        'WebGrabber log',
        'manage_options',
        'epg_log',
        'log_table'
    );

    remove_submenu_page('epg_sync', 'epg_sync');
}

add_action('admin_menu', 'epg_options_page');
register_activation_hook(__FILE__, 'jal_install');
