<?
defined('BASEPATH') OR exit('No direct script access allowed');

class MY_ModelException extends Exception{}

/**
* расширение работы класса модели
* Если поле одно, то результат генерируется в поле, которое описано в модели
*/
class MY_Model extends CI_Model {
    // private $instance;
    protected $_sync_data = false;
    protected $_prepared_sync_data = array();
    protected $_ttl_hours = 5; // time to live in hours
    protected $_fields;
    protected $_sync_db_fields; // переданный флаг, нужно ли синхронизировать результаты с бд

    function __construct() {
        log_message('debug', 'Sape Extend Model Initialized.');
        parent::__construct();
    }

    /**
    * array, one_array, one_to_one
    * array - представление для бд (множество строк)
    * one_array - строка, в результатах от sape, преобразованная в массив  - является одной записью в бд
    * one_to_one - массив, который должен быть одной строкой в бд
    */
    private function _get_type() {
        $type = isset($this->_result_type) ? $this->_result_type : false;

        if (!$type) {
            if (is_array($this->_sync_data)) {
                $this->_result_type = 'array';
            } else {
                $this->_result_type = 'one_array';
            }
        }
    }

    // запись данных, каждые n часов
    public function process_data($data, $sync_db_fields = false, $fields) {
        $this->_sync_data = $data;
        $this->_fields = $fields;
        $this->_sync_db_fields = $sync_db_fields;
        $this->_table_name = strtolower($this->_table_name);
        $time = strftime('%Y-%m-%d %H:%M:%S');

        if (!$sync_db_fields) {
            return;
        }

        $this->_prepare_table();
        $last_row = $this->db->get_where($this->_table_name, array('ci_expire_date > ' => $time, 'ci_create_date < ' => $time), 1, 0);

        #если нет результата, то нужно записать новые данные
        if (!$last_row->result()) {

            if ($this->_result_type == 'array') {
                foreach ($this->_prepared_sync_data as $key => $data) {
                    $this->db->insert($this->_table_name, $data);
                }

            } else {
                $this->db->insert($this->_table_name, $this->_prepared_sync_data);
            }
        }
        unset($last_row);
    }


    protected function _prepare_table() {
        $this->_get_type();
        $this->_extend_fields();
        $this->CI->dbforge->add_field($this->_fields);
        $this->CI->dbforge->create_table($this->_table_name, TRUE);
    }

    function _add_extend_data($data) {
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

        // дата истечения срока годности
        if (!isset($this->_fields['ci_expire_date'])) {
            $this->_fields['ci_expire_date'] = array(
                    'type' => 'DATETIME',
                );
        }

        // ttl, на всякий случай
        if (!isset($this->_fields['ci_ttl'])) {
            $this->_fields['ci_ttl'] = array(
                    'type'           => 'INT',
                    'constraint'     => 3, 
                );
        }
        
        // извлечение текущего ключа для поля
        $key = array_keys($data);
        $key = $key[0];
        $data['ci_id'] = false;
        $data['ci_create_date'] = date("Y-n-j(H:i:s)");
        $data['ci_expire_date'] = date("Y-n-j(H:i:s)", strtotime("+{$this->_ttl_hours} hours"));
        $data['ci_ttl'] = $this->get_ttl();
        return $data;
    }


    /**
    * обработка результатов в виде массива
    */
    protected function _result_array_handler($field, $params) {
        foreach ($this->_sync_data as $key => $sync_data) {
            if (isset($params['sync'])) {
                if (!$params['sync']) {
                    unset($this->_fields[$field]);
                } else {
                    unset($this->_fields[$field]['sync']);

                    #а бывает, что данных нет
                    $this->_prepared_sync_data[$key][$field] = 
                        isset($this->_sync_data[$key][$field])
                            ? $this->_sync_data[$key][$field]
                            : NULL;

                    $this->_prepared_sync_data[$key] = $this->_add_extend_data($this->_prepared_sync_data[$key]);
                }
                continue;
            }
        }
    }

    /**
    * обработка НЕ массивов
    */
    protected function _result_handler($field, $params) {
        $this->_prepared_sync_data[$field] = $this->_sync_data;
        $this->_prepared_sync_data = $this->_add_extend_data($this->_prepared_sync_data);
    }

    /**
    * обработка результатов в виде массива, но с одной строкой (в представлении бд)
    */
    protected function _result_one_array_handler($field, $params) {
        if ($params['sync']) {
            $this->_prepared_sync_data[$field] = $this->_sync_data[$field];
        }
        $this->_prepared_sync_data = $this->_add_extend_data($this->_prepared_sync_data);
    }

    /**
    * расширение полей бд полями ID и DATE и учет параметра sync
    * подготовка данных для синхронизации
    */
    protected function _extend_fields() {
        # удаление полей sync и полей, у которых sync == false
        # и добавление полей ID и DATE
        # Возвращаемое от sape значение может быть строкой
        foreach ($this->_fields as $field => $params) {

            # возвращаемый от sape результат может быть массивом и просто значением
            if ($this->_result_type == 'array') {
                $this->_result_array_handler($field, $params);

            } elseif ($this->_result_type == 'one_array') {
                $this->_result_handler($field, $params);

            } elseif ($this->_result_type == 'one_to_one') {
                $this->_result_one_array_handler($field, $params);
            }
        }
    }

    function set_ttl($ttl_hours) {
        $this->_ttl_hours = $ttl_hours;
    }

    function get_ttl() {
        return $this->_ttl_hours;
    }

    function get_instance() {
        return $this->instance;
    }
}