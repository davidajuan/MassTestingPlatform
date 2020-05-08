<?php $this->load->helper('general'); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title><?= $company_name ?> | Drive Thru Testing Appointment Scheduler</title>
    <meta http-equiv="content-type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">

    <link rel="icon" type="image/x-icon" href="<?= getURL(APP_CONFIG['FAVICON_URL']) ?>">

    <link rel="stylesheet" type="text/css" href="<?= asset_url('assets/ext/bootstrap/css/bootstrap.min.css') ?>">
    <link rel="stylesheet" type="text/css" href="<?= asset_url('assets/ext/jquery-ui/jquery-ui.min.css') ?>">
    <link rel="stylesheet" type="text/css" href="<?= asset_url('assets/ext/jquery-qtip/jquery.qtip.min.css') ?>">
    <link rel="stylesheet" type="text/css" href="<?= asset_url('assets/ext/trumbowyg/ui/trumbowyg.min.css') ?>">
    <link rel="stylesheet" type="text/css" href="<?= asset_url('assets/css/backend.css') ?>">
    <link rel="stylesheet" type="text/css" href="<?= asset_url('assets/css/general.css') ?>">
    <link rel="stylesheet" type="text/css" href="<?= asset_url('assets/css/main.css') ?>">

    <script src="<?= asset_url('assets/ext/jquery/jquery.min.js') ?>"></script>
    <script src="<?= asset_url('assets/ext/bootstrap/js/bootstrap.bundle.min.js') ?>"></script>
    <script src="<?= asset_url('assets/ext/jquery-ui/jquery-ui.min.js') ?>"></script>
    <script src="<?= asset_url('assets/ext/jquery-qtip/jquery.qtip.min.js') ?>"></script>
    <script src="<?= asset_url('assets/ext/datejs/date.js') ?>"></script>
    <script src="<?= asset_url('assets/ext/jquery-mousewheel/jquery.mousewheel.js') ?>"></script>
    <script src="<?= asset_url('assets/ext/trumbowyg/trumbowyg.min.js') ?>"></script>
    <script src="https://cdn.jsdelivr.net/npm/js-cookie@rc/dist/js.cookie.min.js"></script>

    <script>
    	// Global JavaScript Variables - Used in all backend pages.
    	var availableLanguages = <?= json_encode($this->config->item('available_languages')) ?>;
    	var EALang = <?= json_encode($this->lang->language) ?>;
    </script>
</head>

<body>
<header role="banner" class="header">
  <nav role="navigation" id="header" class="navigation navbar navbar-dark">
      <div id="header-logo" class="navbar-brand py-0">
          <img class="header__logo" src="<?= getURL(APP_CONFIG['LOGO_URL']) ?>">
          <span><?= $company_name ?></span>
      </div>

      <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#header-menu" aria-controls="header-menu" aria-expanded="false" aria-label="Toggle navigation">
          <span class="navbar-toggler-icon"></span>
      </button>

      <div id="header-menu" class="collapse navbar-collapse flex-row-reverse">
          <ul class="nav navbar-nav navMenu">
              <?php $hidden = ($privileges['appointments']['edit'] === TRUE) ? '' : ' d-none' ?>
              <?php $active = ($active_menu == PRIV_BOOK) ? 'active' : '' ?>
              <li class="<?= $active.$hidden ?>">
                  <a href="<?= site_url('backend') ?>" class="navMenu__item">
                      <?= lang('book_appointment') ?>
                  </a>
              </li>

              <?php $hidden = ($privileges['business']['edit'] === TRUE) ? '' : ' d-none' ?>
              <?php $active = ($active_menu == PRIV_BUSINESS) ? 'active' : '' ?>
              <li class="<?= $active.$hidden ?>">
                  <a href="<?= site_url('backend/business') ?>" class="navMenu__item">
                    <?= lang('business') ?>
                  </a>
              </li>

              <?php $hidden = ($privileges['business_request']['edit'] === TRUE) ? '' : ' d-none' ?>
              <?php $active = ($active_menu == PRIV_BUSINESS_REQUEST) ? 'active' : '' ?>
              <li class="<?= $active.$hidden ?>">
                  <a href="<?= site_url('backend/business_request') ?>" class="navMenu__item">
                    <?= lang('business_requests') ?>
                  </a>
              </li>

              <?php
                // Permanently hidden but leaving code in place to add back in with permissions
                // for future use
                $hidden = 'd-none';
                //$hidden = ($privileges['system_settings']['add'] == TRUE) ? '' : ' d-none'
              ?>
              <?php $active = ($active_menu == PRIV_APPOINTMENTS) ? 'active' : '' ?>
              <li class="<?= $active . $hidden ?>">
                  <a href="<?= site_url('backend/calendar') ?>" class="navMenu__item">
                      <?= lang('calendar') ?>
                  </a>
              </li>

              <?php $hidden = ($privileges[PRIV_CUSTOMERS]['view'] == TRUE) ? '' : ' d-none' ?>
              <?php $active = ($active_menu == PRIV_CUSTOMERS) ? 'active' : '' ?>
              <li class="<?= $active . $hidden ?>">
                  <a href="<?= site_url('backend/customers') ?>" class="navMenu__item"
                          title="<?= lang('manage_customers_hint') ?>">
                      <?= lang('customers') ?>
                  </a>
              </li>

              <?php $hidden = ($privileges[PRIV_SERVICES]['view'] == TRUE) ? '' : ' d-none' ?>
              <?php $active = ($active_menu == PRIV_SERVICES) ? 'active' : '' ?>
              <li class="<?= $active . $hidden ?>">
                  <a href="<?= site_url('backend/services') ?>" class="navMenu__item">
                      <?= lang('services') ?>
                  </a>
              </li>

              <?php $hidden = ($privileges[PRIV_CUSTOMERS]['view'] == TRUE) ? '' : ' d-none' ?>
              <?php $active = ($active_menu == 'backend/upload_csv') ? 'active' : '' ?>
              <li class="<?= $active . $hidden ?>">
                  <a href="<?= site_url('backend/upload_csv') ?>" class="navMenu__item">
                    <?= lang('upload_csv') ?>
                  </a>
              </li>

              <?php $hidden = ($privileges[PRIV_USERS]['view'] ==  TRUE) ? '' : ' d-none' ?>
              <?php $active = ($active_menu == PRIV_USERS) ? 'active' : '' ?>
              <li class="<?= $active . $hidden ?>">
                  <a href="<?= site_url('backend/users') ?>" class="navMenu__item">
                      <?= lang('users') ?>
                  </a>
              </li>

              <!-- TODO: When reports privalages are added change this to reports -->
              <?php $hidden = ($privileges['business']['edit'] === TRUE) ? '' : ' d-none' ?>
              <?php $active = ($active_menu == PRIV_REPORTS) ? 'active' : '' ?>
              <li class="<?= $active.$hidden ?>">
                  <a href="<?= site_url('backend/reports') ?>" class="navMenu__item">
                    <?= lang('reports') ?>
                  </a>
              </li>

              <?php
              // hacking this to only show if admin
              $hidden = ($privileges[PRIV_SYSTEM_SETTINGS]['view'] == TRUE) ? '' : ' d-none' ?>
              <?php $active = ($active_menu == PRIV_SYSTEM_SETTINGS) ? 'active' : '' ?>
              <li class="<?= $active . $hidden ?>">
                  <a href="<?= site_url('backend/settings') ?>" class="navMenu__item">
                      <?= lang('settings') ?>
                  </a>
              </li>

              <li>
                  <a href="<?= site_url('user/logout') ?>" class="navMenu__item">
                      <?= lang('log_out') ?>
                  </a>
              </li>
          </ul>
      </div>
  </nav>
</header>


<div id="notification" style="display: none;"></div>

<div id="loading" style="display: none;">
    <div class="any-element animation is-loading">
        &nbsp;
    </div>
</div>
