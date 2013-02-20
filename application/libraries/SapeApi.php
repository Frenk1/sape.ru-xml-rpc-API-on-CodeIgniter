<?
/**
* SapeApi
* 
* интерфейс для работы с xml-rpc sape.ru
* Требуется cторонняя библиотека xml-rpc {@link http://sourceforge.net/projects/phpxmlrpc/files/phpxmlrpc/3.0.0beta/xmlrpc-3.0.0.beta.zip/download}.
* Та, что есть в codeigniter не подходила на момент разработки.
* 
* @todo: сделать счетчик запросов в минуту, должно быть не более 120
* @author Frenk1 aka Gudd.ini
* @version 0.2
*/
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . 'third_party/xmlrpc-3.0.0.beta/lib/xmlrpc.inc';
require_once APPPATH . 'third_party/sape/dictionary_errors.php';

class SapeApiException extends Exception{}

class SapeApi extends SapeApiLoader {
    /**
    * Свойства с данными для соединения с сервером xml-rpc
    * @var $_path обычное api или раширенное
    * @var $_host хост
    * @var $_port порт
    */
    protected $_path = '/xmlrpc/', # /xmlrpc/?v=extended
              $_host = 'api.sape.ru',
              $_port = 80;

    /**
    * Данные для авторизаия переназначаются через конструктор
    * 
    * @var string $_login логин в системе sape.ru
    * @var string $_password md5 хэш пароля от sape.ru
    */
    protected $_login = 'login',
              $_password = 'md5_hash';

    /**
    * Уровень режима отладки xml-rpc библиотеки {@link http://sourceforge.net/projects/phpxmlrpc/files/phpxmlrpc/3.0.0beta/xmlrpc-3.0.0.beta.zip/download}
    * 
    * @var integer $_debug от 0 до 2
    * 0, 1, 2
    */
    private $_debug = 0;

    /**
    * объект текущего соединения
    * 
    * @var object $_connect объект соединения с sape xml-rpc
    */
    private $_connect = false;

    /**
    * текущие куки для sape xml-rpc
    * должны быть получены в случае успешного запроса
    * 
    * @var $_cookies
    */
    private $_cookies = false;

    /**
    * результат последнего запроса sape xml-rpc
    * 
    * @var object $_response
    */
    private $_response = false;

    /**
    * последний сохраненный запрос sape xml-rpc
    * 
    * @var $_query
    */
    private $_query = false;

    /**
    * ошибка при запросе array('errno' => …, 'errstr' => …)
    * Сохранение, на всякий случай, ошибки. 
    * Однако, обычно, при ошибках, использую Exception, которые можно расшрить
    * для генерирования ошибок, для json
    * 
    * @var array[[code,[text]]] $_error
    */
    protected $_error = array();

    /**
    * словарь ответов ошибок sape
    * В конструкторе проверяется наличие словаря и если есть ассоциации на коды ошибок, то выводится расшифровка ошибки
    * Сделано из кодов на странице (внизу) {@link http://api.sape.ru/xmlrpc/?v=extended}
    * 
    * @var array $_dictionary_errors
    */
    protected $_dictionary_errors = array();

    /**
    * хранение название метода в запросе к xml-rpc sape.ru
    * выполненного в последний раз
    * 
    * @var string $_sape_method
    */
    protected $_sape_method = false;

    /**
    * хранение сгенерированного названия для кэша
    * название может состоять из sape.method_name[__arg_1[__arg_n]]
    * 
    * @var string $_cache_name
    */
    protected $_cache_name = false;

    /**
    * по сути, переменная таже, что и $_cache_name
    * отделена для более прозрачной логики
    * будет передана в модель, чтобы записываться в бд к синхронихируемому запросу
    * можно будет по этому полю дополнительные выборки делать ^_^
    * 
    * @var $_query_info string
    */
    public $_query_info;

    /**
    * кэшировать ли вообще xml-rpc
    * 
    * @var boolean $_cached
    */
    protected $_cached = false;

    /**
    * флаг показывающий - произошло ли обновление кэша при текущем запросе
    * 
    * @var boolean $_cahe_refreshed
    */
    protected $_cahe_refreshed = false;

    // *
    // * модель, с которой будет происходить синхронизация
    // * название будет сгенерировано из названия метода sape
    // * @var $_model_name
    
    // protected $_model_name;

    // /**
    // * модель для запроса
    // */
    // protected $_model;

    /**
    * загрузчик и распределятор моделей
    * 
    * @var object $_sapeapiloader class
    */
    protected $_sapeapiloader;

    /**
    * синхронизировать ли с бд?
    * 
    * @var boolean $_db_fields
    */
    private $_db_fields = false;

    /**
    * список active records параметров,
    * которые нужно будет применить для запроса к бд
    * в классе MY_model
    * 
    * @var array $_ar_params
    */
    private $_ar_params = array(
            'select' => '',
            'where' => array(),
        );

    /**
    * доступ к экземпляру одиночки (singletone)
    * 
    * @var object $CI
    */
    public $CI;

    /**
    * Библиотека в Codeigniter принимает параметры в виде массива
    * В конструктор нужно передать логин и md5 хэш пароля от sape.ru
    * 
    * @todo сделать в интерфейсе отображение доступных адаптеров кэша и предупреждения
    * @param array $auth_data данные для авторизации
    * @method object __construct(array $auth_data)
    * @return object $this
    */
    function __construct($auth_data = array()) {
        parent::__construct();
        try {
            if (!$auth_data) {
                throw new SapeApiException('SapeApi no have auth data');
            }
            if (!isset($auth_data['login']) || !$auth_data['login']) {
                throw new SapeApiException('Received invalid login');

            } elseif (!isset($auth_data['password']) || !$auth_data['password']) {
                throw new SapeApiException('Received invalid password');
            }

        } catch (SapeApiException $e) {
            echo $e->getMessage();
        }

        $this->CI =& get_instance();

        // загрузка драйвера кэша
        $this->CI->load->driver('cache', array('adapter' => 'file', 'backup' => 'dummy'));
        $this->_login = $auth_data['login'];
        $this->_password = $auth_data['password'];

        $this->_dictionary_errors = function_exists('sape_dictionary') ? sape_dictionary() : array();
        $this->_sapeapiloader = $this->CI->sapeapiloader;
        return $this;
    }

    /**
    * установка времени кэширования результатов xml
    * 
    * @var integer $param время жизни кэша в секундах
    * @method object xml_cache(integer $param)
    * @return $this
    */
    function xml_cache($param = 3600) {
        try {
            if (!is_numeric($param)) {
                throw new SapeApiException('Method "xml_cache" must be numeric type');
            }
        } catch (SapeApiException $e) {
            echo $e->getMessage();
        }

        $this->_cached = $param;
        return $this;
    }

    /**
    * метод для подключения к серверу sape.ru и поддержания соединения
    * 
    * @method object connect()
    * @return $this
    */
    function connect() {
        $this->_connect = new xmlrpc_client(
                $this->_path,
                $this->_host,
                $this->_port
            );
        $this->_connect->setDebug($this->_debug);

        $query = new xmlrpcmsg(
            'sape.login',
            array(
                    php_xmlrpc_encode($this->_login),
                    php_xmlrpc_encode($this->_password),
                    php_xmlrpc_encode(true)
                )
            );
        $this->_response = $this->_connect->send($query);

        try {
            if ($this->_response->value() && !$this->_response->value()->scalarval() ) {
                throw new SapeApiException('Не пришел user_id от сервера');

            } elseif (!$this->_response->value()) {
                $this->set_error($this->_response->errno, $this->_response->errstr);
                $error = $this->get_error();
                throw new SapeApiException('Не могу соединиться. ' . $this->get_error_translate());
            }

        } catch (SapeApiException $e) {
            echo $e->getMessage();
            exit;
        }

        return $this;
    }

    /**
    * установка уровня дебага
    * @method object set_debug(integer $lvl)
    * 
    * @param integer $lvl
    * @return $this
    */
    function set_debug($lvl = NULL) {
        if (!is_null($lvl)) {
            $lvl = intval($lvl);
            $this->_debug = $lvl;
        }

        return $this;
    }

    /**
    * получение свежих кук от сервера
    * 
    * @method void get_cookies()
    */
    function get_cookies() {
        $this->_cookies = $this->_response->_cookies;
    }

    /**
    * синхронизация кук при каждом запросе к xml-rpc sape.ru
    * 
    * @method void sync_cookies()
    */
    function sync_cookies() {
        $this->get_cookies();

        foreach ($this->_response->_cookies as $name => $value) {
            $this->_connect->setCookie($name, $value['value']);
        }
    }

    /**
    * Синхронизировать ли с бд?
    * 
    * @var bool $auto
    * @method void db_fields(bool $auto)
    * @return $this
    */
    function db_fields($auto = false) {
        $this->_db_fields = $auto;
        return $this;
    }

    /**
    * генерирование запроса к серверу sape
    * первый аргумент - название метода
    * второй агрумент - массив агрументов для зыпроса к xml-rpc sape.ru
    * 
    * @method object query()
    * @return $this
    */
    function query() {
        $num_args = func_num_args();
        $args = array();
        $sape_method = func_get_arg(0);
        $this->_sapeapiloader->load_model($sape_method);
        $this->_cache_name = $sape_method;

        log_message('debug', 'SapeApi query: ' . $sape_method);

        if ($num_args > 1) {
            foreach (func_get_args() as $num => $arg) {
                if ($num < 1) continue;
                if ($num == 1) {
                    foreach ($arg as $a) {
                        $args[] = php_xmlrpc_encode($a);
                        $this->_cache_name .= '__' . $a;
                    }
                }
            }
            $this->_query = new xmlrpcmsg($sape_method, $args);

        } else {
            $this->_query = new xmlrpcmsg($sape_method);
        }

        // разделение на переменные (для того, чтобы не запутаться)
        $this->_query_info = $this->_cache_name;

        return $this;
    }

    /**
    * MYSQL select для sql запроса средствами active records
    * Расширяет MY_Model
    * sql запрос происходит только, если в запросе к xmlr-rpc sape
    * используется синхронизация с бд
    * 
    * @param mixed $query
    * @method object query_select(mixed[] $query)
    * @return $this
    */
    function query_select($query = '') {
        if (!$this->_db_fields) {
            throw new SapeApiException('Нужно использовать синхронизацию с бд, чтобы иметь возможность делать select выборки из неё. Для этого, перед запросом query_select должен быть вызван метод "db_fields" с аргументом true');
        }
     
        $this->_ar_params['select'] = $query;
        return $this;
    }

    /**
    * MYSQL where для sql запроса средствами active records
    * Расширяет MY_Model
    * sql запрос происходит только, если в запросе к xmlr-rpc sape
    * используется синхронизация с бд
    * 
    * @param mixed $where
    * @method object query_where(mixed[] $where)
    * @return $this
    */
    function query_where($where = array()) {
        if (!$this->_db_fields) {
            throw new SapeApiException('Нужно использовать синхронизацию с бд, чтобы иметь возможность делать select выборки из неё. Для этого, перед запросом query_select должен быть вызван метод "db_fields" с аргументом true');
        }
     
        $this->_ar_params['where'] = $where;
        return $this;
    }

    /**
    * выполнение запроса к серверу
    * @method void cmd(bool $flush_query_cache)
    * 
    * @param bool $flush_query_cache если в true, то сбрасывает кэш для текущего выполняемого запроса
    * @return object Объект с ответом от xml-rpc sape.ru
    */
    function cmd($flush_query_cache = false) {
        try {
            if (!$this->_query) {
                throw new SapeApiException('cmd method need query for execute');
            }

        } catch (SapeApiException $e) {
            echo $e->getMessage();
        }
        if (! $this->_cached) {
            $this->connect();
            $this->sync_cookies();
            $this->_response = $this->_connect->send($this->_query);

        // если разрешено кэширование xml-rpc - то закэшируем
        } else {        
            // кэширование полученных результатов от Sape xml-rpc, чтобы потом ворочить кэшом
            // $cache_name = "{$this->query->methodname}";
            $cache_name = $this->_cache_name;

            // если нет кэша для выполняемого метода, то обновить запрос
            $cache = $this->CI->cache->get( $cache_name );
            if ( !$cache ) {
                $this->connect();
                $this->sync_cookies();
                $this->_response = $this->_connect->send($this->_query);
                $cache = $this->_response->serialize('utf-8');
                $this->CI->cache->save($cache_name, $cache, $this->_cached); // кэширование результата запроса на час
                $this->_cahe_refreshed = true; // уведомим, что обновился кэш

            } else {
                // если установлена переменная $flush_query_cache в true - то сбросим кэш для запроса
                if ($flush_query_cache) {
                    $this->flush_query_cache($cache_name);
                    // после сброса кэша - вызвать cmd еще разок, только без аргументов
                    $this->cmd();
                } else {
                    $this->_response = php_xmlrpc_decode_xml($cache);
                }
            }
        }

        try {
            if ($this->_response->faultCode()) {
                throw new SapeApiException('Sape server response error');
            }

        } catch (SapeApiException $e) {
            echo $e->getMessage();
        }

        return $this->_response;
    }


    /**
    * Сброс кэша для текущего запроса
    * 
    * @param bool $cache_name
    * @method object flush_query_cache(bool $cache_name)
    * @return $this
    */
    function flush_query_cache($cache_name = NULL) {
        // если не передано имя кэша, то извлечем название метода в запросе к xml-rpc sape.ru
        // и используем в качестве названия кэша
        if (is_null($cache_name)) {
            $cache_name = $this->_cache_name;
        }

        // удалим кэш безо всяких проверок на существование, ибо пофиг
        $this->CI->cache->delete($cache_name);

        return $this;
    }


    /**
     * Выполнить запрос, извлечь данные, обработать ошибки
     * 
     * @method object send()
     * @return $this
     */
    public function send() {
        $response = $this->cmd();
        if ($response->faultCode()) {
            $this->set_errnum($response->faultCode());
            $this->set_error($response->faultString());

            throw new SapeApiException('SapeApi::send() got error from xml-rpc library. Code: ' . $response->faultCode() . ', text: ' . $response->faultString());
        }
        
        $response = php_xmlrpc_decode($response->value());
        $this->set_xml($response); // чтобы в случае чего - продолжить работу с цепочкой
        $this->_sapeapiloader->init($this->get_xml(), $this->_db_fields, $this->_query_info, $this->_ar_params);
        return $this;
    }

    /**
     * Получение текущей модели, используемой в запросе
     * Вроде, как и не нужен метод больше. Потому что можно напрямую обратиться к модели
     * средствами Codeigniter
     * 
     * @method object get_model()
     * @return object $code_ingniter_model
     */
    function get_model() {
        return $this->_sapeapiloader->get_model();
    }

    /**
    * получение массива с данными результата запроса
    * 
    * @method array fetch_data()
    * @return array
    */
    function fetch_data() {
        return php_xmlrpc_decode($this->get_response()->value());
    }

    /**
     * Получить результат выполненного запроса
     * 
     * @method object get_response()
     * @return object
     */
    public function get_response() {
        return $this->_response;
    }


    /**
     * Установка в переменную значения
     * 
     * @method void set_response(mixed[] $set)
     */
    public function set_response($set) {
        $this->_response = $set;
    }

    /**
    * код ответа от sape
    * 
    * @method void set_error(integer $code, string $string)
    */
    function set_error($code = NULL, $string = NULL) {
        if ($code) $this->_error['errno'] = $code;
        if ($string) $this->_error['errstr'] = $string;
    }

    /**
    * получение перевода ошибки
    * 
    * @method void get_error_translate()
    * @return $string
    */
    function get_error_translate() {
        $error = $this->get_error();
        if (isset($error['errstr']) && $error['errstr'] && isset($this->_dictionary_errors[$error['errstr']])) {
            return $this->_dictionary_errors[$error['errstr']];
        } else {
            return $error['errstr'];
        }
    }

    /**
     * @method void get_error()
     * @return string
     */
    public function get_error() {
        return $this->_error;
    }

    /**
     * @method integer get_errnum()
     */
    public function get_errnum() {
        return $this->_errnum;
    }

    /**
     * @method void set_errnum(integer $integer)
     */
    private function set_errnum($errnum) {
        $this->_errnum = $errnum;
    }

    /**
     * @method void set_xml(object $xml)
     */
    private function set_xml($xml) {
        $this->_xml = $xml;
    }

    /**
     * @method object get_xml()
     */
    public function get_xml() {
        return $this->_xml;
    }
}