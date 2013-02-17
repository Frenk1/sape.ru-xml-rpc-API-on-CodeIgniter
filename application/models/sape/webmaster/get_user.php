<?
class Get_user extends MY_Model {
    /**
    * поля для синхронизации результатов запроса sape xml-rpc
    */
    protected $_fields_params = array(
            'login'                   => array(
                                                'sync' => true,
                                                'type' => 'VARCHAR',
                                                'constraint' => 22,
                                            ),

            'email'                   => array(
                                                'sync' => true,
                                                'type' => 'VARCHAR',
                                                'constraint' => 30,
                                            ),

            'balance'                 => array(
                                                'sync' => true,
                                                'type' => 'FLOAT',
                                                'constraint' => 16,
                                            ),

            'hash'                    => array(
                                                'sync' => false,
                                            ),

            'seo_nof_links_ok'        => array(
                                                'sync' => true,
                                                'type' => 'FLOAT',
                                                'constraint' => 16,
                                            ),

            'seo_nof_links'           => array(
                                                'sync' => true,
                                                'type' => 'FLOAT',
                                                'constraint' => 16,
                                            ),

            'seo_budget_monthly'      => array(
                                                'sync' => true,
                                                'type' => 'FLOAT',
                                                'constraint' => 16,
                                            ),

            'seo_budget_monthly_real' => array(
                                                'sync' => true,
                                                'type' => 'FLOAT',
                                                'constraint' => 16,
                                            ),

            'amount_spent_today'      => array(
                                                'sync' => true,
                                                'type' => 'FLOAT',
                                                'constraint' => 16,
                                            ),

            'amount_spent_week'       => array(
                                                'sync' => true,
                                                'type' => 'FLOAT',
                                                'constraint' => 16,
                                            ),

            'amount_earned_today'     => array(
                                                'sync' => true,
                                                'type' => 'FLOAT',
                                                'constraint' => 16,
                                            ),

            'amount_earned_week'      => array(
                                                'sync' => true,
                                                'type' => 'FLOAT',
                                                'constraint' => 16,
                                            ),

            'amount_pc_today'         => array(
                                                'sync' => true,
                                                'type' => 'FLOAT',
                                                'constraint' => 16,
                                            ),

            'amount_pc_week'          => array(
                                                'sync' => true,
                                                'type' => 'FLOAT',
                                                'constraint' => 16,
                                            ),

            'amount_result_today'     => array(
                                                'sync' => true,
                                                'type' => 'FLOAT',
                                                'constraint' => 16,
                                            ),

            'amount_result_week'      => array(
                                                'sync' => true,
                                                'type' => 'FLOAT',
                                                'constraint' => 16,
                                            ),
        );

    /**
    * доступ к экземпляру одиночки (singletone)
    */
    public $CI;
    public $_table_name = false;
    public $_result_type = 'one_to_one';
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
    public function init($data, $sync_db_fields = false) {
        $this->process_data($data, $sync_db_fields, $this->_fields_params);
    }
}
?>