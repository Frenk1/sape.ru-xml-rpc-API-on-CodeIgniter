<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Sape extends CI_Controller {

    public $_sapeapi;
    public $_get_user;
    public $_model_messages;
    private $_manager;

    public function __construct() {
        parent::__construct();

        # инициализация библиотеки для работы с запросами xml-rpc sape.ru
        $auth_data = array(
                'login' => 'login here',
                'password' => 'md5 hash here' // md5 hash
            );
        
        $this->load->library('sapeapiloader');
        $this->load->library('sapeapi', $auth_data);
        $this->_sapeapi = $this->sapeapi->set_debug(0);

        $user = $this
                ->_sapeapi
                ->query('sape.get_user')
                ->xml_cache()
                ->fetch()
                ->get_xml();
    }

    /**
    * ремаппинг методов роутинга, чтобы легче было отличать функции от страниц
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
}