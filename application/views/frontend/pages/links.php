<div class="span12">

    <h3 id="test" data-content="dsa">id:<?= $domain['id'] ?>; <?= $domain['url'] ?>; Тиц: <?= $domain['cy'] ?>; PR: <?= $domain['pr'] ?>; статус: <?= $domain['status'] ?>; <br>заработано сегодня: <?= $domain['amount_today'] ?>; </h3>

    <ul class="nav nav-tabs">
        <? if ($statuses): ?>
            <? foreach ($statuses as $name => $val): ?>
                <li class="<? if ($status == $name): ?>active<? endif; ?>">
                    <a href="<?= site_url('frontend/links/' . $uri_id . '/' . $name) ?>">
                        <?= $name ?>
                        <span class="badge" title="сссылок"><?= $val['count'] ?></span>
                        <span class="badge badge-success"><?= $val['sum'] ?> р.</span>
                    </a>
                </li>
            <? endforeach; ?>
        <? endif ?>
    </ul>

    <div class="row-fluid">
        <table id="list"><tr><td/></tr></table> 
        <div id="pager"></div> 
    </div><!--/row-->

</div><!--/span-->


<script type="text/javascript">
    window.ci_path = '<?= SCPATH ?>/static/3rdparty/jqgrid/';
    $(function () {
        
        // $(".help").popover({title: 'Дополнительно', content: "тиц: <?//= $domain['cy'] ?>. pr: <?//= $domain['pr'] ?>", placement: 'bottom'});


        var $grid_jq = $('#list');
        
        $grid_jq.jqGrid({
            url:'<?= site_url($grid_url); ?>',
            datatype: 'json',
            mtype: 'GET',

            /** fancybox in grid */
            loadComplete: function () {
                $('.iframe').fancybox({
                    width: 1000,
                    height: 700,
                    padding: 80,
                    iframe: {
                        preload: true
                    }
                });
            },
            colNames:[
                'id',
                'status',
                'page_id',
                'url',
                'txt',
                'price',
                'price_new',
                'date_placed',
                'flag_context',
                'site_id',
                'domain_id',
            ],
            colModel :[
                {name: 'id', index: 'id', width: 80},
                {name: 'status', index: 'status', width: 60},
                {name: 'page_id', index: 'page_id', width: 80},
                {name: 'url', index: 'url', width: 300, formatter: function (index, obj, row) {
                        return '<a href="' + index + '" class="iframe" rel="iframes" title="' + index + '">' + index + '</a>';
                    }
                },
                {name: 'txt', index: 'txt', width: 300},
                {name: 'price', index: 'price', width: 40},
                {name: 'price_new', index: 'price_new', width: 40},
                {name: 'date_placed', index: 'date_placed', width: 140},
                {name: 'flag_context', index: 'flag_context', width: 10},
                {name: 'site_id', index: 'site_id', width: 80},
                {name: 'domain_id', index: 'domain_id', width: 80},
            ],
            autoencode: true,
            pager: '#pager',
            rowNum:100,
            rowList:[10,30,100],
            sortname: 'id',
            sortorder: 'desc',
            viewrecords: true,
            gridview: true,
            caption: '<?= $grid_title ?>',
            height: 600
        });

        $grid_jq
            .navGrid('#pager',{edit:false,add:false,del:false,search:false, reload:true})
            .navButtonAdd("#pager",{ caption:"NewButton", buttonicon:"ui-icon-newwin", onClickButton:null, position: "last", title:"", cursor: "pointer"} );

    }); 
</script>