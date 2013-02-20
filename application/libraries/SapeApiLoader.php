<?
/**
* SapeApiLoader
* 
* Это файл для определения моделей, которые могут работать с xml-rpc sape.ru (SapeApi.class.php)
* 
* @author Frenk1 aka Gudd.ini
* @version 0.2
*/

defined('BASEPATH') OR exit('No direct script access allowed');

class SapeApiLoaderException extends Exception{}

class SapeApiLoader {
    /**
     * сюда будет добавлен список методов через конструктор,
     * которые принадлежат оптимизатору или вэбмастеру
     * Обеспечивает дополнительные фильтры для результатов выборок sql
     * 
     * @var array $_loader
     */
    private $_loader = array(
            'webmaster' => array(), 
            'optimizator' => array()
        );

    /**
    * название модели, которую нужно вызвать
    * 
    * @var string $_model_name
    */
    private $_model_name = false;

    /**
    * используемая модель
    * 
    * @var object $_model
    */
    private $_model = false;

    /**
    * путь до модели для её подгрузки
    * 
    * @var string $_model_path
    */
    protected $_model_path = '';

    /**
    * доступ к экземпляру одиночки (singletone)
    * 
    * @var object $CI
    */
    public $CI;


    /**
     * В конструкторе определяется, какие модели можно загружать
     * 
     * @method void __construct()
     * @param $this->_loader['webmaster'|'optimizator'] запрос(ключ) и название класса модели(значение)
     */
    function __construct() {
        $this->CI =& get_instance();

        $this->_loader['webmaster'] = array(
                'sape.get_user'          => 'Get_user', // invoke example - $this->CI->Get_user->get_test()
                'sape.get_balance'       => 'Get_balance',
                'sape.get_balance_real'  => 'Get_balance_real',
                'sape.get_balance_locks' => 'Get_balance_locks',
                'sape.get_bills'         => 'Get_bills',
                'sape.get_sites'         => 'Get_sites',
                'sape.get_sites_links_count' => 'Get_sites_links_count',
                'sape.get_sites_pages_count' => 'Get_sites_pages_count',
                'sape.get_site_money_stats'  => 'Get_site_money_stats',
                'sape.get_url_links'   => 'Get_url_links', 
                'sape.get_site_links'  => 'Get_site_links', 
                // ... and other
            );
    }

    /**
    * Проверка существования модели и её загрузка
    * 
    * @method void load_model(string $query)
    * @var string $query
    */
    protected function load_model($query) {
        if ($this->detect_model($query)) {
            log_message('debug', 'SapeApiLoader try load model for query: ' . $query);
            $this->CI->load->model($this->_model_path);
            $this->_model = class_exists($this->_model_name) ? $this->CI->{$this->_model_name} : false;
            try {
                if (!$this->_model) {
                    throw new SapeApiLoaderException('Can not load model for query: ' . $query);
                }
            } catch (SapeApiLoaderException $e) {
                echo $e->getMessage();
            }
        }
    }

    /**
    * Вызов модели, к которой произошел запрос и передача параметров
    * 
    * @method void init(array $data, boolean $detect_db_fields, string $query_info, array $active_records_params)
    * @var array $data распарсенный xml, полученный от sape
    * @var boolean $detect_db_fields флаг, говорящий, нужно ли синхронизировать с бд
    * @var string $query_info информация о запросе. Двойным подчеркиванием - '__' отделены агрументы от запроса
    * @var array $active_records_params изменение sql запроса через SapeApi
    */
    protected function init($data, $detect_db_fields = false, $query_info = false, $active_records_params = false) {
        log_message('debug', 'SapeApiLoader method init cannot sync with databse');
        $this->_model && method_exists($this->_model, 'init') ? $this->_model->init($data, $detect_db_fields, $query_info, $active_records_params) : '';
    }

    /**
    * определение модели по запросу
    * 
    * @method bool detect_model(string $query)
    * @param string $query
    * @return bool true
    */
    protected function detect_model($query = NULL) {
        try {
            foreach ($this->_loader as $method_type => $methods) {
                if (array_key_exists($query, $methods)) {
                    $this->_model_name = $this->_loader[$method_type][$query];
                    $this->_model_path = 'sape/' . $method_type . '/' . $this->_model_name;
                    log_message('debug', 'SapeApiLoader detect model for query: ' . $query);
                    break;
                }
            }

            if (is_null($query) || !$query) {
                throw new SapeApiLoaderException('Empty query. Can not detect model from query.');
            }
            if (!isset($this->_model_path)) {
                throw new SapeApiLoaderException("Until now not implemented the ability to query '{$query}'.");
            }
        } catch (SapeApiLoaderException $e) {
            echo $e->getMessage();
        }

        return true;
    }

    /**
    * Текущая модель
    * 
    * @method object get_model()
    */
    function get_model() {
        return $this->_model;
    }
}
?>