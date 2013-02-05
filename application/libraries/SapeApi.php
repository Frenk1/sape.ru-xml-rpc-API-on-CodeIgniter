<?
require_once APPPATH . 'third_party/xmlrpc-3.0.0.beta/lib/xmlrpc.inc';
require_once APPPATH . 'third_party/sape/dictionary_errors.php';

class SapeApiException extends Exception{}

/**
* SapeApi
* 
* интерфейс для работы с xml-rpc sape.ru
* потребуется библиотека xml-rpc для php http://sourceforge.net/projects/phpxmlrpc/files/phpxmlrpc/3.0.0beta/xmlrpc-3.0.0.beta.zip/download
* 
* пример:
* require_once './3rdparty/xmlrpc-3.0.0.beta/lib/xmlrpc.inc';
* $sape_xml = new SapeApi;
* $connect = $sape_xml->set_debug(0)->connect();
* $get_user = $connect->query('sape.get_user'); // метод без аргументов
* $this->_sapeapi->query('sape.get_user')->xml_cache()->fetch()->get_xml(); - получение результатов распарсенного xml
* $this->_sapeapi->query('sape.get_site_pages', array(88888))->xml_cache()->fetch()->get_xml(); // метод с одним аргументом
* $this->_sapeapi->query('sape.get_site_pages', array(88888, 111))->xml_cache()->fetch()->get_xml(); // метод с двумя аргументами
*
* @author Frenk1 aka Gudd.ini
* @version 0
*/
class SapeApi extends SapeApiLoader {
    /**
    * Свойства с данными для соединения с сервером xml-rpc
    */
    protected $_path = '/xmlrpc/',
              $_host = 'api.sape.ru',
              $_port = 80;

    /**
    * Свойства с данными для авторизации
    * переназначаются через конструктор
    */
    protected $_login = 'login';
    protected $_password = 'md5_hash'; // md5

    /**
    * Уровень режима отладки
    * 0, 1, 2
    */
    private $_debug = 0;

    /**
    * объект текущего соединения
    */
    private $_connect = false;

    /**
    * текущие куки
    */
    private $_cookies = false;

    /**
    * результат последнего запроса
    */
    private $_response = false;

    /**
    * последний сохраненный запрос
    */
    private $_query = false;

    /**
    * ошибка при запросе array('errno' => …, 'errstr' => …)
    */
    protected $_error = array();

    /**
    * словарь ответов ошибок sape
    */
    protected $_dictionary_errors = array();

    /**
    * хранение название метода в запросе к xml-rpc sape.ru
    * выполненного в последний раз
    */
    protected $_sape_method = false;

    /**
    * хранение сгенерированного названия для кэша
    * название может состоять из sape_method[__args]
    */
    protected $_cache_name = false;

    /**
    * кэшировать ли вообще xml-rpc
    */
    protected $_cached = false;

    /**
    * флаг показывающий - произошло ли обновление кэша при текущем запросе
    */
    protected $_cahe_refreshed = false;

    /**
    * модель, с которой будет происходить синхронизация
    * название будет сгенерировано из названия метода sape
    */
    protected $_model_name;

    /**
    * модель для запроса
    */
    protected $_model;

    /**
     * результат выполнения метода fetch. Сохранение результата в переменную для дальнейшей работы с массивом
     */
    protected $_fetch_result;

    /**
    * 
    */
    protected $_sapeapiloader;

    /**
    * доступ к экземпляру одиночки (singletone)
    */
    public $CI;

    /**
    * need dictionary_errors.php
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
        // TODO - сделать в интерфейсе отображение доступных адаптеров кэша и предупреждения
        $this->CI->load->driver('cache'/*, array('adapter' => 'apc', 'backup' => 'file')*/);
        $this->_login = $auth_data['login'];
        $this->_password = $auth_data['password'];

        $this->_dictionary_errors = function_exists('sape_dictionary') ? sape_dictionary() : array();
        $this->_sapeapiloader = $this->CI->sapeapiloader;
        return $this;
    }

    /**
    * установка кэширования
    */
    function xml_cache($param = true) {
        $this->_cached = (bool)$param;
        return $this;
    }

    /**
    * метод для подключения к серверу и поддержания соединения
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
    */
    function get_cookies() {
        $this->_cookies = $this->_response->_cookies;
    }

    /**
    * синхронизация кук при каждом запросе
    */
    function sync_cookies() {
        $this->get_cookies();

        foreach ($this->_response->_cookies as $name => $value) {
            $this->_connect->setCookie($name, $value['value']);
        }
    }


    /**
    * генерирование запроса к серверу sape
    */
    function query() {
        $num_args = func_num_args();
        $args = array();
        $sape_method = func_get_arg(0);
        $this->_sapeapiloader->load_model($sape_method);

        log_message('debug', 'SapeApi query: ' . $sape_method);

        if ($num_args > 1) {
            foreach (func_get_args() as $num => $arg) {
                if ($num < 1) continue;
                if ($num == 1) {
                    foreach ($arg as $a) {
                        $args[] = php_xmlrpc_encode($a);
                    }
                }
            }
            $this->_query = new xmlrpcmsg($sape_method, $args);

        } else {
            $this->_query = new xmlrpcmsg($sape_method);
        }

        return $this;
    }

    /**
    * выполнение запроса к серверу
    * @var flush_query_cache если в true, то сбрасывает кэш для текущего выполняемого запроса
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
                $this->CI->cache->save($cache_name, $cache, 3600); // кэширование результата запроса на час
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
    */
    function flush_query_cache($cache_name = NULL) {
        // если не передано имя кэша, то извлечем название метода в запросе к xml-rpc sape.ru
        // и используем в качестве названия кэша
        if (is_null($cache_name)) {
            $cache_name = $this->cache_name;
        }

        // удалим кэш безо всяких проверок на существование, ибо пофиг
        $this->CI->cache->file->delete($cache_name);

        return $this;
    }


    /**
     * TODO применить параметр кэширования полученный при цепочке вызовов
     * Выполнить запрос, извлечь данные, обработать ошибки
     * @return array
     */
    public function fetch() {
        $response = $this->cmd();
        if ($response->faultCode()) {
            $this->set_errnum($response->faultCode());
            $this->set_error($response->faultString());
            return false;
        }

        $response = php_xmlrpc_decode($response->value());
        $this->set_xml($response); // чтобы в случае чего - продолжить работу с цепочкой
        $this->_sapeapiloader->set_data($this->get_xml());
        return $this;
    }


    /**
    * получение массива с данными результата запроса
    */
    function fetch_data() {
        return php_xmlrpc_decode($this->get_response()->value());
    }

    /**
     * Получить результат выполненного запроса
     * @return xmlrpcresp
     */
    public function get_response() {
        return $this->_response;
    }

    public function set_response($set) {
        $this->_response = $set;
    }

    /**
    * код ответа от sape
    */
    function set_error($code = NULL, $string = NULL) {
        if ($code) $this->_error['errno'] = $code;
        if ($string) $this->_error['errstr'] = $string;
    }

    /**
    * получение перевода ошибки
    */
    function get_error_translate() {
        $error = $this->get_error();
        if (isset($error['errstr']) && $error['errstr'] && isset($this->_dictionary_errors[$error['errstr']])) {
            return $this->_dictionary_errors[$error['errstr']];
        } else {
            return $error['errstr'];
        }
    }

    public function get_error() {
        return $this->_error;
    }

    public function get_errnum() {
        return $this->_errnum;
    }

    private function set_errnum($errnum) {
        $this->_errnum = $errnum;
    }

    private function set_xml($xml) {
        $this->_xml = $xml;
    }

    public function get_xml() {
        return $this->_xml;
    }
}