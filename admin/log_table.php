<?php

if (!class_exists('WP_List_Table')) {
    require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

class Log_List_Table extends WP_List_Table
{
    public function __construct()
    {
        parent::__construct(
            array(
            'singular'  => 'log',
            'plural'    => 'logs',
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
            'start_date'    => 'Start',
            'end_date'      => 'End',
            'log'           => 'Log',
            'result'        => 'Result'
        );
    }

    private function table_data()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'epg_wg_log';
        $data = array();
        
        $wk_post = $wpdb->get_results("SELECT * FROM $table_name", 'ARRAY_A');

        return $wk_post;
    }

    public function prepare_items()
    {
        global $wpdb;
 
        $columns = $this->get_columns();
        $hidden = array();
 
        $data = $this->table_data();
        $totalitems = count($data);

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
