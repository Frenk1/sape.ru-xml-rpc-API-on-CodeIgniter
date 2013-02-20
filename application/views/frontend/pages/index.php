<div class="span12">

    <div class="row-fluid">
        <table id="list"><tr><td/></tr></table> 
        <div id="pager"></div> 
    </div><!--/row-->

</div><!--/span-->


<script type="text/javascript">
    window.ci_path = '<?= SCPATH ?>/static/3rdparty/jqgrid/';
    $(function () {
        var $grid_jq = $('#list');
        
        $grid_jq.jqGrid({
            url:'<?= site_url('sape/get_sites'); ?>',
            datatype: 'json',
            mtype: 'GET',
            colNames:[
                'id',
                'url',
                'cy',
                'pr',
                'category_id',
                'date_created',
                'date_last_mpp_changed',
                'status',
                'domain_level',
                'flag_auto',
                'mpp_1',
                'mpp_2',
                'mpp_3',
                'flag_blocked_in_yandex',
                'flag_hide_url',
                'links_delimiter',
                'links_css_class',
                'links_css_class_context',
                'flag_use_unprintable_words_stop_list',
                'flag_use_adult_words_stop_list',
                'flag_not_for_sale',
                'amount_today',
                'amount_yesterday',
                'amount_total',
                'comment_admin',
                'nof_pages',
                'in_yaca',
                'in_dmoz',
                'nof_yandex',
                'nof_google',
                'days_to_recheck',
            ],
            colModel :[
                {name: 'id', index: 'id', width: 80, formatter: function (index, obj, row) {
                        return '<a href="<?= site_url('frontend/links') ?>/' + index + '">' + index + '</a>';
                    }
                },
                {name: 'url', index: 'url', width: 100},
                {name: 'cy', index: 'cy', width: 20},
                {name: 'pr', index: 'pr', width: 20},
                {name: 'category_id', index: 'category_id', width: 40},
                {name: 'date_created', index: 'date_created', width: 110},
                {name: 'date_last_mpp_changed', index: 'date_last_mpp_changed', width: 110},
                {name: 'status', index: 'status', width: 40},
                {name: 'domain_level', index: 'domain_level', width: 40},
                {name: 'flag_auto', index: 'flag_auto', width: 40},
                {name: 'mpp_1', index: 'mpp_1', width: 40},
                {name: 'mpp_2', index: 'mpp_2', width: 40},
                {name: 'mpp_3', index: 'mpp_3', width: 40},
                {name: 'flag_blocked_in_yandex', index: 'flag_blocked_in_yandex', width: 40},
                {name: 'flag_hide_url', index: 'flag_hide_url', width: 40},
                {name: 'links_delimiter', index: 'links_delimiter', width: 40},
                {name: 'links_css_class', index: 'links_css_class', width: 40},
                {name: 'links_css_class_context', index: 'links_css_class_context', width: 40},
                {name: 'flag_use_unprintable_words_stop_list', index: 'flag_use_unprintable_words_stop_list', width: 40},
                {name: 'flag_use_adult_words_stop_list', index: 'flag_use_adult_words_stop_list', width: 40},
                {name: 'flag_not_for_sale', index: 'flag_not_for_sale', width: 40},
                {name: 'amount_today', index: 'amount_today', width: 40},
                {name: 'amount_yesterday', index: 'amount_yesterday', width: 40},
                {name: 'amount_total', index: 'amount_total', width: 40},
                {name: 'comment_admin', index: 'comment_admin', width: 40},
                {name: 'nof_pages', index: 'nof_pages', width: 40},
                {name: 'in_yaca', index: 'in_yaca', width: 40},
                {name: 'in_dmoz', index: 'in_dmoz', width: 40},
                {name: 'nof_yandex', index: 'nof_yandex', width: 40},
                {name: 'nof_google', index: 'nof_google', width: 40},
                {name: 'days_to_recheck', index: 'days_to_recheck', width: 40},
            ],
            autoencode: true,
            pager: '#pager',
            rowNum:20,
            rowList:[10,20,30],
            sortname: 'id',
            sortorder: 'desc',
            viewrecords: true,
            gridview: true,
            caption: '<?= $grid_title ?>',
            height: 400,

            del : {
                caption: "Delete",
                msg: "Delete selected record(s)?",
                bSubmit: "Delete",
                bCancel: "Cancel"
            }
        });

        $grid_jq
            .navGrid('#pager',{edit:false,add:false,del:false,search:false})
            .navButtonAdd("#pager",{ caption:"NewButton", buttonicon:"ui-icon-newwin", onClickButton:null, position: "last", title:"", cursor: "pointer"} );

    }); 
</script>