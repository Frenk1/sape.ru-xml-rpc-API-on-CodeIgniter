<?
defined('BASEPATH') OR exit('No direct script access allowed');

class Get_sites_links_count extends MY_Model {
    /**
    * поля для синхронизации результатов запроса sape xml-rpc
    */
    protected $_fields_params = array(
        'site_id' => array(
                'sync' => true,
                'type' => 'INT',
                'constraint' => 10,
            ),
        'nof' => array(
                'sync' => true,
                'type' => 'INT',
                'constraint' => 10,
            ),
        );
    /**
    * доступ к экземпляру одиночки (singletone)
    */
    public $CI;
    public $_table_name = false;
    public $_result_type = 'array';
    private $_ttl = 1;

    function __construct() {
        parent::__construct();
        $this->CI =& get_instance();
        $this->_table_name = __CLASS__;

        if ($this->_ttl) {
            $this->set_ttl($this->_ttl);
        }

        log_message('debug', 'Sape Model Initialized:' . $this->_table_name);
    }

    /**
    * установка значений результатов запроса xml-rpc
    * и их запись
    * если $sync_db_fields == true, то нужно синхронизировать поля с бд
    */
    public function init($data, $sync_db_fields = false, $query_info, $active_records_params = false) {
        $this->process_data($data, $sync_db_fields, $this->_fields_params, $query_info, $active_records_params);
    }
}
?>