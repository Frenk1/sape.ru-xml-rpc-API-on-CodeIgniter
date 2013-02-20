<?
defined('BASEPATH') OR exit('No direct script access allowed');

class Sape extends CI_Controller {

    public $_sapeapi;
    public $CI;

    public function __construct() {
        parent::__construct();
        $this->CI =& get_instance();

        # инициализация библиотеки для работы с запросами xml-rpc sape.ru
        $auth_data = array(
                'login' => 'login here',
                'password' => 'md5 hash here' // md5 hash
            );
        
        $this->load->database();
        $this->load->dbforge();
        $this->load->library('SapeApiLoader');
        $this->load->library('SapeApi', $auth_data);
        $this->_sapeapi = $this->sapeapi->set_debug(0);

        // $user = $this
        //         ->_sapeapi
        //         ->query('sape.get_user')
        //         ->db_fields(true)
        //         ->xml_cache(3600)
        //         ->send()
        //         ->get_xml();
        // // var_dump( $user );


        // $balance_real = $this
        //         ->_sapeapi
        //         ->query('sape.get_balance_real')
        //         ->xml_cache()
        //         ->send()
        //         ->get_xml();

        // $get_balance_locks = $this
        //         ->_sapeapi
        //         ->query('sape.get_balance_locks')
        //         ->xml_cache()
        //         ->send()
        //         ->get_xml();

/************************************/

        // $balance = $this
        //         ->_sapeapi
        //         ->query('sape.get_balance')
        //         ->db_fields(true)
        //         ->xml_cache(3600)
        //         ->send()
        //         ->get_xml();
        // // var_dump($balance);

        // $get_sites = $this
        //         ->_sapeapi
        //         ->query('sape.get_sites')
        //         ->db_fields(true)
        //         // ->xml_cache()
        //         ->send()
        //         ->get_xml();
        // var_dump($get_sites);


        // $get_sites_links_count = $this
        //         ->_sapeapi
        //         ->query('sape.get_sites_links_count')
        //         ->db_fields(true)
        //         ->xml_cache(3600)
        //         // ->flush_query_cache()
        //         ->send()
        //         ->get_xml();
        // var_dump($get_sites_links_count);

        // $get_sites_pages_count = $this
        //         ->_sapeapi
        //         ->query('sape.get_sites_pages_count')
        //         ->db_fields(true)
        //         ->xml_cache(300)
        //         // ->flush_query_cache()
        //         ->send()
        //         ->get_xml();
        // var_dump($get_sites_pages_count);

        // $get_site_money_stats = $this
        //         ->_sapeapi
        //         ->query('sape.get_site_money_stats', array(998242, 2013, 1))
        //         ->db_fields(false)
        //         ->xml_cache(3600)
        //         // ->flush_query_cache()
        //         ->send()
        //         ->get_xml();
        // var_dump($get_site_money_stats);

    }

    /**
    * ремаппинг методов роутинга, чтобы легче было отличать функции от страниц
    * Методы, которые имеют префикс "action_" - являются страницами, которые можно запросить через браузер
    */
    public function _remap($method, $params = array()) {
        $method = 'action_' . $method;
        if (method_exists($this, $method)) {
            return call_user_func_array(array($this, $method), $params);
        }
        show_404();
    }

    function action_index() {
        // костыль. Если есть параметры, когда они не нужны - то показать 404
        if (func_num_args()) {
            show_404();
        }
    }

    function action_get_sites() {
        // iso8601_decode(date)
        $get_sites = $this
                ->_sapeapi
                ->query('sape.get_sites')
                ->db_fields(true)
                ->xml_cache(3600)
                ->send();

        echo $this->Get_sites->get_json();
    }

    function action_links($id = '', $status = false) {
        if (!$id) {
            show_404();
        }

        $get_site_links = $this->_sapeapi
                ->query('sape.get_site_links', array($id))
                ->db_fields(true);
                // ->query_select('id, url')

        if ($status) {
            $get_site_links = $get_site_links->query_where(array('status' => $status));
        }

        $get_site_links = $get_site_links->xml_cache(500)->send();

        echo $this->Get_site_links->get_json();
        log_message('debug', 'Sape controller - action_orders');
    }
}