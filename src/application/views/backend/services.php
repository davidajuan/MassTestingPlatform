<script src="<?= asset_url('assets/ext/jquery-ui/jquery-ui-timepicker-addon.js') ?>"></script>
<script src="<?= asset_url('assets/js/backend_services_helper.js') ?>"></script>
<script src="<?= asset_url('assets/js/backend_categories_helper.js') ?>"></script>
<script src="<?= asset_url('assets/js/backend_services.js') ?>"></script>
<script src="<?= asset_url('assets/js/attendant_override.js') ?>"></script>
<script src="<?= asset_url('assets/ext/jquery-jeditable/jquery.jeditable.min.js') ?>"></script>

<script>
    var GlobalVariables = {
        csrfToken     : <?= json_encode($this->security->get_csrf_hash()) ?>,
        baseUrl       : <?= json_encode($base_url) ?>,
        dateFormat    : <?= json_encode($date_format) ?>,
        timeFormat    : <?= json_encode($time_format) ?>,
        services      : <?= json_encode($services) ?>,
        categories    : <?= json_encode($categories) ?>,
        user          : {
            id        : <?= $user_id ?>,
            email     : <?= json_encode($user_email) ?>,
            role_slug : <?= json_encode($role_slug) ?>,
            privileges: <?= json_encode($privileges) ?>
        }
    };

    $(document).ready(function() {
        BackendServices.initialize(true);
    });
</script>

<div id="services-page" class="container-fluid backend-page mt-3">
    <ul class="nav nav-pills" role="tablist">
        <li role="presentation"><a href="#services" aria-controls="services" role="tab" data-toggle="tab" class="nav-link active"><?= lang('services') ?></a></li>
        <li role="presentation"><a href="#categories" aria-controls="categories" role="tab" data-toggle="tab" class="nav-link"><?= lang('categories') ?></a></li>
    </ul>

    <div class="tab-content">

        <!-- SERVICES TAB -->

        <div role="tabpanel" class="tab-pane active" id="services">
            <input type="hidden" id="service-id">
            <div class="row">
                <div id="filter-services" class="filter-records column col-xs-12 col-sm-5">
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

                    <h3><?= lang('services') ?></h3>
                    <div class="results"></div>
                </div>

                <div class="record-details column col-md-7">
                    <div class="mb-3">
                      <p class="h5"><?= lang('current_view') ?></p>
                      <div class="d-flex switch-view nav-pills bg-white">
                        <div class="display-details nav-link active">Service Details</div>
                        <div class="display-overrides hide nav-link">Attendant Override</div>
                      </div>
                    </div>

                    <div class="btn-toolbar">
                        <div class="add-edit-delete-group mb-2">
                            <button id="add-service" class="btn btn-primary">
                                <i class="fas fa-plus"></i>
                                <?= lang('add') ?>
                            </button>
                            <button id="edit-service" class="btn btn--simple" disabled="disabled">
                                <i class="far fa-edit"></i>
                                <?= lang('edit') ?>
                            </button>
                            <button id="delete-service" class="btn btn--simple" disabled="disabled">
                                <i class="far fa-trash-alt"></i>
                                <?= lang('delete') ?>
                            </button>
                        </div>

                        <div class="save-cancel-group mb-2" style="display:none;">
                            <button id="save-service" class="btn btn-primary">
                                <i class="far fa-check-circle"></i>
                                <?= lang('save') ?>
                            </button>
                            <button id="cancel-service" class="btn btn--simple">
                                <i class="fas fa-ban"></i>
                                <?= lang('cancel') ?>
                            </button>
                        </div>
                    </div>

                    <div class="details-view col-md-7 px-0">
                      <h3><?= lang('details') ?></h3>

                      <div class="form-message alert" style="display:none;"></div>

                      <div class="formGroup">
                          <label for="service-name"><?= lang('name') ?> *</label>
                          <input id="service-name" class="textInput required" maxlength="128">
                      </div>

                      <div class="formGroup">
                          <label for="service-duration"><?= lang('duration_minutes') ?> *</label>
                          <input id="service-duration" class="textInput required" type="number" min="15">
                      </div>

                      <div class="formGroup">
                          <label for="service-price"><?= lang('price') ?> *</label>
                          <input id="service-price" class="textInput required">
                      </div>

                      <div class="formGroup">
                          <label for="service-currency"><?= lang('currency') ?></label>
                          <input id="service-currency" class="textInput" maxlength="32">
                      </div>

                      <div class="formGroup">
                          <label for="service-category"><?= lang('category') ?></label>
                          <select id="service-category" class="selectInput"></select>
                      </div>

                      <div class="formGroup">
                          <label for="service-availabilities-type"><?= lang('availabilities_type') ?></label>
                          <select id="service-availabilities-type" class="selectInput">
                              <option value="<?= AVAILABILITIES_TYPE_FLEXIBLE ?>">
                                  <?= lang('flexible') ?>
                              </option>
                              <option value="<?= AVAILABILITIES_TYPE_FIXED ?>">
                                  <?= lang('fixed') ?>
                              </option>
                          </select>
                      </div>

                      <div class="formGroup">
                          <label for="service-attendants-number"><?= lang('attendants_number') ?> *</label>
                          <input id="service-attendants-number" class="textInput required" type="number" min="1">
                      </div>

                      <div class="formGroup">
                          <label for="service-description"><?= lang('description') ?></label>
                          <textarea id="service-description" rows="4" class="textInput textInput--text-area"></textarea>
                      </div>

                      <p id="form-message" class="text-danger">
                          <em><?= lang('fields_are_required') ?></em>
                      </p>

                    </div>
                    <div class="override-view pt-3"  style="display: none;">
                      <button type="button" class="add-override btn btn-primary mb-3">
                          <i class="fas fa-plus"></i>
                          Add Override
                      </button>
                      <p class="submitErrorMessage hide"></p>
                      <div class="table-responsive-md">
                        <table class="attendant-override table table-striped">
                            <thead>
                                <tr>
                                    <th><?= lang('day') ?></th>
                                    <th><?= lang('start') ?></th>
                                    <th><?= lang('end') ?></th>
                                    <th>Attendants</th>
                                    <th><?= lang('actions') ?></th>
                                </tr>
                            </thead>
                            <tbody><!-- Dynamic Content --></tbody>
                        </table>
                        <small>Note: only dates for the future will be displayed</small>
                      </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- CATEGORIES TAB -->

        <div role="tabpanel" class="tab-pane" id="categories">
            <div class="row">
                <div id="filter-categories" class="filter-records column col-xs-12 col-sm-5">
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

                    <h3><?= lang('categories') ?></h3>
                    <div class="results"></div>
                </div>

                <div class="record-details col-xs-12 col-sm-5">
                    <div class="btn-toolbar">
                        <div class="add-edit-delete-group">
                            <button id="add-category" class="btn btn-primary">
                                <i class="fas fa-plus"></i>
                                <?= lang('add') ?>
                            </button>
                            <button id="edit-category" class="btn btn--simple" disabled="disabled">
                                <i class="far fa-edit"></i>
                                <?= lang('edit') ?>
                            </button>
                            <button id="delete-category" class="btn btn--simple" disabled="disabled">
                                <i class="far fa-trash-alt"></i>
                                <?= lang('delete') ?>
                            </button>
                        </div>

                        <div class="save-cancel-group" style="display:none;">
                            <button id="save-category" class="btn btn-primary">
                                <i class="far fa-check-circle"></i>
                                <?= lang('save') ?>
                            </button>
                            <button id="cancel-category" class="btn btn--simple">
                                <i class="fas fa-ban"></i>
                                <?= lang('cancel') ?>
                            </button>
                        </div>
                    </div>

                    <h3><?= lang('details') ?></h3>

                    <div class="form-message alert" style="display:none;"></div>

                    <input type="hidden" id="category-id">

                    <div class="formGroup">
                        <label for="category-name"><?= lang('name') ?> *</label>
                        <input id="category-name" class="textInput required">
                    </div>

                    <div class="formGroup">
                        <label for="category-description"><?= lang('description') ?></label>
                        <textarea id="category-description" rows="4" class="textInput"></textarea>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
