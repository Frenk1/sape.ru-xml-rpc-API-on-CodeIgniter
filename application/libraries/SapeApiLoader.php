<?
class SapeApiLoaderException extends Exception{}

class SapeApiLoader {
    // сюда будет добавлен список методов через конструктор,
    // которые принадлежат оптимизатору или вэбмастеру
    private $_loader = array(
            'webmaster' => array(), 
            'optimizator' => array()
        );

    private $_model = false;

    /**
    * путь до модели для её подгрузки
    */
    protected $_model_path = '';

    /**
    * доступ к экземпляру одиночки (singletone)
    */
    public $CI;

    function __construct() {
        $this->CI =& get_instance();

        $this->_loader['webmaster'] = array(
                'sape.get_user'          => 'Get_user',
                'sape.get_balance'       => 'Get_balance',
                'sape.get_balance_real'  => 'Get_balance_real',
                'sape.get_balance_locks' => 'Get_balance_locks'
                // ... and other
            );
    }

    protected function load_model($query) {
        if ($this->detect_model($query)) {
            log_message('debug', 'SapeApiLoader try load model for query: ' . $query);
            $this->CI->load->model($this->_model_path);
        }
    }

    /** определение модели по запросу */
    protected function detect_model($query = NULL) {
        try {
            foreach ($this->_loader as $method_type => $methods) {
                if (array_key_exists($query, $methods)) {
                    $this->_model = $this->_loader[$method_type][$query];
                    $this->_model_path = 'sape/' . $method_type . '/' . $this->_model;
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
}
?>