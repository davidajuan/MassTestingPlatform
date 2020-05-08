<div class="container my-5">
    <div class="row mb-3">
        <h2 class="ml-3"><?= lang('reports') ?></h2>
    </div>

    <div class="row">
        <div class="col-sm-12 col-md-4 d-flex align-items-stretch">
            <div class="card p-0">
                <div class="card-body p-4">
                    <h4 class="card-title">Appointment Counts</h5>
                    <h6 class="card-subtitle mb-2 text-muted"> by Date / Date Range</h6>
                    <p class="card-text">This report produces a .csv file that shows the appointment counts for the specified date range.</p>
                    <div class="inlineGroup@lg">
                        <div class="formGroup">
                            <label for="appointment-counts-start-date" class="control-label">Start Date</label>
                            <input type="text" class="textInput" id="appointment-counts-start-date" data-mask="00-00-0000" data-validate="date">
                            <span class="errorMessage"></span>
                        </div>
                        <div class="formGroup">
                            <label for="appointment-counts-end-date" class="control-label">End Date</label>
                            <input type="text" class="textInput" id="appointment-counts-end-date" data-mask="00-00-0000" data-validate="date">
                            <span class="errorMessage"></span>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <button id="gen-appointment-count-report" type="button" class="btn btn-primary btn--spinner px-4 generate-report btn-block">
                        Generate Report
                    </button>
                </div>
            </div>
        </div>
        <div class="col-sm-12 col-md-4 d-flex align-items-stretch">
            <div class="card p-0">
                <div class="card-body p-4">
                    <h4 class="card-title">Businesses</h5>
                    <h6 class="card-subtitle mb-2 text-muted"> by Date / Date Range</h6>
                    <p class="card-text">This report produces a .csv file that shows the business counts for the specified date range and status.</p>
                    <div class="formGroup">
                        <label for="businesses-status" class="control-label">Status</label>
                        <select id="businesses-status" class="selectInput">
                            <option value="">All</option>
                            <option value="active">Approved</option>
                            <option value="pending">Pending</option>
                            <option value="deleted">Declined</option>
                        </select>
                        <span class="errorMessage"></span>
                    </div>
                    <div class="inlineGroup@lg">
                        <div class="formGroup">
                            <label for="businesses-start-date" class="control-label">Start Date</label>
                            <input type="text" class="textInput" id="businesses-start-date" data-mask="00-00-0000" data-validate="date">
                            <span class="errorMessage"></span>
                        </div>
                        <div class="formGroup">
                            <label for="appointment-counts-end-date" class="control-label">End Date</label>
                            <input type="text" class="textInput" id="businesses-counts-end-date" data-mask="00-00-0000" data-validate="date">
                            <span class="errorMessage"></span>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <button id="gen-businesses-report" type="button" class="btn btn-primary btn--spinner btn-block px-4 generate-report">
                        Generate Report
                    </button>
                </div>
            </div>
        </div>
        <div class="col-sm-12 col-md-4 d-flex align-items-stretch">
            <div class="card p-0">
                <div class="card-body p-4">
                    <h4 class="card-title">City Employee Appointments</h5>
                    <h6 class="card-subtitle mb-2 text-muted"> by Date / Date Range</h6>
                    <p class="card-text">This report produces a .csv file that shows the appointments for city workersfor the specified date range.</p>
                    <div class="inlineGroup@lg">
                        <div class="formGroup">
                            <label for="city-employee-start-date" class="control-label">Start Date</label>
                            <input type="text" class="textInput" id="city-employee-start-date" data-mask="00-00-0000" data-validate="date">
                            <span class="errorMessage"></span>
                        </div>
                        <div class="formGroup">
                            <label for="city-employee-end-date" class="control-label">End Date</label>
                            <input type="text" class="textInput" id="city-employee-end-date" data-mask="00-00-0000" data-validate="date">
                            <span class="errorMessage"></span>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <button id="gen-city-employee-report" type="button" class="btn btn-primary btn--spinner btn-block px-4 generate-report">
                        Generate Report
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    var GlobalVariables = {
        availableServices   : <?= json_encode($available_services) ?>,
        availableProviders  : <?= json_encode($available_providers) ?>,
        baseUrl             : <?= json_encode(config('base_url')) ?>,
        csrfToken           : <?= json_encode($this->security->get_csrf_hash()) ?>,
    };
    var EALang = <?= json_encode($this->lang->language) ?>;
    var availableLanguages = <?= json_encode($this->config->item('available_languages')) ?>;
</script>

<script src="<?= asset_url('assets/js/book-validations.js') ?>"></script>
<script src="<?= asset_url('assets/js/backend_reports.js') ?>"></script>
<script>
    $(document).ready(function() {
        BackendReports.initialize(true);
        GeneralFunctions.enableLanguageSelection($('#select-language'));
    });
</script>
