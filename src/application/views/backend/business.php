<div class="px-4 mb-4 d-flex flex-column align-items-md-center">
    <!-- BUSINESS SEARCH BAR -->
    <div id="filter-business" class="search">
        <form method="get" id="business-search">
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
        </form>
    </div>

    <h1 class="h3 mb-4">
      <?= lang('business_information') ?>
    </h1>
    <form method="post" id="business-form">
        <div class="formGroup">
            <label for="business_name">
                <?= lang('business_name') ?>
            </label>
            <input class="textInput required" type="text" name="business_name" id="business_name" maxlength="120" data-validate="required" data-format-string="titleCase">
            <span class="errorMessage"></span>
        </div>

        <fieldset>
            <legend class="h6">
              <?= lang('owner_auth_rep') ?>
            </legend>
            <div class="inlineGroup@md">
                <div class="formGroup">
                    <label for="owner_first_name">
                        <?= lang('first_name') ?>
                    </label>
                    <input class="textInput required" type="text" name="owner_first_name" id="owner_first_name" maxlength="120" data-validate="required, name" data-format-string="titleCase">
                    <span class="errorMessage"></span>
                </div>
                <div class="formGroup">
                  <label for="owner_last_name">
                      <?= lang('last_name') ?>
                  </label>
                  <input class="textInput required" type="text" name="owner_last_name" id="owner_last_name" maxlength="120" data-validate="required, name" data-format-string="titleCase">
                  <span class="errorMessage"></span>
                </div>
            </div>
        </fieldset>

        <hr class="mt-0">
        <div class="formGroup">
            <label for="phone_number" class="control-label"><?= lang('business_phone') ?></label>
            <input type="tel" name="business_phone" id="business_phone" class="textInput required" maxlength="12" data-mask="000-000-0000" data-validate="required, phone"/>
            <span class="errorMessage"></span>
        </div>

        <div class="formGroup">
            <label for="mobile_phone" class="control-label"><?= lang('mobile_number') ?> (<?= lang('Optional') ?>)</label>
            <input type="tel" name="mobile_phone" id="mobile_phone" class="textInput mb-2" maxlength="12" data-mask="000-000-0000"/>
            <span class="errorMessage"></span>
            <input type="checkbox" name="consent_sms" id="consent_sms"/>
            <label for="consent_sms" class="ml-1"><?= lang('patient_consent_sms') ?></label>
        </div>

        <div class="formGroup">
            <label for="email" class="control-label"><?= lang('email') ?> (<?= lang('Optional') ?>)</label>
            <input type="text" name="email" id="email" class="textInput" maxlength="120" data-validate="email, dnsCheck"/>
            <span class="errorMessage"></span>
            <span class="errorMessage errorMessage--email"></span>
            <div class="formGroup">
                <input type="checkbox" name="consent_email" id="consent_email"/>
                <label for="consent_email" class="ml-1"><?= lang('patient_consent_email') ?></label>
            </div>
        </div>

        <div class="formGroup">
            <label for="address" class="control-label"><?= lang('business_address') ?></label>
            <input type="text" name="address" id="address" class="textInput required" maxlength="120" data-validate="required, address" data-format-string="titleCase"/>
            <span class="errorMessage"></span>
        </div>

        <div class="formGroup">
            <label for="city" class="control-label"><?= lang('city') ?></label>
            <input type="text" name="city" id="city" class="textInput required" maxlength="120" data-validate="required, address" data-format-string="titleCase"/>
            <span class="errorMessage"></span>
        </div>

        <div class="inlineGroup@md">
            <div class="formGroup fullWidth">
                <label for="state" class="control-label"><?= lang('state') ?></label>
                <select name="state" id="state" class="selectInput d-block required" data-validate="required">
                    <?= include 'includes/state_options.php' ?>
                </select>
                <span class="errorMessage"></span>
            </div>

            <div class="formGroup px-0">
                <label for="zip-code" class="control-label"><?= lang('zip_code') ?></label>
                <input type="tel" name="zip_code" id="zip-code" class="textInput required" maxlength="5" data-mask="00000" data-validate="required, numeric, zipcode"/>
                <span class="errorMessage"></span>
            </div>
        </div>

        <?php if ($manage_mode) { ?>
        <?php foreach ($business_requests as $request) { ?>
        <div class="card">
            <p class="mb-1">
                <?= lang('business_code') ?>:
                <strong>
                    <?= $request['business_code'] ?>
                </strong>
            </p>
            <p class="mb-1">
            <?= lang('business_status') ?>:
                <?php
                switch ($request['status']) {
                    case 'pending':
                        $class = 'colorYellow';
                    break;
                    case 'active':
                        $class = 'colorGreen';
                    break;
                    case 'deleted':
                        $class = 'colorRed';
                    break;
                    default:
                        $class = '';
                    break;
                }
                ?>
                <strong class="<?= $class ?>">
                    <?= $request['status'] ?>
                </strong>
            </p>
            <p class="mb-1">
                <?= lang('slots_requested') ?>:
                <strong>
                  <?= $request['slots_requested'] ?>
                </strong>
            </p>
            <p class="mb-1">
                <?= lang('slots_approved') ?>:
                <strong>
                  <?= $request['slots_approved'] ?>
                </strong>
            </p>
            <p class="mb-1">
                <?= lang('slots_used') ?>:
                <strong>
                  <?= $request['total_slots_used'] ?>
                </strong>
            </p>
        </div>
        <?php } ?>
        <?php } else { ?>
        <div class="formGroup">
            <label for="slots_requested" class="control-label"><?= lang('requested_number_appts') ?></label>
            <input type="tel" name="slots_requested" id="slots_requested" class="textInput required w-auto" maxlength="120" data-validate="required, numeric"/>
            <span class="errorMessage"></span>
        </div>
        <?php } ?>

        <p class="submitErrorMessage hide"></p>

        <?php if ($manage_mode) { ?>
        <input type="hidden" name="id" value="<?= $business['id'] ?>" />
        <?php } ?>
        <input type="hidden" name="csrfToken" value="<?= $this->security->get_csrf_hash() ?>" />

        <div class="d-flex">
            <button id="business-submit" type="button" class="btn btn-primary btn--spinner d-flex px-4">
                <?= !$manage_mode ? lang('confirm') : lang('update') ?>
            </button>
            <?php if ($manage_mode) { ?>
            <a href="/backend/business" id="edit-business-cancel" type="button" class="btn btn--simple px-o ml-1" title="Cancel">
                <?= lang('Cancel') ?>
            </a>
            <?php } ?>
        </div>
    </form>
</div>

<!-- GOOGLE API ADDRESS AUTOCOMPLETE -->
<?php
  // If ip address is anything other than ipv4 then do not load and use integrated validation
  $ip = $_SERVER['REMOTE_ADDR'];
  if(filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) && !empty($google_api_key)) : ?>
    <script src="<?= asset_url('assets/js/business_address_autocomplete.js') ?>"></script>
    <script src="https://maps.googleapis.com/maps/api/js?key=<?= $google_api_key ?>&libraries=places&callback=initAutocomplete" async defer></script>
<?php endif; ?>

<script>
    var GlobalVariables = {
        baseUrl             : <?= json_encode(config('base_url')) ?>,
        csrfToken           : <?= json_encode($this->security->get_csrf_hash()) ?>,
        user                : {
            id         : <?= $user_id ?>,
            email      : <?= json_encode($user_email) ?>,
            role_slug  : <?= json_encode($role_slug) ?>,
            privileges : <?= json_encode($privileges) ?>
        },
        business_requests   :  <?= json_encode($business_requests) ?>,
        business   :  <?= json_encode($business) ?>,
        manage_mode   :  <?= json_encode($manage_mode) ?>
    };

    var EALang = <?= json_encode($this->lang->language) ?>;
    var availableLanguages = <?= json_encode($this->config->item('available_languages')) ?>;
</script>

<script src="<?= asset_url('assets/js/book-validations.js') ?>"></script>
<script src="<?= asset_url('assets/js/backend_business.js') ?>"></script>
<script src="<?= asset_url('assets/js/backend_business_helpers.js') ?>"></script>
<script>
    $(document).ready(function() {
        BackendBusiness.initialize(true);
    });
</script>
