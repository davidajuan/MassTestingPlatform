<link rel="stylesheet" type="text/css" href="<?= asset_url('assets/css/frontend.css') ?>">
<link rel="stylesheet" type="text/css" href="<?= asset_url('assets/css/general.css') ?>">

<div id="book-appointment-wizard" class="bookAppointment">
    <!-- FRAME TOP BAR -->
    <div id="no-appointments-banner" class="alert alert-fatal h3 hide">
        <?= lang('no_more_appointments'); ?>
    </div>

    <!-- PATIENT SEARCH BAR -->
    <div id="filter-customers" class="search">
        <form>
            <div class="d-flex align-items-center search__inputContainer">
                <input type="text" class="col key textInput search__input" placeholder="Search">
                <div class="input-group-addon">
                    <div>
                        <button class="filter btn btn--simple px-1" type="submit" title="<?= lang('filter') ?>">
                            <i class="fas fa-search"></i>
                        </button>
                        <button class="clear btn btn--simple px-1" type="button" title="<?= lang('clear') ?>">
                            <i class="fas fa-redo-alt"></i>
                        </button>
                    </div>
                </div>
            </div>
            <div class="searchResults">
                <div class="searchResults__items"></div>
            </div>
            <input type="hidden" id="appointment-hash" />
        </form>
        <div id="customer-appointments"></div>
    </div>

    <?php if ($manage_mode): ?>
        <div class="px-4 mb-3 px-4 mb-3" id="customer-edit">
            <h1 id="company-name" class="bookHeading mb-2"><?= lang('edit_title') ?></h1>
            <button id="delete-personal-information" class="btn btn-danger btn-sm mr-3"><?= lang('delete_patient_button') ?></button>
            <button id="edit-customer-cancel" type="button" class="btn btn--simple px-0" title="Cancel">
              <?= lang('cancel') ?>
            </button>
        </div>
    <?php endif; ?>

    <?php
        if (isset($exceptions)) {
            echo '<div style="margin: 10px">';
            echo '<h4>' . lang('unexpected_issues') . '</h4>';
            foreach($exceptions as $exception) {
                echo exceptionToHtml($exception);
            }
            echo '</div>';
        }
    ?>

    <!-- SELECT SERVICE AND PROVIDER -->
    <div id="wizard-frame-1" class="px-4 pb-4">
        <div class="d-none">
            <h3><?= lang('step_one_title') ?></h3>
            <div class="frame-content">
                <div class="formGroup">
                    <label for="select-service">
                        <strong><?= lang('select_service') ?></strong>
                    </label>
                    <select id="select-service" class="col-xs-12 col-sm-4 textInput">
                        <?php
                        // Group services by category, only if there is at least one service with a parent category.
                        $has_category = FALSE;
                        foreach($available_services as $service) {
                            if ($service['category_id'] != NULL) {
                                $has_category = TRUE;
                                break;
                            }
                        }

                        if ($has_category) {
                            $grouped_services = array();
                            foreach($available_services as $service) {
                                if ($service['category_id'] != NULL) {
                                    if (!isset($grouped_services[$service['category_name']])) {
                                        $grouped_services[$service['category_name']] = array();
                                    }
                                    $grouped_services[$service['category_name']][] = $service;
                                }
                            }

                            // We need the un-categorized services at the end of the list so
                            // we will use another iteration only for the un-categorized services.
                            $grouped_services['uncategorized'] = array();
                            foreach($available_services as $service) {
                                if ($service['category_id'] == NULL) {
                                $grouped_services['uncategorized'][] = $service;
                                }
                            }

                            foreach($grouped_services as $key => $group) {
                                $group_label = ($key != 'uncategorized')
                                ? $group[0]['category_name'] : 'Uncategorized';

                                if (count($group) > 0) {
                                    echo '<optgroup label="' . $group_label . '">';
                                    foreach($group as $service) {
                                        echo '<option value="' . $service['id'] . '">'
                                        . $service['name'] . '</option>';
                                    }
                                    echo '</optgroup>';
                                }
                            }
                        }  else {
                            foreach($available_services as $service) {
                                echo '<option value="' . $service['id'] . '">' . $service['name'] . '</option>';
                            }
                        }
                        ?>
                    </select>
                </div>

                <div class="formGroup">
                    <label for="select-provider"><strong><?= lang('select_provider') ?></strong></label>
                    <select id="select-provider" class="col-xs-12 col-sm-4 textInput"></select>
                </div>
                <div id="service-description" style="display:none;"></div>
            </div>
        </div>

        <!-- BOOK APPOINTMENT FORM -->
        <div class="mb-3">
            <label class="font-weight-bold h5"><?= lang('caller'); ?></label>
            <div class="d-flex">
                <input class="radioInput" id="patient" name="caller" type="radio" value="patient" data-validate="required">
                <label class="radioLabel" for="patient"><?= lang('patient'); ?></label>
                <input class="radioInput" id="provider" name="caller" type="radio" value="provider">
                <label class="radioLabel" for="provider"><?= lang('provider'); ?></label>
                <input class="radioInput" id="essential-worker" name="caller" type="radio" value="essential-worker">
                <label class="radioLabel" for="essential-worker"><?= lang('essential_worker'); ?></label>
            </div>
            <span class="errorMessage"></span>
        </div>

        <div class="formGroup mb-1">
            <input type="checkbox" id="first-responder" class="mr-2"/>
            <label for="first-responder" class="control-label"><?= lang('first_responder') ?></label>
        </div>

        <div class="formGroup">
            <input type="checkbox" id="city-worker" class="mr-2"/>
            <label for="city-worker" class="control-label"><?= lang('city_worker') ?></label>
        </div>

        <div class="formStartBlank bookForm">
            <div class="bookForm__column">
            <h4><?= lang('patient_info_title') ?></h4>
                <div id="business-code-container" class="hide">
                    <div class="formGroup d-flex align-items-end mb-0">
                        <div>
                            <label for="business-code" class="control-label"><?= lang('business_code') ?></label>
                            <div class="inputIconGroup">
                            <input type="text" id="business-code" class="textInput" maxlength="8" data-mask="000000AA" data-validate="<?= $manage_mode ? '' : 'businessCode' ?>" data-format-string="uppercase" <?= $manage_mode ? 'disabled' : '' ?>/>
                            <i id="busCodeSuccess" class="fas fa-check hide inputIconGroup__icon colorGreen"></i>
                            <i id="busCodeError" class="fas fa-times hide inputIconGroup__icon colorRed"></i>
                            </div>
                        </div>
                    </div>
                    <span class="errorMessage mb-3"></span>
                </div>
                <div class="inlineGroup@lg">
                    <div class="formGroup fullWidth">
                        <label for="first-name" class="control-label"><?= lang('first_name') ?></label>
                        <input type="text" id="first-name" class="required textInput" maxlength="120" data-validate="required, name" data-format-string="titleCase"/>
                        <span class="errorMessage"></span>
                    </div>

                    <div class="formGroup">
                        <label for="middle-initial" class="control-label"><?= lang('middle_initial') ?></label>
                        <input type="text" id="middle-initial" class="textInput" maxlength="5" data-validate="name" data-format-string="uppercase"/>
                        <span class="errorMessage"></span>
                    </div>
                </div>

                <div class="formGroup">
                    <label for="last-name" class="control-label"><?= lang('last_name') ?></label>
                    <input type="text" id="last-name" class="required textInput" maxlength="120" data-validate="required, name" data-format-string="titleCase" />
                    <span class="errorMessage"></span>
                </div>

                <div class="formGroup">
                    <label for="gender" class="control-label"><?= lang('gender') ?></label>
                    <select class="selectInput required" id="gender" data-validate="required">
                        <option value="">Select Gender</option>
                        <option value="female">Female</option>
                        <option value="male">Male</option>
                        <option value="transgender">Transgender</option>
                        <option value="other">Other</option>
                    </select>
                    <span class="errorMessage"></span>
                </div>

                <div class="formGroup">
                    <label for="dob" class="control-label"><?= lang('dob') ?></label>
                    <input type="tel" id="dob" class="required textInput" data-mask="00-00-0000" data-dob data-validate="required, date, futureDate"/>
                    <span class="errorMessage"></span>
                </div>

                <div class="formGroup hide" id="ageCalculated">
                    <?= lang('age') ?>: <span data-age></span>
                </div>

                <div class="formGroup">
                    <div class="d-flex justify-content-between">
                        <label for="email" class="control-label"><?= lang('email') ?></label>
                        <div>
                            <input type="checkbox" id="patient-consent" name="patient-consent">
                            <label for="patient-consent" class="ml-1"><?= lang('patient_consent_email') ?></label>
                        </div>
                    </div>
                    <input type="text" id="email" class="textInput mb-2" maxlength="120" data-validate="email, dnsCheck"/>
                    <span class="errorMessage"></span>
                    <span class="errorMessage errorMessage--email"></span>
                </div>

                <div class="formGroup">
                    <div class="d-flex justify-content-between">
                        <label for="mobile-number" class="control-label"><?= lang('mobile_number') ?></label>
                        <div>
                            <input type="checkbox" id="patient-consent-sms" name="patient-consent-sms">
                            <label for="patient-consent-sms" class="ml-1"><?= lang('patient_consent_sms') ?></label>
                        </div>
                    </div>
                    <input type="tel" id="mobile-number" class="required textInput mb-2" maxlength="12" data-mask="000-000-0000" data-validate="required, phone"/>
                    <span class="errorMessage"></span>
                </div>
            </div>

            <!-- SECOND COLUMN -->
            <div class="bookForm__column">
              <h4>Patient Information Cont.</h4>
              <div class="formGroup">
                  <label for="phone-number" class="control-label"><?= lang('phone_number') ?> (<?= lang('Optional') ?>)</label>
                  <input type="tel" id="phone-number" class="textInput mb-2" maxlength="12" data-mask="000-000-0000" data-validate="altPhone"/>
                  <span class="errorMessage"></span>
              </div>

              <div class="formGroup">
                  <label for="address" class="control-label"><?= lang('address') ?></label>
                  <input type="text" id="address" class="required textInput" maxlength="120" data-validate="required, address" data-format-string="titleCase"/>
                  <span class="errorMessage"></span>
              </div>

              <div class="formGroup">
                  <label for="apt" class="control-label"><?= lang('apt') ?></label>
                  <input type="text" id="apt" class="textInput" maxlength="120" data-validate="specialChars" data-format-string="titleCase"/>
                  <span class="errorMessage"></span>
              </div>

              <div class="inlineGroup@lg">
                  <div class="formGroup formGroup--equal">
                      <label for="city" class="control-label"><?= lang('city') ?></label>
                      <input type="text" id="city" class="required textInput" maxlength="120" data-validate="required, address" data-format-string="titleCase"/>
                      <span class="errorMessage"></span>
                  </div>

                  <div class="formGroup formGroup--equal">
                      <label for="county" class="control-label"><?= lang('county') ?> (<?= lang('Optional') ?>)</label>
                      <input type="text" id="county" class="textInput" maxlength="120" data-validate="name" data-format-string="titleCase"/>
                      <span class="errorMessage"></span>
                  </div>
              </div>

              <div class="inlineGroup@lg">
                  <div class="formGroup fullWidth">
                      <label for="state" class="control-label"><?= lang('state') ?></label>
                      <select name="state" id="state" class="required selectInput d-block" data-validate="required">
                          <?= include 'includes/state_options.php' ?>
                      </select>
                      <span class="errorMessage"></span>
                  </div>

                  <div class="formGroup">
                      <label for="zip-code" class="control-label"><?= lang('zip_code') ?></label>
                      <input type="tel" id="zip-code" class="required textInput" maxlength="5" data-mask="00000" data-validate="required, numeric, zipcode"/>
                      <span class="errorMessage"></span>
                  </div>
              </div>

              <div class="formGroup">
                  <label for="ssn" class="control-label"><?= lang('ssn') ?> (<?= lang('Optional') ?>)</label>
                  <input type="text" id="ssn" class="textInput textInput--small" maxlength="4" data-mask="0000" data-validate="ssn"/>
                  <span class="errorMessage"></span>
              </div>
            </div>

            <!-- THIRD COLUMN -->
            <div class="bookForm__column">
                <h4><?= lang('pcp_info_title') ?> (<?= lang('Optional') ?>)</h4>
                <div class="inlineGroup@lg">
                    <div class="formGroup formGroup--equal">
                        <label for="pcp-first-name" class="control-label"><?= lang('first_name') ?></label>
                        <input type="text" id="pcp-first-name" class="textInput" maxlength="120" data-validate="name" data-format-string="titleCase" />
                        <span class="errorMessage"></span>
                    </div>

                    <div class="formGroup formGroup--equal">
                        <label for="pcp-last-name" class="control-label"><?= lang('last_name') ?></label>
                        <input type="text" id="pcp-last-name" class="textInput" maxlength="120" data-validate="name" data-format-string="titleCase" />
                        <span class="errorMessage"></span>
                    </div>
                </div>

                <div id="pcp-section" class="hide">
                    <div class="formGroup">
                        <label for="pcp-phone-number" class="control-label"><?= lang('phone') ?></label>
                        <input type="tel" id="pcp-phone-number" class="textInput mb-2" maxlength="12" data-mask="000-000-0000" data-validate="altPhone"/>
                        <span class="errorMessage"></span>
                    </div>

                    <div class="formGroup">
                        <label for="pcp-address" class="control-label"><?= lang('address') ?></label>
                        <input type="text" id="pcp-address" class="textInput" maxlength="120" data-validate="address" data-format-string="titleCase"/>
                        <span class="errorMessage"></span>
                    </div>

                    <div class="formGroup">
                        <label for="pcp-city" class="control-label"><?= lang('city') ?></label>
                        <input type="text" id="pcp-city" class="textInput" maxlength="120" data-validate="address" data-format-string="titleCase"/>
                        <span class="errorMessage"></span>
                    </div>

                    <div class="inlineGroup@lg">
                        <div class="formGroup fullWidth">
                            <label for="pcp-state" class="control-label"><?= lang('state') ?></label>
                            <select name="pcp-state" id="pcp-state" class="selectInput d-block" data-validate="">
                                <?= include 'includes/state_options.php' ?>
                            </select>
                            <span class="errorMessage"></span>
                        </div>

                        <div class="formGroup">
                            <label for="pcp-zip-code" class="control-label"><?= lang('zip_code') ?></label>
                            <input type="tel" id="pcp-zip-code" class="textInput" maxlength="5" data-mask="00000" data-validate="numeric, zipcode"/>
                            <span class="errorMessage"></span>
                        </div>
                    </div>
                </div>
                <div id="doctor-section">
                    <h4><?= lang('doctor_info_title') ?></h4>
                    <div class="formGroup">
                        <label for="provider-patient-id" class="control-label"><?= lang('provider_patient_id') ?> (<?= lang('Optional') ?>)</label>
                        <input type="text" id="provider-patient-id" class="textInput" maxlength="120" />
                        <span class="errorMessage"></span>
                    </div>

                    <div class="formGroup">
                        <label for="doctor-npi" class="control-label"><?= lang('doctor_npi') ?></label>
                        <input type="text" id="doctor-npi" class="textInput" maxlength="120"/>
                        <span class="errorMessage"></span>
                    </div>

                    <div class="inlineGroup@lg">
                        <div class="formGroup formGroup--equal">
                            <label for="doctor-first-name" class="control-label"><?= lang('first_name') ?></label>
                            <input type="text" id="doctor-first-name" class="textInput required" maxlength="120" data-validate="required, name" data-format-string="titleCase"/>
                            <span class="errorMessage"></span>
                        </div>

                        <div class="formGroup formGroup--equal">
                            <label for="doctor-last-name" class="control-label"><?= lang('last_name') ?></label>
                            <input type="text" id="doctor-last-name" class="textInput required" maxlength="120" data-validate="required, name" data-format-string="titleCase"/>
                            <span class="errorMessage"></span>
                        </div>
                    </div>

                    <div class="formGroup">
                        <label for="doctor-phone-number" class="control-label"><?= lang('phone') ?></label>
                        <input type="tel" id="doctor-phone-number" class="textInput required" maxlength="12" data-mask="000-000-0000" data-validate="required, phone"/>
                        <span class="errorMessage"></span>
                    </div>

                    <div class="formGroup">
                        <label for="doctor-address" class="control-label"><?= lang('address') ?></label>
                        <input type="text" id="doctor-address" class="textInput required" maxlength="120" data-validate="required, address" data-format-string="titleCase"/>
                        <span class="errorMessage"></span>
                    </div>

                    <div class="formGroup">
                        <label for="doctor-city" class="control-label"><?= lang('city') ?></label>
                        <input type="text" id="doctor-city" class="textInput required" maxlength="120" data-validate="required, address" data-format-string="titleCase"/>
                        <span class="errorMessage"></span>
                    </div>

                    <div class="inlineGroup@lg">
                        <div class="formGroup fullWidth">
                            <label for="doctor-state" class="control-label"><?= lang('state') ?></label>
                            <select name="doctor-state" id="doctor-state" class="selectInput required d-block" data-validate="required">
                                <?= include 'includes/state_options.php' ?>
                            </select>
                            <span class="errorMessage"></span>
                        </div>

                        <div class="formGroup">
                            <label for="doctor-zip-code" class="control-label"><?= lang('zip_code') ?></label>
                            <input type="tel" id="doctor-zip-code" class="textInput required" maxlength="5" data-mask="00000" data-validate="required, zipcode"/>
                            <span class="errorMessage"></span>
                        </div>
                    </div>

                    <div class="formGroup">
                        <label for="rx-date" class="control-label"><?= lang('rx_date') ?></label>
                        <input type="tel" id="rx-date" class="textInput required" maxlength="10" data-mask="00-00-0000" data-validate="required, date, futureDate"/>
                        <span class="errorMessage"></span>
                    </div>
                </div>

                <div class="command-buttons">
                    <button type="button" id="button-next-1" class="btn button-next btn-primary" data-step_index="1">
                        <?= !$manage_mode ? lang('confirm') : lang('update') ?>
                    </button>
                    <p class="submitErrorMessage hide py-2"></p>
                </div>
            </div>
        </div>
    </div>

    <!-- SELECT APPOINTMENT DATE -->
    <div id="wizard-frame-2" class="px-4 pb-4 d-none" style="display:none;">
        <h3 class="mb-4"><?= lang('step_four_title') ?></h3>
        <div class="frame-content row">
            <div id="appointment-details" class="col-xs-12 col-sm-6"></div>
            <div id="customer-details" class="col-xs-12 col-sm-6"></div>
        </div>
        <?php if ($this->settings_model->get_setting('require_captcha') === '1'): ?>
            <div class="frame-content row">
                <div class="col-xs-12 col-sm-6">
                <h4 class="captcha-title">
                    <?= lang('captcha') ?>
                    <i class="fas fa-sync-alt"></i>
                </h4>
                <img class="captcha-image" src="<?= site_url('captcha') ?>">
                <input class="captcha-text" type="text" value="" />
                <span id="captcha-hint" class="help-block" style="opacity:0">&nbsp;</span>
            </div>
        </div>
        <?php endif; ?>
        <div class="command-buttons">
            <button type="button" id="button-back-2" class="btn button-back btn-default" data-step_index="2">
                <span class="glyphicon glyphicon-backward"></span>
                <?= lang('back') ?>
            </button>
            <form id="book-appointment-form" style="display:inline-block" method="post">
                <button id="book-appointment-submit" type="button" class="btn btn-success">
                    <span class="glyphicon glyphicon-ok"></span>
                    <?= !$manage_mode ? lang('confirm') : lang('update') ?>
                </button>
                <input type="hidden" id="last_used_service_id" />
                <input type="hidden" id="business_service_id" value="<?= $business_service_id ?>" />
                <input type="hidden" id="priority_service_id" value="<?= $priority_service_id ?>" />
                <input type="hidden" name="csrfToken" />
                <input type="hidden" name="post_data" />
            </form>
        </div>
    </div>

    <!-- Schedule an appointment -->
    <aside class="sidePanel">
        <div class="sidePanelTrigger" id="sidePanelTrigger">
          <div class="sidePanelTrigger__content">
            Schedule Appointment
          </div>
        </div>

        <div class="sidePanel__content">
            <div class="mb-3">
                <h4 class="mb-4"><?= lang('step_two_title') ?></h4>
                <button type="button" id="refresh_hours" class="btn btn-primary">
                    <i class="fas fa-redo-alt mr-2"></i>
                    <?= lang('refresh_hours'); ?>
                </button>
            </div>

            <?php if($manage_mode): ?>
                <?php $appointmentPrevious = date('Y-m-d', strtotime($appointment_data['start_datetime'])) < date('Y-m-d') ? true : false; ?>
                <?php $appointmentToday = date('Y-m-d', strtotime($appointment_data['start_datetime'])) === date('Y-m-d') ? true : false; ?>
                <?php $rescheduleAllowed =  !$appointmentPrevious && !$appointmentToday; ?>

                <p class='my-4'>Current appointment <?= ($appointmentPrevious ? "was" : "is") ?><br/>
                <b><?= date('F jS Y, g:i A', strtotime($appointment_data['start_datetime'])) ?></b>
                <?php if($rescheduleAllowed): ?>
                    <button class='editAppt btn btn--simple lineHeight--1 px-0 py-0'>
                    <i class='fas fa-edit ml-2'></i></button>
                <?php else: ?>
                    <p>Note: You cannot reschedule an appointment that is today or was in the past.</p>
                <?php endif; ?>
                </p>
            <?php endif; ?>

            <div class="sidePanel__schedule-appointment <?= $manage_mode ? 'hide' : '' ?>">
                <div class="bookAppointment__calendarSpinner hide">
                    <span class="spinner spinner--large"></span>
                </div>
                <div id="select-date" class="mb-3"></div>
                <div id="available-hours" class="bookHours mb-3"></div>
                <input type="hidden" id="original-select-start-date" value="<?= $manage_mode ? $appointment_data['start_datetime'] : '' ?>" />
                <input type="hidden" id="original-select-end-date" value="<?= $manage_mode ? $appointment_data['end_datetime'] : '' ?>" />
                <input type="hidden" id="rescheduleAllowed" value="<?= $manage_mode && $rescheduleAllowed ? '1' : '0' ?>" />
            </div>
            <div class="h5 font-weight-normal" id="appointments_remaining_wrapper" style="display: none;">
                <?= lang('total_appointments_left') ?>
                <span id="appointments_remaining_days"></span> <?= lang('total_appointments_left_days') ?>: <span id="appointments_remaining" class="font-weight-bold"></span>
            </div>
        </div>
    </aside>
</div>

    <!-- GOOGLE API ADDRESS AUTOCOMPLETE -->
    <?php
      // If ip address is anything other than ipv4 then do not load and use integrated validation
      $ip = $_SERVER['REMOTE_ADDR'];
      if(filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) && !empty($google_api_key)) : ?>
        <script src="<?= asset_url('assets/js/address_autocomplete.js') ?>"></script>
        <script src="https://maps.googleapis.com/maps/api/js?key=<?= $google_api_key ?>&libraries=places&callback=initAutocomplete" async defer></script>
    <?php endif; ?>

<?php if ($manage_mode): ?>
    <?php require 'delete_patient_confirmation_modal.php' ?>
<?php endif ?>


<script>
    var GlobalVariables = {
        availableServices   : <?= json_encode($available_services) ?>,
        availableProviders  : <?= json_encode($available_providers) ?>,
        baseUrl             : <?= json_encode(config('base_url')) ?>,
        manageMode          : <?= $manage_mode ? 'true' : 'false' ?>,
        customerToken       : <?= json_encode($customer_token) ?>,
        dateFormat          : <?= json_encode($date_format) ?>,
        timeFormat          : <?= json_encode($time_format) ?>,
        appointmentData     : <?= json_encode($appointment_data) ?>,
        providerData        : <?= json_encode($provider_data) ?>,
        customerData        : <?= json_encode($customer_data) ?>,
        csrfToken           : <?= json_encode($this->security->get_csrf_hash()) ?>,
        wasDeleted          : <?= json_encode($wasDeleted) ?>,
        user                : {
            id         : <?= $user_id ?>,
            email      : <?= json_encode($user_email) ?>,
            role_slug  : <?= json_encode($role_slug) ?>,
            privileges : <?= json_encode($privileges) ?>
        }
    };

    var EALang = <?= json_encode($this->lang->language) ?>;
    var availableLanguages = <?= json_encode($this->config->item('available_languages')) ?>;
</script>
<script src="<?= asset_url('assets/js/backend_book_api.js') ?>"></script>
<script src="<?= asset_url('assets/js/backend_book.js') ?>"></script>
<script src="<?= asset_url('assets/js/backend_book_helpers.js') ?>"></script>
<script>
    $(document).ready(function() {
        BackendBook.initialize(true, GlobalVariables.manageMode);
        GeneralFunctions.enableLanguageSelection($('#select-language'));
    });
</script>