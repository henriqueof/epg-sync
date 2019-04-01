<?php

if (!class_exists('WP_List_Table')) {
    require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

class Sources_List_Table extends WP_List_Table
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

    public function column_cb($item)
    {
        return sprintf(
            '<input type="checkbox" name="%1$s[]" value="%2$s" />',
            /*$1%s*/ $this->_args['singular'],  //Let's simply repurpose the table's singular label ("movie")
            /*$2%s*/
            $item['id']                //The value of the checkbox should be the record's id
        );
    }

    public function column_default($item, $column_name)
    {
        switch ($column_name) {
            case 'source_url':
                return $item[$column_name];
            default:
                return print_r($item, true); //Show the whole array for troubleshooting purposes
        }
    }

    public function column_country_name($item)
    {
        // Build row actions
        $actions = array(
            'edit'      => sprintf('<a href="?page=%s&action=%s&source=%s">Edit</a>', $_REQUEST['page'], 'edit', $item['id']),
            'delete'    => sprintf('<a href="?page=%s&action=%s&source=%s">Delete</a>', $_REQUEST['page'], 'delete', $item['id']),
        );

        // Return the title contents
        return sprintf(
            '%1$s <span style="color:silver">(id:%2$s)</span>%3$s',
            /*$1%s*/ $item['country_name'],
            /*$2%s*/ $item['id'],
            /*$3%s*/ $this->row_actions($actions)
        );
    }

    public function column_source_status($item)
    {
        switch ($item['source_status']) {
            case 1:
            return 'enabled';
            case 2:
            return 'disabled';
            case 3:
            return 'error';
        }
    }

    public function column_source_registered($item)
    {
        return sprintf('%1$s', date_i18n(get_option('date_format'), strtotime($item['source_registered'])));
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

    public function get_bulk_actions()
    {
        $actions = array(
            'update'    => 'Update'
        );
        return $actions;
    }

    public function process_bulk_action()
    {
        //Detect when a bulk action is being triggered...
        if ('update' === $this->current_action()) {
            wp_die('Items deleted (or they would be if we had items to delete)!');
        }
    }

    private function read_channel($channel)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'epg_channel';
        $data = array();

        $data['channel_id'] = $channel['id'];

        foreach ($channel->children() as $child) {
            switch ($child->getName()) {
                case 'display-name':
                    $data['display_name'] = $child;
                    $data['language'] = $child['lang'];
                break;
                case 'url':
                    $data['url'] = $child;
                break;
            }
        }
        
        $wpdb->replace($table_name, $data, array('%s', '%d'));
    }

    private function read_programme($programme)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'epg_programme';
        $data = array();

        $startDateObject = date_create_from_format('YmdHis O', $programme['start']);
        $stopDateObject = date_create_from_format('YmdHis O', $programme['stop']);

        $data['start_date'] = date('Y-m-d H:i:s', $startDateObject->getTimestamp());
        $data['end_date'] = date('Y-m-d H:i:s', $stopDateObject->getTimestamp());

        $data['channel_id'] = $programme['channel'];

        foreach ($programme->children() as $child) {
            switch ($child->getName()) {
                case 'title':
                    $data['title'] = $child;
                break;
                case 'desc':
                    $data['description'] = $child;
                break;
                case 'date':
                    $data['date'] = $child;
                break;
                case 'rating':
                    $data['rating'] = $child->children()[0];
                break;
                case 'episode-num':
                    $data['episode_num'] = $child;
                break;
            }
        }

        $wpdb->replace($table_name, $data);
    }

    private function table_data()
    {
        $xml = simplexml_load_file('/home/henriqueof/.wg++/guide.xml');

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
