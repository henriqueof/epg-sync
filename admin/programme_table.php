<?php

if (!class_exists('WP_List_Table')) {
    require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

class Programme_List_Table extends WP_List_Table
{
    public function __construct()
    {
        parent::__construct(
            array(
            'singular'  => 'source',
            'plural'    => 'sources',
            'ajax'      => false
            )
        );
    }

    public function column_default($item, $column_name)
    {
        return $item[$column_name];
    }

    public function get_columns()
    {
        return $columns = array(
            'cb'                => '<input type="checkbox" />',
            'source_url'        => 'URL',
            'country_name'      => 'Country',
            'source_status'     => 'Status',
            'source_registered' => 'Created'
        );
    }

    public function get_sortable_columns()
    {
        $sortable_columns = array(
            'source_name'       => array('source_name', false),
            'country_name'      => array('country_name', false),
            'source_registered' => array('source_registered', false)
        );
        return $sortable_columns;
    }

    private function table_data()
    {
        // Channel has many programme
        foreach ($xml->children() as $child) {
            switch ($child->getName()) {
                case 'channel':
                    $this->read_channel($child);
                break;
                case 'programme':
                    $this->read_programme($child);
                break;
            }
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'epg_data';
        $data = array();

        /*
        global $wpdb;
        $table_name = $wpdb->prefix . 'epg_sources';
        $data = array();

        if (isset($_GET['s'])) {
            $search=$_GET['s'];
            $search = trim($search);
            $wk_post = $wpdb->get_results("SELECT * FROM $table_name WHERE source_name LIKE '%$search%'", 'ARRAY_A');
        } else {
            $wk_post = $wpdb->get_results("SELECT * FROM $table_name", 'ARRAY_A');
        }
        */

        // ----
        $base_dir = '/home/henriqueof/.wg++/siteini.pack';
        $countries = array_diff(scandir($base_dir), array('..', '.'));

        foreach ($countries as $country) {
            if (is_dir($base_dir . DIRECTORY_SEPARATOR . $country)) {
                $sources = preg_grep('~\.(ini)$~', scandir($base_dir . DIRECTORY_SEPARATOR  . $country));

                foreach ($sources as $source) {
                    $wk_post[] = array('id' => 1, 'source_url' => substr($source, 0, -4), 'country_name' => $country, 'source_status' => 1, 'source_registered' => '');
                }
            }
        }

        return $wk_post;
    }

    public function prepare_items()
    {
        global $wpdb;
 
        $columns = $this->get_columns();
        $sortable = $this->get_sortable_columns();
        $hidden = array();

        $this->process_bulk_action();
 
        $data = $this->table_data();
        $totalitems = count($data);

        $user = get_current_user_id();

        $screen = get_current_screen();
        $option = $screen->get_option('per_page', 'option');
        $perpage = 10;

        $this->_column_headers = array($columns, $hidden, $sortable);
 
        if (empty($per_page) || $per_page < 1) {
            $per_page = $screen->get_option('per_page', 'default');
        }

        function usort_reorder($a, $b)
        {
            $orderby = (!empty($_REQUEST['orderby'])) ? $_REQUEST['orderby'] : 'id'; //If no sort, default to title
            $order = (!empty($_REQUEST['order'])) ? $_REQUEST['order'] : 'desc'; //If no order, default to asc
            $result = strcmp($a[$orderby], $b[$orderby]); //Determine sort order
 
            return ($order === 'asc') ? $result : -$result; //Send final sort direction to usort
        }

        usort($data, 'usort_reorder');

        $totalpages = ceil($totalitems / $perpage);
        $currentPage = $this->get_pagenum();
        $data = array_slice($data, (($currentPage - 1) * $perpage), $perpage);
 
        $this->items =$data;
        
        $this->set_pagination_args(array(
                "total_items" => $totalitems,
                "total_pages" => $totalpages,
                "per_page" => $perpage,
            ));
    }

    public function no_items()
    {
        _e('No record found in the database.', 'bx');
    }
}
