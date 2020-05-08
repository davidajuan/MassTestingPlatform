<div id="customers-page" class="container-fluid backend-page mt-3">
    <div class="row">
    	<div id="filter-customers" class="patient-records col-md-12 col-lg-3">
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
            <h3><?= lang('customers') ?></h3>
            <div class="results"><?= lang('search_above') ?></div>
    	</div>

    	<div class="col-md-12 col-lg-9">
            <input id="customer-id" type="hidden">
            <div class="row">
                <div class="col-md-12 col-lg-8 my-4">
                        <h3><?= lang('details') ?></h3>
                        <div id="form-message" class="alert" style="display:none;"></div>

                        <!--  Patient Appointment Display -->
                        <div class="card m-1">
                            <div class="card-body">
                                <h5 class="card-title"><?= lang('patient_info') ?></h5>
                                <div class="alert alert-danger hide" role="alert" data-caller-patient>
                                    <?= lang('prescription_required') ?>
                                </div>
                                <div class="row">
                                    <div class="col-sm-12 col-md-6">
                                        <ul class="patientRecordDisplay">
                                            <li><?= lang('first_name') ?>: <span data-patient-detail="first-name"></span></li>
                                            <li><?= lang('middle_initial') ?>: <span data-patient-detail="middle-initial"></span></li>
                                            <li><?= lang('last_name') ?>: <span data-patient-detail="last-name"></span></li>
                                            <li><?= lang('gender') ?>: <span data-patient-detail="gender"></span></li>
                                            <li><?= lang('dob') ?>: <span data-patient-detail="dob"></span></li>
                                            <li><?= lang('age') ?>: <span data-patient-detail="age"></span></li>
                                            <li><?= lang('email') ?>: <a href="mailTo:<span data-patient-detail='email'></span>"><span data-patient-detail="email"></span></a></li>
                                            <li><?= lang('patient_consent_email') ?>: <span data-patient-detail="patient-consent-email"></span></li>
                                            <li><?= lang('mobile_number') ?>: <a href="tel:<span data-patient-detail='mobile-number'></span>"><span data-patient-detail="mobile-number"></span></a></li>
                                            <li><?= lang('patient_consent_sms') ?>: <span data-patient-detail="patient-consent-sms"></span></li>
                                        </ul>
                                    </div>
                                    <div class="col-sm-12 col-md-6">
                                        <ul class="patientRecordDisplay">
                                            <li><?= lang('phone_number') ?>: <a href="tel:<span data-patient-detail='phone-number'></span>"><span data-patient-detail="phone-number"></span></a></li>
                                            <li><?= lang('address') ?>: <span data-patient-detail="address"></span></li>
                                            <li><?= lang('apt') ?>: <span data-patient-detail="apt"></span></li>
                                            <li><?= lang('city') ?>: <span data-patient-detail="city"></span></li>
                                            <li><?= lang('state') ?>: <span data-patient-detail="state"></span></li>
                                            <li><?= lang('zip_code') ?>: <span data-patient-detail="zip-code"></span></li>
                                            <li><?= lang('ssn') ?>: <span data-patient-detail="ssn"></span></li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!--  Patient Appointment Display -->
                        <div class="card m-1">
                            <div class="card-body">
                                <h5 class="card-title"><?= lang('physician_info') ?></h5>
                                <ul class="patientRecordDisplay">
                                    <li><?= lang('provider_patient_id') ?>: <span data-patient-detail="provider-patient-id"></span></li>
                                    <li><?= lang('doctor_npi') ?>: <span data-patient-detail="doctor-npi"></span></li>
                                    <li><?= lang('doctor_first_name') ?>: <span data-patient-detail="doctor-first-name"></span></li>
                                    <li><?= lang('doctor_last_name') ?>: <span data-patient-detail="doctor-last-name"></span></li>
                                    <li><?= lang('doctor_phone_number') ?>: <a href="tel:<span data-patient-detail='doctor-phone-number'>"><span data-patient-detail="doctor-phone-number"></a></li>
                                    <li><?= lang('doctor_address') ?>: <span data-patient-detail="doctor-address"></span></li>
                                    <li><?= lang('doctor_city') ?>: <span data-patient-detail="doctor-city"></span></li>
                                    <li><?= lang('doctor_state') ?>: <span data-patient-detail="doctor-state"></span></li>
                                    <li><?= lang('doctor_zip_code') ?>: <span data-patient-detail="doctor-zip-code"></span></li>
                                </ul>
                            </div>
                        </div>
                        <input type="hidden" id="appointment-hash" />
                    </div>

                    <div class="col-md-12 col-lg-4 my-3">
                        <div id="customer-appointments" class="customer-appointments well"></div>
                    </div>
                </div>
    	    </div>
        </div>
    </div>
</div>

<script src="<?= asset_url('assets/ext/jquery-ui/jquery-ui-timepicker-addon.js') ?>"></script>
<script src="<?= asset_url('assets/js/backend_customers_helper.js') ?>"></script>
<script src="<?= asset_url('assets/js/backend_customers.js') ?>"></script>
<script>
    var GlobalVariables = {
        csrfToken          : <?= json_encode($this->security->get_csrf_hash()) ?>,
        availableProviders : <?= json_encode($available_providers) ?>,
        availableServices  : <?= json_encode($available_services) ?>,
        secretaryProviders : <?= json_encode($secretary_providers) ?>,
        dateFormat         : <?= json_encode($date_format) ?>,
        timeFormat         : <?= json_encode($time_format) ?>,
        baseUrl            : <?= json_encode($base_url) ?>,
        customers          : <?= json_encode($customers) ?>,
        user               : {
            id         : <?= $user_id ?>,
            email      : <?= json_encode($user_email) ?>,
            role_slug  : <?= json_encode($role_slug) ?>,
            privileges : <?= json_encode($privileges) ?>
        }
    };

    $(document).ready(function() {
        BackendCustomers.initialize(true);
    });
</script>
