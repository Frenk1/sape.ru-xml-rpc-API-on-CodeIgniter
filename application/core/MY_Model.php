<?
defined('BASEPATH') OR exit('No direct script access allowed');

class MY_ModelException extends Exception{}

/**
* Расширение работы SapeApi модели
* В модели должен быть прописан параметр {@var $_fields_params} в формате по Database Forge Class {@link http://ellislab.com/codeigniter/user-guide/database/forge.html#create_table}
* 
* @author Frenk1 aka Gudd.ini
* @version 0.1
*/
class MY_Model extends CI_Model {
    /**
     * Распарсенный xml, полученный от sape xml-rpc/
     * Его нужно будет синхронизировать с бд
     * 
     * @var array $_sync_data
     */
    protected $_sync_data = false;

    /**
     * Хранение обработанных данных, которые уже можно вставлять в бд
     * 
     * @var array $_prepared_sync_data
     */
    protected $_prepared_sync_data = array();

    /**
     * Время жизни кэша в секундах. Чтобы не производить слишком много запросов к xml-rpc
     * 
     * @var integer $_ttl_seconds
     */
    protected $_ttl_seconds; // time to live

    /**
     * настройки для Database Forge Class {@link http://ellislab.com/codeigniter/user-guide/database/forge.html#create_table}
     * 
     * @var array $_fields
     */
    protected $_fields;

    /**
     * переданный флаг, нужно ли синхронизировать результаты с бд
     * 
     * @var boolean $_sync_db_fields
     */
    protected $_sync_db_fields;

    /**
     * информация о запросе, через двойное нижнее подчеркивание перечислены аргументы
     * 
     * @var string $_query_info
     */
    protected $_query_info;

    /**
     * вермя должно быть одинаковым в запросах, поэтому храним тут
     * 
     * @var $_time
     */
    protected $_time;

    /**
     * храним результат выборки в json
     * 
     * @var string $_json
     */
    protected $_json;

    /**
    * список active records параметров,
    * которые нужно будет применить для запроса к бд
    * 
    * @var array $_ar_params
    */
    private $_ar_params = array(
            'select' => '',
            'where' => array(),
        );

    /**
     * Установка времени кэша запросов xm-rpc sape.ru в бд по умолчанию на 8 часов
     * 
     * @method void __construct()
     */
    function __construct() {
        log_message('debug', 'Sape Extend Model Initialized.');
        parent::__construct();
        $this->_ttl_seconds = 3600 * 8;
        $this->_time = strftime('%Y-%m-%d %H:%M:%S');
    }

    /**
    * Каждой модели желательно прописать тип данных, который ожидается получить от xml-rpc sape.ru. Иначе, с синхронизацие будут проблемы.
    * В модели поддерживаются значения типов данных array, one_array, one_to_one
    * 
    * array - представление для бд (множество строк)
    * one_array - строка, в результатах от sape, преобразованная в массив  - является одной записью в бд
    * one_to_one - массив, который должен быть одной строкой в бд
    * 
    * @method void __construct(array $auth_data)
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

    /**
     * Запись данных выборок xml-rpc результатов, каждые n секунд (пресловутый кэш в бд)
     * 
     * @method void __process_data(array $data, boolean $sync_db_fields, array $fields, string $query_info, array $active_records_params)
     * @param array $data результаты от xml-rpc
     * @param boolean $sync_db_fields синхронизировать ли бд
     * @param array $fields список настроек полей бд
     * @param string $query_info запрос с аргументами, который был отправлен на sape.ru xml-rpc api
     * @param array $active_records_params массив с конфигами для дополнительных sql выборок (select|where)
     * @return void
     */
    public function process_data($data, $sync_db_fields = false, $fields, $query_info, $active_records_params = false) {
        $this->_sync_data = $data;
        $this->_fields = $fields;
        $this->_sync_db_fields = $sync_db_fields;
        $this->_table_name = strtolower($this->_table_name);
        $this->_query_info = $query_info;
        $this->_ar_params = $active_records_params ? $active_records_params : $this->_ar_params;
        $query_time = $this->_time;

        if (!$sync_db_fields) {
            return;
        }

        $this->_prepare_table();
        $last_row = $this->db->get_where($this->_table_name, array('ci_expire_date > ' => $query_time, 'ci_create_date < ' => $query_time, 'ci_query = ' => $this->_query_info), 0, 1);

        #если нет результата, то нужно записать новые данные
        if (!$last_row->result_array()) {
            if ($this->_result_type == 'array') {
                foreach ($this->_prepared_sync_data as $key => $data) {
                    $this->db->insert($this->_table_name, $data);
                }

            } else {
                $this->db->insert($this->_table_name, $this->_prepared_sync_data);
            }
        }
        unset($last_row);

        log_message('debug', 'Sape MY_Model / process_data success');
    }


    /**
     * подготовка данных перед записью
     * 
     * @method void _prepare_table()
     */
    protected function _prepare_table() {
        $this->_get_type();
        $this->_extend_fields();
        $this->CI->dbforge->add_field($this->_fields);
        $this->CI->dbforge->create_table($this->_table_name, TRUE);
    }


    /**
     * Обработка полей, который должны быть записаны в бд.
     * Дополнительно добавлены поля, по которым можно делать качественные выборки
     * 
     * @method array _add_extend_data(array $data)
     * @param array $data
     * @return array $data уже обработанные данные с новыми полями
     */
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

        // запись запроса в бд, тоже на всякий случай
        if (!isset($this->_fields['ci_query'])) {
            $this->_fields['ci_query'] = array(
                    'type'           => 'VARCHAR',
                    'constraint'     => 100, 
                );
        }
        
        // извлечение текущего ключа для поля
        $this->_time = time();
        $key = array_keys($data);
        $key = $key[0];
        $data['ci_id'] = false;
        $data['ci_create_date'] = strftime('%Y-%m-%d %H:%M:%S', $this->_time);
        $data['ci_expire_date'] = strftime('%Y-%m-%d %H:%M:%S', strtotime("+{$this->_ttl_seconds} seconds", $this->_time));
        $data['ci_ttl'] = $this->get_ttl();
        $data['ci_query'] = $this->_query_info;
        return $data;
    }


    /**
    * Обработка полей данных модели, тип которой = 'array'
    * 
    * @method void _result_array_handler(string $field, array $params)
    * @param string $field
    * @param array $params
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

                if (isset($params['callback']) && function_exists($params['callback'])) {
                    # преобразование даты в нужную
                    $this->_prepared_sync_data[$key][$field] = call_user_func($params['callback'], $this->_prepared_sync_data[$key][$field]);
                    $this->_prepared_sync_data[$key][$field] = strftime('%Y-%m-%d %H:%M:%S', $this->_prepared_sync_data[$key][$field]);
                }
                continue;
            }
        }
    }

    /**
    * Обработка полей данных модели, тип которой = 'one_array'
    * 
    * @method void _result_handler(string $field, array $params)
    * @param string $field
    * @param array $params
    */
    protected function _result_handler($field, $params) {
        $this->_prepared_sync_data[$field] = $this->_sync_data;
        $this->_prepared_sync_data = $this->_add_extend_data($this->_prepared_sync_data);
    }

    /**
    * Обработка полей данных модели, тип которой = 'one_to_one'
    * Подразумевается получение от sape xml-rpc только одной строки
    * 
    * @method void _result_one_array_handler(string $field, array $params)
    * @param string $field
    * @param array $params
    */
    protected function _result_one_array_handler($field, $params) {
        if ($params['sync']) {
            $this->_prepared_sync_data[$field] = $this->_sync_data[$field];
        }
        $this->_prepared_sync_data = $this->_add_extend_data($this->_prepared_sync_data);

        if (isset($params['callback']) && function_exists($params['callback'])) {
            # преобразование даты в нужную
            $this->_prepared_sync_data[$field] = call_user_func($params['callback'], $this->_prepared_sync_data[$field]);
            $this->_prepared_sync_data[$field] = strftime('%Y-%m-%d %H:%M:%S', $this->_prepared_sync_data[$field]);
        }
    }

    /**
    * Вызов обработчиков для разного типа данных моделей
    * 
    * @method void _extend_fields()
    */
    protected function _extend_fields() {
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

    /**
    * Расширение работы SapeApi с sql
    * дополнительные выборки для
    * 
    * @method void db_query()
    */
    function db_query() {
        if ($this->_ar_params['select']) {
            $this->db->select($this->_ar_params['select']);
        }

        if ($this->_ar_params['where']) {
            $this->db->where($this->_ar_params['where']);
        }
    }

    /**
    * контент для таблицы JqGrid
    * 
    * @method string get_json()
    * @return string $this->_json
    */
    function get_json() {
        $page = $this->input->get('page');
        $rows = $this->input->get('rows');
        $sort = $this->input->get('sidx');
        $order = $this->input->get('sord');
        $time = strftime('%Y-%m-%d %H:%M:%S');
        $query_set = array('ci_expire_date > ' => $time, 'ci_create_date < ' => $time, 'ci_query = ' => $this->_query_info);
        
        $this->db_query();
        $total = $this->db->get_where($this->_table_name, $query_set)->num_rows();
        $this->db->order_by($sort, $order);
        
        $this->db_query();
        $query = $this->db->get_where($this->_table_name, $query_set, intval($rows), intval(($page * $rows) - $rows));
        $result = $query->result_array();
        
        foreach ($result as $key => $value) {
            $result[$key] = array(
                    'id' => $value['id'],
                    'cell' => array_values($value)
                );
        }

        $prepare_json = array(
                'page' => $page,
                'records' => $rows,
                'total' => round($total / $rows),
                'rows' => $result,
            );
        log_message('debug', 'Sape MY_Model / get_json');
        $this->_json = json_encode($prepare_json);
        return $this->_json;
    }

    /**
     * @method void set_ttl(integer $ttl_seconds)
     */
    function set_ttl($ttl_seconds) {
        $this->_ttl_seconds = $ttl_seconds;
    }

    /**
     * @method void set_ttl()
     */
    function get_ttl() {
        return $this->_ttl_seconds;
    }
}