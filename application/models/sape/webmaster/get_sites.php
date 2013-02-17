<?
defined('BASEPATH') OR exit('No direct script access allowed');

class Get_sites extends MY_Model {
    /**
    * поля для синхронизации результатов запроса sape xml-rpc
    */
    protected $_fields_params = array(
            'id'                                   => array(
                                                        'sync' => true,
                                                        'type' => 'INT',
                                                        'constraint' => 12,
                                                        ),

            'url'                                  => array(
                                                        'sync' => true,
                                                        'type' => 'VARCHAR',
                                                        'constraint' => 500,
                                                        ),

            'cy'                                   => array(
                                                        'sync' => true,
                                                        'type' => 'INT',
                                                        'constraint' => 6,
                                                        ),

            'pr'                                   => array(
                                                        'sync' => true,
                                                        'type' => 'INT',
                                                        'constraint' => 2,
                                                        ),

            'category_id'                          => array(
                                                        'sync' => true,
                                                        'type' => 'INT',
                                                        'constraint' => 6,
                                                        ),

            'date_created'                         => array(
                                                        'sync' => true,
                                                        'type' => 'VARCHAR',
                                                        'constraint' => 20,
                                                        ),

            'date_last_mpp_changed'                 => array(
                                                        'sync' => true,
                                                        'type' => 'VARCHAR',
                                                        'constraint' => 20,
                                                        ),

            'status'                               => array(
                                                        'sync' => true,
                                                        'type' => 'VARCHAR',
                                                        'constraint' => 20,
                                                        ),

            'domain_level'                         => array(
                                                        'sync' => true,
                                                        'type' => 'INT',
                                                        'constraint' => 2, // ну мало ли :)
                                                        ),

            'flag_auto'                            => array(
                                                        'sync' => true,
                                                        'type' => 'INT',
                                                        'constraint' => 1,
                                                        ),

            'mpp_1'                                => array(
                                                        'sync' => true,
                                                        'type' => 'INT',
                                                        'constraint' => 8,
                                                        ),

            'mpp_2'                                => array(
                                                        'sync' => true,
                                                        'type' => 'INT',
                                                        'constraint' => 8,
                                                        ),

            'mpp_3'                                => array(
                                                        'sync' => true,
                                                        'type' => 'INT',
                                                        'constraint' => 8,
                                                        ),

            'flag_blocked_in_yandex'               => array(
                                                        'sync' => true,
                                                        'type' => 'INT',
                                                        'constraint' => 1,
                                                        ),

            'flag_hide_url'                        => array(                // such as bool field
                                                        'sync' => true,
                                                        'type' => 'INT',
                                                        'constraint' => 1,
                                                        ),

            'links_delimiter'                      => array(
                                                        'sync' => true,
                                                        'type' => 'VARCHAR',
                                                        'constraint' => 250, // delimeter maybe very big
                                                        ),

            'links_css_class'                      => array(
                                                        'sync' => true,
                                                        'type' => 'VARCHAR',
                                                        'constraint' => 30,
                                                        ),

            'links_css_class_context'              => array(
                                                        'sync' => true,
                                                        'type' => 'VARCHAR',
                                                        'constraint' => 30,
                                                        ),

            'flag_use_unprintable_words_stop_list' => array(
                                                        'sync' => true,
                                                        'type' => 'INT',
                                                        'constraint' => 1,
                                                        ),

            'flag_use_adult_words_stop_list'       => array(
                                                        'sync' => true,
                                                        'type' => 'INT',
                                                        'constraint' => 1,
                                                        ),

            'flag_not_for_sale'                    => array(
                                                        'sync' => true,
                                                        'type' => 'INT',
                                                        'constraint' => 1,
                                                        ),

            'amount_today'                         => array(
                                                        'sync' => true,
                                                        'type' => 'VARCHAR',
                                                        'constraint' => 20,
                                                        'default' => '0'
                                                        ),

            'amount_yesterday'                     => array(
                                                        'sync' => true,
                                                        'type' => 'VARCHAR',
                                                        'constraint' => 20,
                                                        'default' => '0'
                                                        ),

            'amount_total'                         => array(
                                                        'sync' => true,
                                                        'type' => 'VARCHAR', // не люблю float-ы
                                                        'constraint' => 20,
                                                        'default' => '0'
                                                        ),

            'comment_admin'                        => array(
                                                        'sync' => true,
                                                        'type' => 'VARCHAR', // не люблю float-ы
                                                        'constraint' => 250,
                                                        ),

            'nof_pages'                            => array(
                                                        'sync' => true,
                                                        'type' => 'INT',
                                                        'constraint' => 6,
                                                        ),

            'in_yaca'                              => array(
                                                        'sync' => true,
                                                        'type' => 'INT',
                                                        'constraint' => 1,
                                                        ),

            'in_dmoz'                              => array(
                                                        'sync' => true,
                                                        'type' => 'INT',
                                                        'constraint' => 1,
                                                        ),

            'nof_yandex'                           => array(
                                                        'sync' => true,
                                                        'type' => 'INT',
                                                        'constraint' => 6,
                                                        ),

            'nof_google'                           => array(
                                                        'sync' => true,
                                                        'type' => 'INT',
                                                        'constraint' => 6,
                                                        ),

            'days_to_recheck'                      => array(
                                                        'sync' => true,
                                                        'type' => 'INT',
                                                        'constraint' => 3,
                                                        ),
        );

    /**
    * доступ к экземпляру одиночки (singletone)
    */
    public $CI;
    public $_table_name = false;
    private $_ttl = false;

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