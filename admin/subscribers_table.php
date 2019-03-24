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
    
    public function get_columns()
    {
        return $columns = array(
            'user_name'         => 'Name',
            'start_date'        => 'URL',
            'end_date'          => 'Status',
            'status'            => 'Created'
        );
    }
}
