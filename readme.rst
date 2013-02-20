###################
Копия Codeigniter для работы с xml-rpc SapeApi
###################
https://github.com/Frenk1/xml-rpc-Sape.ru-api

Для того, чтобы начать пользоваться библиотекой необходимо знать базовые вещи о CodeIgniter.
Работу можно начать с рассмотрения контроллера application/controllers/sape.php

Сразу скажу, что очевидные вещи, такие, как отладка или указание логина и пароля я опущу.

## Необходимые действия
Для начала нужно прописать доступы к базе данных и выставить права на запись к директориям "cache" и "log".

Данные для авторизации
> $auth_data = array(
>     'login' => 'login here',
>     'password' => 'md5 hash here'
> );

Для работы с бд
> $this->load->database();

> $this->load->dbforge();


Далее подключение SapeApiLoader - основного подгрузчика моделей. В нем же нужно будет прописать модели, допустимые для использования. Позже я расскажу подробнее о том, как подключать модели.
> $this->load->library('SapeApiLoader');

> $this->load->library('SapeApi', $auth_data);


Отключение отладочных сообщений от библиотеки xml-rpc и сохранение объекта соединения в свойство _sapeapi 
> $this->_sapeapi = $this->sapeapi->set_debug(0);

Попробуем сделать первый запрос.
Для этого, надо будет создать страницу вывода. В примере контроллера sape.php, все методы вывода страниц имеют префикс "action_".
Создайте метод (функцию), например, 'test'. В контроллере sape.php он будет назваться "action_test". Убедитесь, что можете зайти на свежесозданную страницу, у меня она имеет такой адрес: http://localhost/index.php/sape/test

Теперь, в качестве примера выполним следующий код (т.к. модель get_user уже создана):
>     $user = $this
>         ->_sapeapi
>         ->query('sape.get_user') // Выполнение запроса к sape api. Сохранение данных о запросе
>         ->db_fields(true) // сохранять ли данные в бд
>         ->xml_cache(3600) // время жизни xml кэша, полученного от sape api
>         ->send() // передача данных в модель и манипуляции над ними
>         ->get_xml(); // можно получить распарсенный xml результат, обычно использую для отладки

> var_dump( $user );

## Хранение моделей
Список доступных моделей для запросов к sape api хранится в файле "CodeIgniter/application/libraries/SapeApiLoader.php"

Изначально задумывалось, что любой сможет расширить работу с этой библиотекой. В связи с этим SapeApiLoader распределяет запросы по типам. Если вы посмотрите структуру папки моделей, то увидите, что сейчас для sape используются запросы типа "вэбмастер" - CodeIgniter/application/models/sape/webmaster/. Тип прописывается в переменной 
> $this->_loader['webmaster']

(здесь тип запроса - "webmaster"). Соответственно, если нужно будет использовать запросы другого типа, то можно добавить еще один ключ. Например, 
> $this->_loader['optimizator']. 

Но пути к моделям и модели придется создавать вручную.

## Структура модели
Рассмотрим модель из нашего первого запроса.
Думаю, нет смысла объяснять о том, что за конфигурации для полей используются.

Разве, что кратко:
Ключ "sync" означает, что поле нужно синхронизировать с бд. В примере пользователя я не сохраняю в бд хэш пароля. Потому что незачем.

Строка
>                                                 'sync' => true,
>                                                 'type' => 'VARCHAR',
>                                                 'constraint' => 22,

Мимикрия под boolean поле
>                                                 'sync' => true,
>                                                 'type' => 'INT',
>                                                 'constraint' => 1,

В некоторых моделях с датами вы можете увидеть такую конфигурацию:
>             'date_created'                         => array(
>                                                         'sync' => true,
>                                                         'callback' => 'iso8601_decode', // function name
>                                                         # так как дата будет преобразована, то можно сменить тип поля на DATETIME
>                                                         'type' => 'DATETIME',
>                                                         ),

> <?
> class Get_user extends MY_Model {
>     /**
>     * поля для синхронизации результатов запроса sape xml-rpc
>     */
>     protected $_fields_params = array(
>             'login'                   => array(
>                                                 'sync' => true,
>                                                 'type' => 'VARCHAR',
>                                                 'constraint' => 22,
>                                             ),

>             'email'                   => array(
>                                                 'sync' => true,
>                                                 'type' => 'VARCHAR',
>                                                 'constraint' => 30,
>                                             ),

>             'balance'                 => array(
>                                                 'sync' => true,
>                                                 'type' => 'FLOAT',
>                                                 'constraint' => 16,
>                                             ),

>             'hash'                    => array(
>                                                 'sync' => false,
>                                             ),

>             'seo_nof_links_ok'        => array(
>                                                 'sync' => true,
>                                                 'type' => 'FLOAT',
>                                                 'constraint' => 16,
>                                             ),

>             'seo_nof_links'           => array(
>                                                 'sync' => true,
>                                                 'type' => 'FLOAT',
>                                                 'constraint' => 16,
>                                             ),

>             'seo_budget_monthly'      => array(
>                                                 'sync' => true,
>                                                 'type' => 'FLOAT',
>                                                 'constraint' => 16,
>                                             ),

>             'seo_budget_monthly_real' => array(
>                                                 'sync' => true,
>                                                 'type' => 'FLOAT',
>                                                 'constraint' => 16,
>                                             ),

>             'amount_spent_today'      => array(
>                                                 'sync' => true,
>                                                 'type' => 'FLOAT',
>                                                 'constraint' => 16,
>                                             ),

>             'amount_spent_week'       => array(
>                                                 'sync' => true,
>                                                 'type' => 'FLOAT',
>                                                 'constraint' => 16,
>                                             ),

>             'amount_earned_today'     => array(
>                                                 'sync' => true,
>                                                 'type' => 'FLOAT',
>                                                 'constraint' => 16,
>                                             ),

>             'amount_earned_week'      => array(
>                                                 'sync' => true,
>                                                 'type' => 'FLOAT',
>                                                 'constraint' => 16,
>                                             ),

>             'amount_pc_today'         => array(
>                                                 'sync' => true,
>                                                 'type' => 'FLOAT',
>                                                 'constraint' => 16,
>                                             ),

>             'amount_pc_week'          => array(
>                                                 'sync' => true,
>                                                 'type' => 'FLOAT',
>                                                 'constraint' => 16,
>                                             ),

>             'amount_result_today'     => array(
>                                                 'sync' => true,
>                                                 'type' => 'FLOAT',
>                                                 'constraint' => 16,
>                                             ),

>             'amount_result_week'      => array(
>                                                 'sync' => true,
>                                                 'type' => 'FLOAT',
>                                                 'constraint' => 16,
>                                             ),
>         );

>     /**
>     * доступ к экземпляру одиночки (singletone)
>     */
>     public $CI;
>     public $_table_name = false;
>     public $_result_type = 'one_to_one';
>     private $_ttl = 1800;

>     function __construct() {
>         parent::__construct();
>         $this->CI =& get_instance();
>         $this->_table_name = __CLASS__;

>         if ($this->_ttl) {
>             $this->set_ttl($this->_ttl);
>         }

>         log_message('debug', 'Sape Model Initialized:' . $this->_table_name);
>     }

>     /**
>     * установка значений результатов запроса xml-rpc
>     * и их запись
>     * если $sync_db_fields == true, то нужно синхронизировать поля с бд
>     */
>     public function init($data, $sync_db_fields = false, $query_info, $active_records_params = false) {
>         $this->process_data($data, $sync_db_fields, $this->_fields_params, $query_info, $active_records_params);
>     }
> }
> ?>

Отдельного объяснения потребуют вот эти два свойства - public $_result_type = 'one_to_one';
private $_ttl = 1800;

_ttl - время срока годности кэша, в секундах, для базы данных. Хочу заметить, что из бд, пока не удаляется автоматически устаревший кэш.

### $_result_type

По моим оценкам, sape api выдает три различных типа данных. Которые я отметил, как "array, one_array и one_to_one".

Перед тем, как выставить значение этого свойства, вам придется подумать и протестировать, какие данные стоит ожидать от запроса.

**one_to_one** - получен массив с полями. Грубо говоря, одна строка для вставки в бд, по-идее, тоже должен автоматически определиться в MY_model.php.

**array** - это стандартный тип, который должен и сам определиться, если не будет заполнено свойство "_result_type". Оно означает, что поступил массив массивов.

**one_array** - получена строка, которая будет преобразована в одно поле, в зависимости от того, какое будет указано в списке полей модели (пример, модель **get_balance.php**).


###################
What is CodeIgniter
###################

CodeIgniter is an Application Development Framework - a toolkit - for people
who build web sites using PHP. Its goal is to enable you to develop projects
much faster than you could if you were writing code from scratch, by providing
a rich set of libraries for commonly needed tasks, as well as a simple
interface and logical structure to access these libraries. CodeIgniter lets
you creatively focus on your project by minimizing the amount of code needed
for a given task.

*******************
Release Information
*******************

This repo contains in development code for future releases. To download the
latest stable release please visit the `CodeIgniter Downloads
<http://codeigniter.com/downloads/>`_ page.

**************************
Changelog and New Features
**************************

You can find a list of all changes for each release in the `user
guide change log <https://github.com/EllisLab/CodeIgniter/blob/develop/user_guide_src/source/changelog.rst>`_.

*******************
Server Requirements
*******************

-  PHP version 5.2.4 or newer.

************
Installation
************

Please see the `installation section <http://codeigniter.com/user_guide/installation/index.html>`_
of the CodeIgniter User Guide.

*******
License
*******

Please see the `license
agreement <http://codeigniter.com/user_guide/license.html>`_

*********
Resources
*********

-  `User Guide <http://codeigniter.com/user_guide/>`_
-  `Community Forums <http://codeigniter.com/forums/>`_
-  `Community Wiki <http://codeigniter.com/wiki/>`_
-  `Community IRC <http://ellislab.com/codeigniter/irc>`_

***************
Acknowledgement
***************

The EllisLab team and The Reactor Engineers would like to thank all the
contributors to the CodeIgniter project and you, the CodeIgniter user.
