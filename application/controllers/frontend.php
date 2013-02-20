<?
defined('BASEPATH') OR exit('No direct script access allowed');

class Frontend extends CI_Controller {
    private $CI;
    public $addons = array(
            'project_name' => 'SapeBerry 0.1',
            'title' => 'SapeBerry 0.1',
            'meta_cache' => '<meta http-equiv="cache-control" content="no-cache">',
            'scripts' => '',
            'view_name' => '', // путь до вьюхи, которая должна подгрузиться в контентную область
        );

    function __construct() {
        parent::__construct();
        $this->CI =& get_instance();
        $this->load->database();
        $this->load->dbforge();
    }

    /**
    на главной странице список сайтов
    */
    public function index() {
        $way_jqgrid = SCPATH . '/static/3rdparty/jqgrid/jquery.jqGrid.js';
        $way_jqgrid_i18n = SCPATH . '/static/3rdparty/jqgrid/js/i18n/grid.locale-en.js';
        $this->addons['scripts'] .= "<script src='{$way_jqgrid}'></script>";
        $this->addons['scripts'] .= "<script src='{$way_jqgrid_i18n}'></script>";
        $this->addons['title'] .= " / Список площадок";
        $this->addons['grid_title'] = "sape.get_sites";
        $this->addons['view_name'] .= 'frontend/pages/index';
        $this->load->view('frontend/common', $this->addons);
    }

    /**
    Список ссылок
    */
    public function links($id = '', $status = false) {
        if (!$id) {
            show_404();
        }

        if ($status && !preg_match('~(WAIT_WM|WAIT_SEO|OK|ERROR|SLEEP)~', $status)) {
            show_error('Неправильный статус для фильтра', 403);
        }

        $statuses = array(
            'WAIT_WM' => array('count' => 0, 'sum' => 0),
            'WAIT_SEO' => array('count' => 0, 'sum' => 0),
            'OK' => array('count' => 0, 'sum' => 0),
            'ERROR' => array('count' => 0, 'sum' => 0),
            'SLEEP' => array('count' => 0, 'sum' => 0),
            );

            $time = strftime('%Y-%m-%d %H:%M:%S');
        if ($this->db->table_exists('get_site_links')) {
            $query_set = array('ci_expire_date > ' => $time, 'ci_create_date < ' => $time, 'ci_query' => 'sape.get_site_links__' . $id);
            $this->db->select("count(status) as count, status, sum(price) as sum");
            $this->db->group_by('status');
            $status_count_query = $this->db->get_where('get_site_links', $query_set);
            $status_counters = $status_count_query->result_array();
            foreach (array_keys($statuses) as $s) {
                foreach ($status_counters as $s_group) {
                    if ($s == $s_group['status']) {
                        $statuses[$s] = array(
                                'count' => $s_group['count'], 
                                'sum' => number_format($s_group['sum'], 2, '.', ' '),
                            );
                    }
                }
            }
            $status_count_query->free_result();
        }


        $this->addons['grid_url'] = 'sape/links/' . $id;
        $this->addons['status'] = '';
        $this->addons['statuses'] = $statuses;
        if ($status) {
            $this->addons['grid_url'] .= '/' . $status;
            $this->addons['status'] = $status;
        }

        // запрос для извлечения инфы о площадке
        $this->db->select('id, url, cy, pr, status, amount_today');
        $domain = $this->db
                ->get_where('get_sites', array('ci_query = ' => 'sape.get_sites', 'id' => $id), 1, 0);
        $result = $domain->result_array();
        $result = $result[0];

        $way_jqgrid = SCPATH . '/static/3rdparty/jqgrid/jquery.jqGrid.js';
        $way_jqgrid_i18n = SCPATH . '/static/3rdparty/jqgrid/js/i18n/grid.locale-en.js';
        $way_fancybox = SCPATH . '/static/3rdparty/jquery.fancybox-1.3.4/fancybox/jquery.fancybox-1.3.4.pack.js';
        $way_fancybox_css = SCPATH . '/static/3rdparty/jquery.fancybox-1.3.4/fancybox/jquery.fancybox-1.3.4.css';
        $this->addons['scripts'] .= "<script src='{$way_jqgrid}'></script>";
        $this->addons['scripts'] .= "<script src='{$way_jqgrid_i18n}'></script>";
        $this->addons['scripts'] .= "<script src='{$way_fancybox}'></script>";
        $this->addons['scripts'] .= "<link rel='stylesheet' type='text/css' href='{$way_fancybox_css}'>";
        $this->addons['title'] .= " / Обработка заявок";
        $this->addons['grid_title'] = "sape.get_site_links";
        $this->addons['domain'] = $result;
        
        $this->addons['uri_id'] = $id;
        $this->addons['view_name'] .= 'frontend/pages/links';
        $this->load->view('frontend/common', $this->addons);
    }
}