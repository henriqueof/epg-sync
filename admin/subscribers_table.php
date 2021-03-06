<?php

if (!class_exists('WP_List_Table')) {
    require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

class Subscribers_List_Table extends WP_List_Table
{
    /**
    * Constructor, we override the parent to pass our own arguments
    * We usually focus on three parameters: singular and plural labels, as well as whether the class supports AJAX.
    */
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

    public function column_user_login($item)
    {
        // Build row actions
        $actions = array(
            'edit'      => sprintf('<a href="?page=%s&action=%s&source=%s">Edit</a>', $_REQUEST['page'], 'edit', $item['id']),
            'cancel'    => sprintf('<a href="?page=%s&action=%s&source=%s">Cancel</a>', $_REQUEST['page'], 'cancel', $item['id']),
        );

        // Return the title contents
        return sprintf(
            '%1$s <span style="color:silver">(id:%2$s)</span>%3$s',
            /*$1%s*/ $item['user_login'],
            /*$2%s*/ $item['ID'],
            /*$3%s*/ $this->row_actions($actions)
        );
    }
    
    public function get_columns()
    {
        return $columns = array(
            'user_login'    => 'Username',
            'display_name'  => 'Name',
            'user_email'    => 'E-mail',
            'start_date'    => 'Start',
            'end_date'      => 'End',
            'status'        => 'Status',
            'price'         => 'Price'
        );
    }

    public function get_sortable_columns()
    {
        $sortable_columns = array(
            'start_date'    => array('start_date', false),
            'end_date'      => array('end_date', false),
            'status'        => array('status', false)
        );

        return $sortable_columns;
    }

    private function table_data()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'epg_subscrition';
        $data = array();
        
        $data = $wpdb->get_results("SELECT * FROM wp_users LEFT JOIN $table_name ON wp_users.ID = $table_name.user_id", 'ARRAY_A');

        return $data;
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
