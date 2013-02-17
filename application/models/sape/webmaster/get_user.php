<?
/**
* DANGER !!! ALARM!!!
* old model !!!!!!!!!
**
* *
* *
* */
class Get_user extends CI_Model {
    protected $_table_name;

    /**
    * поля для синхронизации результатов запроса sape xml-rpc
    */
    protected $_fields = array(
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

    protected $_sync_data = false;
    protected $_prepared_sync_data = array();
    protected $_ttl_hours = 5; // time to live in hours

    /**
    * доступ к экземпляру одиночки (singletone)
    */
    public $CI;

    function __construct() {
        parent::__construct();
        $this->CI =& get_instance();
        $this->_table_name = __CLASS__;

        log_message('debug', 'Sape Model Initialized:' . $this->_table_name);
    }

    // запись данных, каждые n часов
    protected function process_data() {
        $time = strftime('%Y-%m-%d %H:%M:%S');
        $this->_prepare_table();
        $last_row = $this->db->get_where($this->_table_name, array('ci_expire_date > ' => $time, 'ci_create_date < ' => $time), 1, 0);

        #если нет результата, то нужно записать новые данные
        if (!$last_row->result()) {
            $this->db->insert($this->_table_name, $this->_prepared_sync_data);
        }

        unset($last_row);
    }

    protected function _prepare_table() {
        $this->_extend_fields();
        $this->CI->dbforge->add_field($this->_fields);
        $this->CI->dbforge->create_table($this->_table_name, TRUE);
    }

    public function _set_data($data) {
        $this->_sync_data = $data;
        $this->process_data();
    }

    /**
    * расширение полей бд полями ID и DATE и учет параметра sync
    * подготовка данных для синхронизации
    */
    protected function _extend_fields() {
        # удаление полей sync и полей, у которых sync == false
        # и добавление полей ID и DATE
        foreach ($this->_fields as $field => $params) {

            if (isset($params['sync'])) {
                if (!$params['sync']) {
                    unset($this->_fields[$field]);
                } else {
                    unset($this->_fields[$field]['sync']);

                    $this->_prepared_sync_data[$field] = $this->_sync_data[$field];
                }
                continue;
            }
        }

        // для возможных конфликтов с результатами запросов id записи == ci_id
        if (!isset($this->_fields['ci_id'])) {
            $this->_fields['ci_id'] = array(
                    'type'           => 'INT',
                    'constraint'     => 8, 
                    'unsigned'       => true,
                    'AUTO_INCREMENT' => true
                );
            $this->CI->dbforge->add_key('ci_id', true);
        }

        // дата создания
        if (!isset($this->_fields['ci_create_date'])) {
            $this->_fields['ci_create_date'] = array(
                    'type' => 'DATETIME',
                );
        }
        $this->_prepared_sync_data['ci_create_date'] = date("Y-n-j(H:i:s)");

        // дата истечения срока годности
        if (!isset($this->_fields['ci_expire_date'])) {
            $this->_fields['ci_expire_date'] = array(
                    'type' => 'DATETIME',
                );
        }
        $this->_prepared_sync_data['ci_expire_date'] = date("Y-n-j(H:i:s)", strtotime("+{$this->_ttl_hours} hours"));
    }
}
?>