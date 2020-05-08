<script src="<?= asset_url('assets/js/backend_users_admins.js') ?>"></script>
<script src="<?= asset_url('assets/js/backend_users_providers.js') ?>"></script>
<script src="<?= asset_url('assets/js/backend_users_secretaries.js') ?>"></script>
<script src="<?= asset_url('assets/js/backend_users_city_admin.js') ?>"></script>
<script src="<?= asset_url('assets/js/backend_users_city_business.js') ?>"></script>
<script src="<?= asset_url('assets/js/backend_users.js') ?>"></script>
<script src="<?= asset_url('assets/js/working_plan.js') ?>"></script>
<script src="<?= asset_url('assets/ext/jquery-ui/jquery-ui-timepicker-addon.js') ?>"></script>
<script src="<?= asset_url('assets/ext/jquery-jeditable/jquery.jeditable.min.js') ?>"></script>

<script>
    var GlobalVariables = {
        csrfToken      : <?= json_encode($this->security->get_csrf_hash()) ?>,
        baseUrl        : <?= json_encode($base_url) ?>,
        dateFormat     : <?= json_encode($date_format) ?>,
        timeFormat     : <?= json_encode($time_format) ?>,
        admins         : <?= json_encode($admins) ?>,
        providers      : <?= json_encode($providers) ?>,
        secretaries    : <?= json_encode($secretaries) ?>,
        services       : <?= json_encode($services) ?>,
        workingPlan    : <?= json_encode(json_decode($working_plan)) ?>,
        user           : {
            id         : <?= $user_id ?>,
            email      : <?= json_encode($user_email) ?>,
            role_slug  : <?= json_encode($role_slug) ?>,
            privileges : <?= json_encode($privileges) ?>
        }
    };

    $(document).ready(function() {
        BackendUsers.initialize(true);
    });
</script>

<div id="users-page" class="container-fluid backend-page mt-3">

    <!-- PAGE NAVIGATION -->

    <ul class="nav nav-pills" role="tablist">
        <li role="presentation"><a class="nav-link active" href="#providers" aria-controls="providers" role="tab" data-toggle="tab"><?= lang('sites') ?></a></li>
        <li role="presentation"><a class="nav-link" href="#secretaries" aria-controls="secretaries" role="tab" data-toggle="tab"><?= lang('secretaries') ?></a></li>
        <li role="presentation"><a class="nav-link" href="#admins" aria-controls="admins" role="tab" data-toggle="tab"><?= lang('admins') ?></a></li>
        <li role="presentation"><a class="nav-link" href="#city-admin" aria-controls="city admin" role="tab" data-toggle="tab"><?= lang('city_admins') ?></a></li>
        <li role="presentation"><a class="nav-link" href="#city-business" aria-controls="city business request admin" role="tab" data-toggle="tab"><?= lang('city_business_request_admins') ?></a></li>
    </ul>

    <div class="tab-content">

        <!-- SITES TAB -->

        <div role="tabpanel" class="tab-pane active" id="providers">
            <div class="row">
                <div id="filter-providers" class="filter-records col-md-5">
                    <form>
                        <div class="input-group mb-4">
                            <input type="text" class="col key textInput textInput--simple" placeholder="Search">

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
                    </form>
                    <h3><?= lang('sites') ?></h3>
                    <div class="results"></div>
                </div>

                <div class="record-details col-md-7">
                  <div class="mb-5">
                    <p class="h5"><?= lang('current_view') ?></p>
                    <div class="d-flex switch-view nav-pills bg-white">
                      <div class="display-details nav-link active"><?= lang('details') ?></div>
                      <div class="display-working-plan nav-link"><?= lang('working_plan') ?></div>
                    </div>
                  </div>

                  <div class="d-flex mb-4">
                    <div class="add-edit-delete-group">
                        <button id="add-provider" class="btn btn-primary">
                            <i class="fas fa-plus"></i>
                            <?= lang('add') ?>
                        </button>
                        <button id="edit-provider" class="btn btn--simple" disabled="disabled">
                            <i class="far fa-edit"></i>
                            <?= lang('edit') ?>
                        </button>
                        <button id="delete-provider" class="btn btn--simple" disabled="disabled">
                            <i class="far fa-trash-alt"></i>
                            <?= lang('delete') ?>
                        </button>
                    </div>

                    <div class="save-cancel-group" style="display:none;">
                        <button id="save-provider" class="btn btn-primary">
                            <i class="far fa-check-circle"></i>
                            <?= lang('save') ?>
                        </button>
                        <button id="cancel-provider" class="btn btn--simple">
                            <i class="fas fa-ban"></i>
                            <?= lang('cancel') ?>
                        </button>
                    </div>
                  </div>

                    <?php // This form message is outside the details view, so that it can be
                    // visible when the user has working plan view active. ?>
                    <div class="form-message alert" style="display:none;"></div>

                    <div class="details-view provider-view">
                        <h3><?= lang('provider_details') ?></h3>

                        <input type="hidden" id="provider-id" class="record-id">

                        <div class="row">
                            <div class="col-sm-6">
                                <div class="formGroup">
                                    <label for="provider-first-name"><?= lang('first_name') ?> *</label>
                                    <input type="text" id="provider-first-name" class="textInput required" maxlength="120">
                                </div>

                                <div class="formGroup">
                                    <label for="provider-last-name"><?= lang('last_name') ?> *</label>
                                    <input type="text" id="provider-last-name" class="textInput required" maxlength="120">
                                </div>

                                <div class="formGroup">
                                    <label for="provider-email"><?= lang('email') ?> *</label>
                                    <input type="email" id="provider-email" class="textInput required" max-length="120">
                                </div>

                                <div class="formGroup">
                                    <label for="provider-phone-number"><?= lang('phone_number') ?> *</label>
                                    <input type="tel" id="provider-phone-number" class="textInput required" max-length="120" data-mask="000-000-0000">
                                </div>

                                <div class="formGroup">
                                    <label for="provider-mobile-number"><?= lang('mobile_number') ?></label>
                                    <input type="tel" id="provider-mobile-number" class="textInput" maxlength="12" data-mask="000-000-0000">
                                </div>

                                <div class="formGroup">
                                    <label for="provider-address"><?= lang('address') ?></label>
                                    <input type="text" id="provider-address" class="textInput" maxlength="120">
                                </div>

                                <div class="formGroup">
                                    <label for="provider-city"><?= lang('city') ?></label>
                                    <input type="text" id="provider-city" class="textInput" maxlength="120">
                                </div>

                                <div class="formGroup">
                                    <label for="provider-state"><?= lang('state') ?></label>
                                    <select name="provider-state" id="provider-state" class="selectInput d-block">
                                        <option value="AL">Alabama</option>
                                        <option value="AK">Alaska</option>
                                        <option value="AZ">Arizona</option>
                                        <option value="AR">Arkansas</option>
                                        <option value="CA">California</option>
                                        <option value="CO">Colorado</option>
                                        <option value="CT">Connecticut</option>
                                        <option value="DE">Delaware</option>
                                        <option value="DC">District Of Columbia</option>
                                        <option value="FL">Florida</option>
                                        <option value="GA">Georgia</option>
                                        <option value="HI">Hawaii</option>
                                        <option value="ID">Idaho</option>
                                        <option value="IL">Illinois</option>
                                        <option value="IN">Indiana</option>
                                        <option value="IA">Iowa</option>
                                        <option value="KS">Kansas</option>
                                        <option value="KY">Kentucky</option>
                                        <option value="LA">Louisiana</option>
                                        <option value="ME">Maine</option>
                                        <option value="MD">Maryland</option>
                                        <option value="MA">Massachusetts</option>
                                        <option value="MI" selected>Michigan</option>
                                        <option value="MN">Minnesota</option>
                                        <option value="MS">Mississippi</option>
                                        <option value="MO">Missouri</option>
                                        <option value="MT">Montana</option>
                                        <option value="NE">Nebraska</option>
                                        <option value="NV">Nevada</option>
                                        <option value="NH">New Hampshire</option>
                                        <option value="NJ">New Jersey</option>
                                        <option value="NM">New Mexico</option>
                                        <option value="NY">New York</option>
                                        <option value="NC">North Carolina</option>
                                        <option value="ND">North Dakota</option>
                                        <option value="OH">Ohio</option>
                                        <option value="OK">Oklahoma</option>
                                        <option value="OR">Oregon</option>
                                        <option value="PA">Pennsylvania</option>
                                        <option value="RI">Rhode Island</option>
                                        <option value="SC">South Carolina</option>
                                        <option value="SD">South Dakota</option>
                                        <option value="TN">Tennessee</option>
                                        <option value="TX">Texas</option>
                                        <option value="UT">Utah</option>
                                        <option value="VT">Vermont</option>
                                        <option value="VA">Virginia</option>
                                        <option value="WA">Washington</option>
                                        <option value="WV">West Virginia</option>
                                        <option value="WI">Wisconsin</option>
                                        <option value="WY">Wyoming</option>
                                        </select>
                                </div>

                                <div class="formGroup">
                                    <label for="provider-zip-code"><?= lang('zip_code') ?></label>
                                    <input type="tel" id="provider-zip-code" class="textInput" maxlength="5" data-mask="00000">
                                </div>

                                <div class="formGroup">
                                    <label for="provider-notes"><?= lang('notes') ?></label>
                                    <textarea id="provider-notes" class="textInput textInput--text-area" rows="3"></textarea>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="formGroup">
                                    <label for="provider-username"><?= lang('username') ?> *</label>
                                    <input id="provider-username" class="textInput required" maxlength="120">
                                </div>

                                <div class="formGroup">
                                    <label for="provider-password"><?= lang('password') ?> *</label>
                                    <input type="password" id="provider-password" class="textInput required" maxlength="120">
                                </div>

                                <div class="formGroup">
                                    <label for="provider-password-confirm"><?= lang('retype_password') ?> *</label>
                                    <input type="password" id="provider-password-confirm" class="textInput required" maxlength="120">
                                </div>

                                <div class="formGroup">
                                    <label for="provider-business-service-id"><?= lang('business_service_label') ?></label>
                                    <input type="tel" id="provider-business-service-id" class="textInput" maxlength="5" data-mask="000">
                                </div>

                                <div class="formGroup">
                                    <label for="provider-business-service-id"><?= lang('priority_service_label') ?></label>
                                    <input type="tel" id="provider-priority-service-id" class="textInput" maxlength="5" data-mask="000">
                                </div>

                                <div class="formGroup">
                                    <label for="provider-calendar-view"><?= lang('calendar') ?> *</label>
                                    <select id="provider-calendar-view" class="selectInput required">
                                        <option value="default">Default</option>
                                        <option value="table">Table</option>
                                    </select>
                                </div>

                                <br>

                                <button type="button" id="provider-notifications" class="btn btn--simple" data-toggle="button">
                                    <i class="far fa-envelope"></i>
                                    <span><?= lang('receive_notifications') ?></span>
                                </button>

                                <br><br>

                                <h4><?= lang('services') ?></h4>
                                <div id="provider-services" class="well"></div>
                            </div>
                        </div>
                    </div>

                    <div class="working-plan-view provider-view" style="display: none;">
                        <h3><?= lang('working_plan') ?></h3>
                        <button id="reset-working-plan" class="btn btn-primary mb-3">
                            <i class="fas fa-redo-alt"></i>
                            <?= lang('reset_plan') ?></button>
                        <table class="working-plan table table-striped">
                            <thead>
                                <tr>
                                    <th><?= lang('day') ?></th>
                                    <th><?= lang('start') ?></th>
                                    <th><?= lang('end') ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>
                                        <div class="checkbox">
                                            <label>
                                                <input class="mr-1" type="checkbox" id="sunday">
                                                <?= lang('sunday') ?>
                                            </label>
                                        </div>
                                    </td>
                                    <td><input id="sunday-start" class="work-start textInput input-sm"></td>
                                    <td><input id="sunday-end" class="work-end textInput input-sm"></td>
                                </tr>
                                <tr>
                                    <td>
                                        <div class="checkbox">
                                            <label>
                                                <input class="mr-1" type="checkbox" id="monday">
                                                <?= lang('monday') ?>
                                            </label>
                                        </div>
                                    </td>
                                    <td><input id="monday-start" class="work-start textInput input-sm"></td>
                                    <td><input id="monday-end" class="work-end textInput input-sm"></td>
                                </tr>
                                <tr>
                                    <td>
                                        <div class="checkbox">
                                            <label>
                                                <input class="mr-1" type="checkbox" id="tuesday">
                                                <?= lang('tuesday') ?>
                                            </label>
                                        </div>
                                    </td>
                                    <td><input id="tuesday-start" class="work-start textInput input-sm"></td>
                                    <td><input id="tuesday-end" class="work-end textInput input-sm"></td>
                                </tr>
                                <tr>
                                    <td>
                                        <div class="checkbox">
                                            <label>
                                                <input class="mr-1" type="checkbox" id="wednesday">
                                                <?= lang('wednesday') ?>
                                            </label>
                                        </div>
                                    </td>
                                    <td><input id="wednesday-start" class="work-start textInput input-sm"></td>
                                    <td><input id="wednesday-end" class="work-end textInput input-sm"></td>
                                </tr>
                                <tr>
                                    <td>
                                        <div class="checkbox">
                                            <label>
                                                <input class="mr-1" type="checkbox" id="thursday">
                                                <?= lang('thursday') ?>
                                            </label>
                                        </div>
                                    </td>
                                    <td><input id="thursday-start" class="work-start textInput input-sm"></td>
                                    <td><input id="thursday-end" class="work-end textInput input-sm"></td>
                                </tr>
                                <tr>
                                    <td>
                                        <div class="checkbox">
                                            <label>
                                                <input class="mr-1" type="checkbox" id="friday">
                                                <?= lang('friday') ?>
                                            </label>
                                        </div>
                                    </td>
                                    <td><input id="friday-start" class="work-start textInput input-sm"></td>
                                    <td><input id="friday-end" class="work-end textInput input-sm"></td>
                                </tr>
                                <tr>
                                    <td>
                                        <div class="checkbox">
                                            <label>
                                                <input class="mr-1" type="checkbox" id="saturday">
                                                <?= lang('saturday') ?>
                                            </label>
                                        </div>
                                    </td>
                                    <td><input id="saturday-start" class="work-start textInput input-sm"></td>
                                    <td><input id="saturday-end" class="work-end textInput input-sm"></td>
                                </tr>
                            </tbody>
                        </table>

                        <br>

                        <h3><?= lang('breaks') ?></h3>

                        <p class="help-block mb-2">
                            <?= lang('add_breaks_during_each_day') ?>
                        </p>

                        <div>
                            <button type="button" class="add-break btn btn-primary">
                                <i class="fas fa-plus"></i>
                                <?= lang('add_break') ?>
                            </button>
                        </div>

                        <br>

                        <table class="breaks table table-striped">
                            <thead>
                                <tr>
                                    <th><?= lang('day') ?></th>
                                    <th><?= lang('start') ?></th>
                                    <th><?= lang('end') ?></th>
                                    <th><?= lang('actions') ?></th>
                                </tr>
                            </thead>
                            <tbody><!-- Dynamic Content --></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- SECRETARIES TAB -->

        <div role="tabpanel" class="tab-pane" id="secretaries">
            <div class="row">
                <div id="filter-secretaries" class="filter-records column col-xs-12 col-sm-5">
                    <form>
                        <div class="input-group mb-4">
                            <input type="text" class="col key textInput textInput--simple" placeholder="Search">

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
                    </form>
                    <h3><?= lang('secretaries') ?></h3>

                    <div class="results"></div>
                </div>

                <div class="record-details column col-xs-12 col-sm-7">
                    <div class="d-flex mb-4">
                        <div class="add-edit-delete-group">
                            <button id="add-secretary" class="btn btn-primary">
                                <i class="fas fa-plus"></i>
                                <?= lang('add') ?>
                            </button>
                            <button id="edit-secretary" class="btn btn--simple" disabled="disabled">
                                <i class="far fa-edit"></i>
                                <?= lang('edit') ?>
                            </button>
                            <button id="delete-secretary" class="btn btn--simple" disabled="disabled">
                                <i class="far fa-trash-alt"></i>
                                <?= lang('delete') ?>
                            </button>
                        </div>

                        <div class="save-cancel-group" style="display:none;">
                            <button id="save-secretary" class="btn btn-primary">
                                <i class="far fa-check-circle"></i>
                                <?= lang('save') ?>
                            </button>
                            <button id="cancel-secretary" class="btn btn--simple">
                                <i class="fas fa-ban"></i>
                                <?= lang('cancel') ?>
                            </button>
                        </div>
                    </div>

                    <h3><?= lang('details') ?></h3>

                    <div class="form-message alert" style="display:none;"></div>

                    <input type="hidden" id="secretary-id" class="record-id">

                    <div class="row">
                        <div class="secretary-details col-xs-12 col-sm-6">
                            <div class="formGroup">
                                <label for="secretary-first-name"><?= lang('first_name') ?> *</label>
                                <input type="text" id="secretary-first-name" class="textInput required" maxlength="120">
                            </div>

                            <div class="formGroup">
                                <label for="secretary-last-name"><?= lang('last_name') ?> *</label>
                                <input type="text" id="secretary-last-name" class="textInput required" maxlength="120">
                            </div>

                            <div class="formGroup">
                                <label for="secretary-email"><?= lang('email') ?> *</label>
                                <input type="email" id="secretary-email" class="textInput required" maxlength="120">
                            </div>

                            <div class="formGroup">
                                <label for="secretary-phone-number"><?= lang('phone_number') ?> *</label>
                                <input type="tel" id="secretary-phone-number" class="textInput required" maxlength="12" data-mask="000-000-0000">
                            </div>

                            <div class="formGroup">
                                <label for="secretary-mobile-number"><?= lang('mobile_number') ?></label>
                                <input type="tel" id="secretary-mobile-number" class="textInput" maxlength="12" data-mask="000-000-0000">
                            </div>

                            <div class="formGroup">
                                <label for="secretary-address"><?= lang('address') ?></label>
                                <input type="text" id="secretary-address" class="textInput" maxlength="120">
                            </div>

                            <div class="formGroup">
                                <label for="secretary-city"><?= lang('city') ?></label>
                                <input type="text" id="secretary-city" class="textInput" maxlength="120">
                            </div>

                            <div class="formGroup">
                                <label for="secretary-state"><?= lang('state') ?></label>
                                <select name="secretary-state" id="secretary-state" class="selectInput d-block">
                                    <option value="AL">Alabama</option>
                                    <option value="AK">Alaska</option>
                                    <option value="AZ">Arizona</option>
                                    <option value="AR">Arkansas</option>
                                    <option value="CA">California</option>
                                    <option value="CO">Colorado</option>
                                    <option value="CT">Connecticut</option>
                                    <option value="DE">Delaware</option>
                                    <option value="DC">District Of Columbia</option>
                                    <option value="FL">Florida</option>
                                    <option value="GA">Georgia</option>
                                    <option value="HI">Hawaii</option>
                                    <option value="ID">Idaho</option>
                                    <option value="IL">Illinois</option>
                                    <option value="IN">Indiana</option>
                                    <option value="IA">Iowa</option>
                                    <option value="KS">Kansas</option>
                                    <option value="KY">Kentucky</option>
                                    <option value="LA">Louisiana</option>
                                    <option value="ME">Maine</option>
                                    <option value="MD">Maryland</option>
                                    <option value="MA">Massachusetts</option>
                                    <option value="MI" selected>Michigan</option>
                                    <option value="MN">Minnesota</option>
                                    <option value="MS">Mississippi</option>
                                    <option value="MO">Missouri</option>
                                    <option value="MT">Montana</option>
                                    <option value="NE">Nebraska</option>
                                    <option value="NV">Nevada</option>
                                    <option value="NH">New Hampshire</option>
                                    <option value="NJ">New Jersey</option>
                                    <option value="NM">New Mexico</option>
                                    <option value="NY">New York</option>
                                    <option value="NC">North Carolina</option>
                                    <option value="ND">North Dakota</option>
                                    <option value="OH">Ohio</option>
                                    <option value="OK">Oklahoma</option>
                                    <option value="OR">Oregon</option>
                                    <option value="PA">Pennsylvania</option>
                                    <option value="RI">Rhode Island</option>
                                    <option value="SC">South Carolina</option>
                                    <option value="SD">South Dakota</option>
                                    <option value="TN">Tennessee</option>
                                    <option value="TX">Texas</option>
                                    <option value="UT">Utah</option>
                                    <option value="VT">Vermont</option>
                                    <option value="VA">Virginia</option>
                                    <option value="WA">Washington</option>
                                    <option value="WV">West Virginia</option>
                                    <option value="WI">Wisconsin</option>
                                    <option value="WY">Wyoming</option>
                                    </select>
                            </div>

                            <div class="formGroup">
                                <label for="secretary-zip-code"><?= lang('zip_code') ?></label>
                                <input type="tel" id="secretary-zip-code" class="textInput" maxlength="5" data-mask="00000">
                            </div>

                            <div class="formGroup">
                                <label for="secretary-notes"><?= lang('notes') ?></label>
                                <textarea id="secretary-notes" class="textInput textInput--text-area" rows="3"></textarea>
                            </div>
                        </div>
                        <div class="secretary-settings col-xs-12 col-sm-6">
                            <div class="formGroup">
                                <label for="secretary-username"><?= lang('username') ?> *</label>
                                <input type="text" id="secretary-username" class="textInput required" maxlength="120">
                            </div>

                            <div class="formGroup">
                                <label for="secretary-password"><?= lang('password') ?> *</label>
                                <input type="password" id="secretary-password" class="textInput required" maxlength="120">
                            </div>

                            <div class="formGroup">
                                <label for="secretary-password-confirm"><?= lang('retype_password') ?> *</label>
                                <input type="password" id="secretary-password-confirm" class="textInput required" maxlength="120">
                            </div>

                            <div class="formGroup">
                                <label for="secretary-calendar-view"><?= lang('calendar') ?> *</label>
                                <select id="secretary-calendar-view" class="selectInput required">
                                    <option value="default">Default</option>
                                    <option value="table">Table</option>
                                </select>
                            </div>

                            <br>

                            <button type="button" id="secretary-notifications" class="btn btn--simple" data-toggle="button">
                                <i class="far fa-envelope"></i>
                                <span><?= lang('receive_notifications') ?></span>
                            </button>

                            <br><br>

                            <h4><?= lang('sites') ?></h4>
                            <div id="secretary-providers" class="well"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ADMINS TAB -->
        <div role="tabpanel" class="tab-pane" id="admins">
            <div class="row">
                <div id="filter-admins" class="filter-records column col-xs-12 col-sm-5">
                    <form>
                        <div class="input-group mb-4">
                            <input type="text" class="col key textInput textInput--simple" placeholder="Search">

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
                    </form>

                    <h3><?= lang('admins') ?></h3>

                    <div class="results"></div>
                </div>

                <div class="record-details column col-xs-12 col-sm-7">
                    <div class="d-flex mb-4">
                        <div class="add-edit-delete-group">
                            <button id="add-admin" class="btn btn-primary">
                                <i class="fas fa-plus"></i>
                                <?= lang('add') ?>
                            </button>
                            <button id="edit-admin" class="btn btn--simple" disabled="disabled">
                                <i class="far fa-edit"></i>
                                <?= lang('edit') ?>
                            </button>
                            <button id="delete-admin" class="btn btn--simple" disabled="disabled">
                                <i class="far fa-trash-alt"></i>
                                <?= lang('delete') ?>
                            </button>
                        </div>

                        <div class="save-cancel-group" style="display:none;">
                            <button id="save-admin" class="btn btn-primary">
                                <i class="far fa-check-circle"></i>
                                <?= lang('save') ?>
                            </button>
                            <button id="cancel-admin" class="btn btn--simple">
                                <i class="fas fa-ban"></i>
                                <?= lang('cancel') ?>
                            </button>
                        </div>
                    </div>

                    <h3><?= lang('details') ?></h3>

                    <div class="form-message alert" style="display:none;"></div>

                    <input type="hidden" id="admin-id" class="record-id">

                    <div class="row">
                        <div class="admin-details col-xs-12 col-sm-6">
                            <div class="formGroup">
                                <label for="first-name"><?= lang('first_name') ?> *</label>
                                <input type="text" id="admin-first-name" class="textInput required" maxlength="120">
                            </div>

                            <div class="formGroup">
                                <label for="admin-last-name"><?= lang('last_name') ?> *</label>
                                <input type="text" id="admin-last-name" class="textInput required" maxlength="120">
                            </div>

                            <div class="formGroup">
                                <label for="admin-email"><?= lang('email') ?> *</label>
                                <input type="email" id="admin-email" class="textInput required" maxlength="120">
                            </div>

                            <div class="formGroup">
                                <label for="admin-phone-number"><?= lang('phone_number') ?> *</label>
                                <input type="tel" id="admin-phone-number" class="textInput required" maxlength="12" data-mask="000-000-0000">
                            </div>

                            <div class="formGroup">
                                <label for="admin-mobile-number"><?= lang('mobile_number') ?></label>
                                <input type="tel" id="admin-mobile-number" class="textInput" maxlength="12" data-mask="000-000-0000">
                            </div>

                            <div class="formGroup">
                                <label for="admin-address"><?= lang('address') ?></label>
                                <input type="text" id="admin-address" class="textInput" maxlength="120">
                            </div>

                            <div class="formGroup">
                                <label for="admin-city"><?= lang('city') ?></label>
                                <input type="text" id="admin-city" class="textInput" maxlength="120">
                            </div>

                            <div class="formGroup">
                                <label for="admin-state"><?= lang('state') ?></label>
                                <select name="admin-state" id="admin-state" class="selectInput d-block">
                                    <option value="AL">Alabama</option>
                                    <option value="AK">Alaska</option>
                                    <option value="AZ">Arizona</option>
                                    <option value="AR">Arkansas</option>
                                    <option value="CA">California</option>
                                    <option value="CO">Colorado</option>
                                    <option value="CT">Connecticut</option>
                                    <option value="DE">Delaware</option>
                                    <option value="DC">District Of Columbia</option>
                                    <option value="FL">Florida</option>
                                    <option value="GA">Georgia</option>
                                    <option value="HI">Hawaii</option>
                                    <option value="ID">Idaho</option>
                                    <option value="IL">Illinois</option>
                                    <option value="IN">Indiana</option>
                                    <option value="IA">Iowa</option>
                                    <option value="KS">Kansas</option>
                                    <option value="KY">Kentucky</option>
                                    <option value="LA">Louisiana</option>
                                    <option value="ME">Maine</option>
                                    <option value="MD">Maryland</option>
                                    <option value="MA">Massachusetts</option>
                                    <option value="MI" selected>Michigan</option>
                                    <option value="MN">Minnesota</option>
                                    <option value="MS">Mississippi</option>
                                    <option value="MO">Missouri</option>
                                    <option value="MT">Montana</option>
                                    <option value="NE">Nebraska</option>
                                    <option value="NV">Nevada</option>
                                    <option value="NH">New Hampshire</option>
                                    <option value="NJ">New Jersey</option>
                                    <option value="NM">New Mexico</option>
                                    <option value="NY">New York</option>
                                    <option value="NC">North Carolina</option>
                                    <option value="ND">North Dakota</option>
                                    <option value="OH">Ohio</option>
                                    <option value="OK">Oklahoma</option>
                                    <option value="OR">Oregon</option>
                                    <option value="PA">Pennsylvania</option>
                                    <option value="RI">Rhode Island</option>
                                    <option value="SC">South Carolina</option>
                                    <option value="SD">South Dakota</option>
                                    <option value="TN">Tennessee</option>
                                    <option value="TX">Texas</option>
                                    <option value="UT">Utah</option>
                                    <option value="VT">Vermont</option>
                                    <option value="VA">Virginia</option>
                                    <option value="WA">Washington</option>
                                    <option value="WV">West Virginia</option>
                                    <option value="WI">Wisconsin</option>
                                    <option value="WY">Wyoming</option>
                                    </select>
                            </div>

                            <div class="formGroup">
                                <label for="admin-zip-code"><?= lang('zip_code') ?></label>
                                <input type="tel" id="admin-zip-code" class="textInput" maxlength="5" data-mask="00000">
                            </div>

                            <div class="formGroup">
                                <label for="admin-notes"><?= lang('notes') ?></label>
                                <textarea id="admin-notes" class="textInput textInput--text-area" rows="3"></textarea>
                            </div>
                        </div>
                        <div class="admin-settings col-xs-12 col-sm-6">
                            <div class="formGroup">
                                <label for="admin-username"><?= lang('username') ?> *</label>
                                <input id="admin-username" class="textInput required" maxlength="120">
                            </div>

                            <div class="formGroup">
                                <label for="admin-password"><?= lang('password') ?> *</label>
                                <input type="password" id="admin-password" class="textInput required" maxlength="120">
                            </div>

                            <div class="formGroup">
                                <label for="admin-password-confirm"><?= lang('retype_password') ?> *</label>
                                <input type="password" id="admin-password-confirm" class="textInput required" maxlength="120">
                            </div>

                            <div class="formGroup">
                                <label for="admin-calendar-view"><?= lang('calendar') ?> *</label>
                                <select id="admin-calendar-view" class="selectInput required">
                                    <option value="default">Default</option>
                                    <option value="table">Table</option>
                                </select>
                            </div>

                            <br>

                            <button type="button" id="admin-notifications" class="btn btn--simple" data-toggle="button">
                                <i class="far fa-envelope"></i>
                                <span><?= lang('receive_notifications') ?></span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- CITY ADMIN TAB -->
        <div role="tabpanel" class="tab-pane" id="city-admin">
            <div class="row">
                <div id="filter-city-admin" class="filter-records column col-xs-12 col-sm-5">
                    <form>
                        <div class="input-group mb-4">
                            <input type="text" class="col key textInput textInput--simple" placeholder="Search">

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
                    </form>
                    <h3><?= lang('city_admins') ?></h3>

                    <div class="results"></div>
                </div>

                <div class="record-details column col-xs-12 col-sm-7">
                    <div class="d-flex mb-4">
                        <div class="add-edit-delete-group">
                            <button id="add-city-admin" class="btn btn-primary">
                                <i class="fas fa-plus"></i>
                                <?= lang('add') ?>
                            </button>
                            <button id="edit-city-admin" class="btn btn--simple" disabled="disabled">
                                <i class="far fa-edit"></i>
                                <?= lang('edit') ?>
                            </button>
                            <button id="delete-city-admin" class="btn btn--simple" disabled="disabled">
                                <i class="far fa-trash-alt"></i>
                                <?= lang('delete') ?>
                            </button>
                        </div>

                        <div class="save-cancel-group" style="display:none;">
                            <button id="save-city-admin" class="btn btn-primary">
                                <i class="far fa-check-circle"></i>
                                <?= lang('save') ?>
                            </button>
                            <button id="cancel-city-admin" class="btn btn--simple">
                                <i class="fas fa-ban"></i>
                                <?= lang('cancel') ?>
                            </button>
                        </div>
                    </div>

                    <h3><?= lang('city_admin_details') ?></h3>

                    <div class="form-message alert" style="display:none;"></div>

                    <input type="hidden" id="cityadmin-id" class="record-id">

                    <div class="row">
                        <div class="city-admin-details col-xs-12 col-sm-6">
                            <div class="formGroup">
                                <label for="city-admin-first-name"><?= lang('first_name') ?> *</label>
                                <input type="text" id="city-admin-first-name" class="textInput required" maxlength="120">
                            </div>

                            <div class="formGroup">
                                <label for="city-admin-last-name"><?= lang('last_name') ?> *</label>
                                <input type="text" id="city-admin-last-name" class="textInput required" maxlength="120">
                            </div>

                            <div class="formGroup">
                                <label for="city-admin-email"><?= lang('email') ?> *</label>
                                <input type="email" id="city-admin-email" class="textInput required" maxlength="120">
                            </div>

                            <div class="formGroup">
                                <label for="city-admin-phone-number"><?= lang('phone_number') ?> *</label>
                                <input type="tel" id="city-admin-phone-number" class="textInput required" maxlength="12" data-mask="000-000-0000">
                            </div>

                            <div class="formGroup">
                                <label for="city-admin-mobile-number"><?= lang('mobile_number') ?></label>
                                <input type="tel" id="city-admin-mobile-number" class="textInput" maxlength="12" data-mask="000-000-0000">
                            </div>

                            <div class="formGroup">
                                <label for="city-admin-address"><?= lang('address') ?></label>
                                <input type="text" id="city-admin-address" class="textInput" maxlength="120">
                            </div>

                            <div class="formGroup">
                                <label for="city-admin-city"><?= lang('city') ?></label>
                                <input type="text" id="city-admin-city" class="textInput" maxlength="120">
                            </div>

                            <div class="formGroup">
                                <label for="city-admin-state"><?= lang('state') ?></label>
                                <select name="city-admin-state" id="city-admin-state" class="selectInput d-block">
                                    <option value="AL">Alabama</option>
                                    <option value="AK">Alaska</option>
                                    <option value="AZ">Arizona</option>
                                    <option value="AR">Arkansas</option>
                                    <option value="CA">California</option>
                                    <option value="CO">Colorado</option>
                                    <option value="CT">Connecticut</option>
                                    <option value="DE">Delaware</option>
                                    <option value="DC">District Of Columbia</option>
                                    <option value="FL">Florida</option>
                                    <option value="GA">Georgia</option>
                                    <option value="HI">Hawaii</option>
                                    <option value="ID">Idaho</option>
                                    <option value="IL">Illinois</option>
                                    <option value="IN">Indiana</option>
                                    <option value="IA">Iowa</option>
                                    <option value="KS">Kansas</option>
                                    <option value="KY">Kentucky</option>
                                    <option value="LA">Louisiana</option>
                                    <option value="ME">Maine</option>
                                    <option value="MD">Maryland</option>
                                    <option value="MA">Massachusetts</option>
                                    <option value="MI" selected>Michigan</option>
                                    <option value="MN">Minnesota</option>
                                    <option value="MS">Mississippi</option>
                                    <option value="MO">Missouri</option>
                                    <option value="MT">Montana</option>
                                    <option value="NE">Nebraska</option>
                                    <option value="NV">Nevada</option>
                                    <option value="NH">New Hampshire</option>
                                    <option value="NJ">New Jersey</option>
                                    <option value="NM">New Mexico</option>
                                    <option value="NY">New York</option>
                                    <option value="NC">North Carolina</option>
                                    <option value="ND">North Dakota</option>
                                    <option value="OH">Ohio</option>
                                    <option value="OK">Oklahoma</option>
                                    <option value="OR">Oregon</option>
                                    <option value="PA">Pennsylvania</option>
                                    <option value="RI">Rhode Island</option>
                                    <option value="SC">South Carolina</option>
                                    <option value="SD">South Dakota</option>
                                    <option value="TN">Tennessee</option>
                                    <option value="TX">Texas</option>
                                    <option value="UT">Utah</option>
                                    <option value="VT">Vermont</option>
                                    <option value="VA">Virginia</option>
                                    <option value="WA">Washington</option>
                                    <option value="WV">West Virginia</option>
                                    <option value="WI">Wisconsin</option>
                                    <option value="WY">Wyoming</option>
                                    </select>
                            </div>

                            <div class="formGroup">
                                <label for="city-admin-zip-code"><?= lang('zip_code') ?></label>
                                <input type="tel" id="city-admin-zip-code" class="textInput" maxlength="5" data-mask="00000">
                            </div>

                            <div class="formGroup">
                                <label for="city-admin-notes"><?= lang('notes') ?></label>
                                <textarea id="city-admin-notes" class="textInput textInput--text-area" rows="3"></textarea>
                            </div>
                        </div>
                        <div class="city-admin-settings col-xs-12 col-sm-6">
                            <div class="formGroup">
                                <label for="city-admin-username"><?= lang('username') ?> *</label>
                                <input type="text" id="city-admin-username" class="textInput required" maxlength="120">
                            </div>

                            <div class="formGroup">
                                <label for="city-admin-password"><?= lang('password') ?> *</label>
                                <input type="password" id="city-admin-password" class="textInput required" maxlength="120">
                            </div>

                            <div class="formGroup">
                                <label for="city-admin-password-confirm"><?= lang('retype_password') ?> *</label>
                                <input type="password" id="city-admin-password-confirm" class="textInput required" maxlength="120">
                            </div>

                            <div class="formGroup">
                                <label for="city-admin-calendar-view"><?= lang('calendar') ?> *</label>
                                <select id="city-admin-calendar-view" class="selectInput required">
                                    <option value="default">Default</option>
                                    <option value="table">Table</option>
                                </select>
                            </div>

                            <br>

                            <button type="button" id="city-admin-notifications" class="btn btn--simple" data-toggle="button">
                                <i class="far fa-envelope"></i>
                                <span><?= lang('receive_notifications') ?></span>
                            </button>

                            <br><br>

                            <h4><?= lang('sites') ?></h4>
                            <div id="city-admin-providers" class="well"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- CITY BUSINESS REQUEST ADMIN TAB -->
        <div role="tabpanel" class="tab-pane" id="city-business">
            <div class="row">
                <div id="filter-city-business" class="filter-records column col-xs-12 col-sm-5">
                    <form>
                        <div class="input-group mb-4">
                            <input type="text" class="col key textInput textInput--simple" placeholder="Search">

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
                    </form>
                    <h3><?= lang('city_business_request_admins') ?></h3>

                    <div class="results"></div>
                </div>

                <div class="record-details column col-xs-12 col-sm-7">
                    <div class="d-flex mb-4">
                        <div class="add-edit-delete-group">
                            <button id="add-city-business" class="btn btn-primary">
                                <i class="fas fa-plus"></i>
                                <?= lang('add') ?>
                            </button>
                            <button id="edit-city-business" class="btn btn--simple" disabled="disabled">
                                <i class="far fa-edit"></i>
                                <?= lang('edit') ?>
                            </button>
                            <button id="delete-city-business" class="btn btn--simple" disabled="disabled">
                                <i class="far fa-trash-alt"></i>
                                <?= lang('delete') ?>
                            </button>
                        </div>

                        <div class="save-cancel-group" style="display:none;">
                            <button id="save-city-business" class="btn btn-primary">
                                <i class="far fa-check-circle"></i>
                                <?= lang('save') ?>
                            </button>
                            <button id="cancel-city-business" class="btn btn--simple">
                                <i class="fas fa-ban"></i>
                                <?= lang('cancel') ?>
                            </button>
                        </div>
                    </div>

                    <h3><?= lang('city_business_reques_admin_details') ?></h3>

                    <div class="form-message alert" style="display:none;"></div>

                    <input type="hidden" id="citybusiness-id" class="record-id">

                    <div class="row">
                        <div class="city-business-details col-xs-12 col-sm-6">
                            <div class="formGroup">
                                <label for="city-business-first-name"><?= lang('first_name') ?> *</label>
                                <input type="text" id="city-business-first-name" class="textInput required" maxlength="120">
                            </div>

                            <div class="formGroup">
                                <label for="city-business-last-name"><?= lang('last_name') ?> *</label>
                                <input type="text" id="city-business-last-name" class="textInput required" maxlength="120">
                            </div>

                            <div class="formGroup">
                                <label for="city-business-email"><?= lang('email') ?> *</label>
                                <input type="email" id="city-business-email" class="textInput required" maxlength="120">
                            </div>

                            <div class="formGroup">
                                <label for="city-business-phone-number"><?= lang('phone_number') ?> *</label>
                                <input type="tel" id="city-business-phone-number" class="textInput required" maxlength="12" data-mask="000-000-0000">
                            </div>

                            <div class="formGroup">
                                <label for="city-business-mobile-number"><?= lang('mobile_number') ?></label>
                                <input type="tel" id="city-business-mobile-number" class="textInput" maxlength="12" data-mask="000-000-0000">
                            </div>

                            <div class="formGroup">
                                <label for="city-business-address"><?= lang('address') ?></label>
                                <input type="text" id="city-business-address" class="textInput" maxlength="120">
                            </div>

                            <div class="formGroup">
                                <label for="city-business-city"><?= lang('city') ?></label>
                                <input type="text" id="city-business-city" class="textInput" maxlength="120">
                            </div>

                            <div class="formGroup">
                                <label for="city-business-state"><?= lang('state') ?></label>
                                <select name="city-business-state" id="city-business-state" class="selectInput d-block">
                                    <option value="AL">Alabama</option>
                                    <option value="AK">Alaska</option>
                                    <option value="AZ">Arizona</option>
                                    <option value="AR">Arkansas</option>
                                    <option value="CA">California</option>
                                    <option value="CO">Colorado</option>
                                    <option value="CT">Connecticut</option>
                                    <option value="DE">Delaware</option>
                                    <option value="DC">District Of Columbia</option>
                                    <option value="FL">Florida</option>
                                    <option value="GA">Georgia</option>
                                    <option value="HI">Hawaii</option>
                                    <option value="ID">Idaho</option>
                                    <option value="IL">Illinois</option>
                                    <option value="IN">Indiana</option>
                                    <option value="IA">Iowa</option>
                                    <option value="KS">Kansas</option>
                                    <option value="KY">Kentucky</option>
                                    <option value="LA">Louisiana</option>
                                    <option value="ME">Maine</option>
                                    <option value="MD">Maryland</option>
                                    <option value="MA">Massachusetts</option>
                                    <option value="MI" selected>Michigan</option>
                                    <option value="MN">Minnesota</option>
                                    <option value="MS">Mississippi</option>
                                    <option value="MO">Missouri</option>
                                    <option value="MT">Montana</option>
                                    <option value="NE">Nebraska</option>
                                    <option value="NV">Nevada</option>
                                    <option value="NH">New Hampshire</option>
                                    <option value="NJ">New Jersey</option>
                                    <option value="NM">New Mexico</option>
                                    <option value="NY">New York</option>
                                    <option value="NC">North Carolina</option>
                                    <option value="ND">North Dakota</option>
                                    <option value="OH">Ohio</option>
                                    <option value="OK">Oklahoma</option>
                                    <option value="OR">Oregon</option>
                                    <option value="PA">Pennsylvania</option>
                                    <option value="RI">Rhode Island</option>
                                    <option value="SC">South Carolina</option>
                                    <option value="SD">South Dakota</option>
                                    <option value="TN">Tennessee</option>
                                    <option value="TX">Texas</option>
                                    <option value="UT">Utah</option>
                                    <option value="VT">Vermont</option>
                                    <option value="VA">Virginia</option>
                                    <option value="WA">Washington</option>
                                    <option value="WV">West Virginia</option>
                                    <option value="WI">Wisconsin</option>
                                    <option value="WY">Wyoming</option>
                                    </select>
                            </div>

                            <div class="formGroup">
                                <label for="city-business-zip-code"><?= lang('zip_code') ?></label>
                                <input type="tel" id="city-business-zip-code" class="textInput" maxlength="5" data-mask="00000">
                            </div>

                            <div class="formGroup">
                                <label for="city-business-notes"><?= lang('notes') ?></label>
                                <textarea id="city-business-notes" class="textInput textInput--text-area" rows="3"></textarea>
                            </div>
                        </div>
                        <div class="city-business-settings col-xs-12 col-sm-6">
                            <div class="formGroup">
                                <label for="city-business-username"><?= lang('username') ?> *</label>
                                <input type="text" id="city-business-username" class="textInput required" maxlength="120">
                            </div>

                            <div class="formGroup">
                                <label for="city-business-password"><?= lang('password') ?> *</label>
                                <input type="password" id="city-business-password" class="textInput required" maxlength="120">
                            </div>

                            <div class="formGroup">
                                <label for="city-business-password-confirm"><?= lang('retype_password') ?> *</label>
                                <input type="password" id="city-business-password-confirm" class="textInput required" maxlength="120">
                            </div>

                            <div class="formGroup">
                                <label for="city-business-calendar-view"><?= lang('calendar') ?> *</label>
                                <select id="city-business-calendar-view" class="selectInput required">
                                    <option value="default">Default</option>
                                    <option value="table">Table</option>
                                </select>
                            </div>

                            <br>

                            <button type="button" id="city-business-notifications" class="btn btn--simple" data-toggle="button">
                                <i class="far fa-envelope"></i>
                                <span><?= lang('receive_notifications') ?></span>
                            </button>

                            <br><br>

                            <h4><?= lang('sites') ?></h4>
                            <div id="city-business-providers" class="well"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
