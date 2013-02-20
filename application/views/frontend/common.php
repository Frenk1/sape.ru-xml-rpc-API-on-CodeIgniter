<!DOCTYPE html>
<html lang="en">
  <head>
    <?= $this->load->view('frontend/head'); ?>
  </head>

  <body>

    <div class="navbar navbar-inverse navbar-fixed-top">
      <div class="navbar-inner">
        <div class="container-fluid">
          <a class="brand" href="<?= SCPATH ?>"><?= $project_name ?></a>
          <?= $this->load->view('frontend/topmenu'); ?>
        </div>
      </div>
    </div>

    <div class="container-fluid">
      <div class="row-fluid">

        <? if ($view_name): ?>
            <? $this->load->view($view_name) ?>
        <? endif ?>

      </div><!--/row-->

      <hr>

      <footer>
        <p>&copy; Company 2013</p>
      </footer>

    </div><!--/.fluid-container-->

    <?= $scripts ?>
    <link href="<?= SCPATH ?>/static/css/style.css" rel="stylesheet"> <!-- тут, чтобы перекрыть стили других скриптов, если понадобится. А это понадобится -->
  </body>
</html>
