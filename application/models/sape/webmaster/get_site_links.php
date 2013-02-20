<?
defined('BASEPATH') OR exit('No direct script access allowed');

class Get_site_links extends MY_Model {
    /**
    * поля для синхронизации результатов запроса sape xml-rpc
    */
    protected $_fields_params = array(
        'id' => array(
                'sync' => true,
                'type' => 'INT',
                'constraint' => 12,
            ),

        'status' => array(
                'sync' => true,
                'type' => 'VARCHAR',
                'constraint' => 12,
            ),

        'page_id' => array(
                'sync' => true,
                'type' => 'INT',
                'constraint' => 12,
            ),

        'url' => array(
                'sync' => true,
                'type' => 'VARCHAR',
                'constraint' => 350,
            ),


        'txt' => array(
                'sync' => true,
                'type' => 'VARCHAR',
                'constraint' => 500,
            ),

        'price' => array(
                'sync' => true,
                'type' => 'VARCHAR', // не люблю float-ы в бд
                'constraint' => 20,
            ),


        'price_new' => array(
                'sync' => true,
                'type' => 'VARCHAR', // не люблю float-ы в бд
                'constraint' => 20,
            ),

        'date_placed' => array(
                'sync' => true,
                'callback' => 'iso8601_decode', // function name
                # так как дата будет преобразована, то можно сменить тип поля на DATETIME
                'type' => 'DATETIME',
            ),

        'flag_context' => array(
                'sync' => true,
                'type' => 'VARCHAR',
                'constraint' => 50,
            ),

        'site_id' => array(
                'sync' => true,
                'type' => 'INT',
                'constraint' => 12,
            ),

        'domain_id' => array(
                'sync' => true,
                'type' => 'INT',
                'constraint' => 12,
            ),

        );
    /**
    * доступ к экземпляру одиночки (singletone)
    */
    public $CI;
    public $_table_name = false;
    /**
    * array, one_array, one_to_one
    * array - представление для бд (множество строк)
    * one_array - строка, в результатах от sape, преобразованная в массив  - является одной записью в бд
    * one_to_one - массив, который должен быть одной строкой в бд
    */
    public $_result_type = 'array';
    private $_ttl;

    function __construct() {
        parent::__construct();
        $this->CI =& get_instance();
        $this->_table_name = __CLASS__;

        $this->_ttl = 3600 * 8;
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