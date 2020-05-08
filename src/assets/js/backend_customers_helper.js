/* ----------------------------------------------------------------------------
 * Easy!Appointments - Open Source Web Scheduler
 *
 * @package     EasyAppointments
 * @author      A.Tselegidis <alextselegidis@gmail.com>
 * @copyright   Copyright (c) 2013 - 2018, Alex Tselegidis
 * @license     http://opensource.org/licenses/GPL-3.0 - GPLv3
 * @link        http://easyappointments.org
 * @since       v1.0.0
 * ---------------------------------------------------------------------------- */

(function () {

    'use strict';

    /**
     * CustomersHelper Class
     *
     * This class contains the methods that are used in the backend customers page.
     *
     * @class CustomersHelper
     */
    function CustomersHelper() {
        this.filterResults = {};
    }

    /**
     * Binds the default event handlers of the backend customers page.
     */
    CustomersHelper.prototype.bindEventHandlers = function () {
        var instance = this;

        /**
         * Event: Filter Customers Form "Submit"
         */
        $('#filter-customers form').submit(function (event) {
            var key = $('#filter-customers .key').val();
            $('#filter-customers .selected').removeClass('selected');
            instance.resetForm();
            instance.filter(key);
            return false;
        });

        /**
         * Event: Filter Customers Clear Button "Click"
         */
        $('#filter-customers .clear').click(function () {
            $('#filter-customers .key').val('');
            instance.filter('');
            instance.resetForm();
        });

        /**
         * Event: Filter Entry "Click"
         *
         * Display the customer data of the selected row.
         */
        $(document).on('click', '.entry', function () {
            if ($('#filter-customers .filter').prop('disabled')) {
                return; // Do nothing when user edits a customer record.
            }

            var customerId = $(this).attr('data-id');
            var customer = {};
            $.each(instance.filterResults, function (index, item) {
                if (item.id == customerId) {
                    customer = item;
                    return false;
                }
            });

            instance.display(customer);
            $('#filter-customers .selected').removeClass('selected');
            $(this).addClass('selected');
            $('#edit-customer, #delete-customer').prop('disabled', false);
        });

        /**
         * Event: Add Customer Button "Click"
         */
        $('#add-customer').click(function () {
            instance.resetForm();
            $('#add-edit-delete-group').hide();
            $('#save-cancel-group').show();
            $('.record-details').find('input, textarea').prop('readonly', false);

            $('#filter-customers button').prop('disabled', true);
            $('#filter-customers .results').css('color', '#AAA');
        });

        /**
         * Event: Edit Customer Button "Click"
         */
        $("body").on("click", "#edit-customer", function(){
            var appointment_hash = $('#appointment-hash').val();
            if (appointment_hash) {
                window.location.href = '/backend?appointment_hash=' + appointment_hash;
            }
        });

        /**
         * Event: Edit Customer Button "Click"
         */
        $("body").on("click", "#edit-appointment", function(){
            $('#appointment-select-edit').show();
            $('#edit-appointment').hide();
        });

        $("body").on("click", "#cancel-appointment", function(){
            $('#appointment-select-edit').hide();
            $('#edit-appointment').show();
        });

        $("body").on("click", "#save-appointment", function(){
            var appointment = { "id": $('#appointment-id').val(), "start_datetime": $('#start-datetime').val() }

            if (!instance.validateAppointment()) {
                return;
            }

            instance.updateAppointment(appointment);
        });

        /**
         * Event: Cancel Customer Add/Edit Operation Button "Click"
         */
        $('#cancel-customer').click(function () {
            var id = $('#customer-id').val();
            instance.resetForm();
            if (id != '') {
                instance.select(id, true);
            }
        });

        /**
         * Event: Save Add/Edit Customer Operation "Click"
         */
        $('#save-customer').click(function () {
            var customer = {
                first_name: $('#first-name').val(),
                last_name: $('#last-name').val(),
                email: $('#email').val(),
                phone_number: $('#phone-number').val(),
                address: $('#address').val(),
                city: $('#city').val(),
                zip_code: $('#zip-code').val(),
                notes: $('#notes').val()
            };

            if ($('#customer-id').val() != '') {
                customer.id = $('#customer-id').val();
            }

            if (!instance.validate()) {
                return;
            }

            instance.save(customer);
        });

        /**
         * Event: Delete Customer Button "Click"
         */
        $('#delete-customer').click(function () {
            var customerId = $('#customer-id').val();
            var buttons = [
                {
                    text: EALang.delete,
                    click: function () {
                        instance.delete(customerId);
                        $('#message_box').dialog('close');
                    }
                },
                {
                    text: EALang.cancel,
                    click: function () {
                        $('#message_box').dialog('close');
                    }
                }
            ];

            GeneralFunctions.displayMessageBox(EALang.delete_customer,
                EALang.delete_record_prompt, buttons);
        });
    };

    /**
     * Save a customer record to the database (via ajax post).
     *
     * @param {Object} customer Contains the customer data.
     */
    CustomersHelper.prototype.save = function (customer) {
        var postUrl = GlobalVariables.baseUrl + '/backend_api/ajax_save_customer';
        var postData = {
            csrfToken: GlobalVariables.csrfToken,
            customer: JSON.stringify(customer)
        };

        $.post(postUrl, postData, function (response) {
            if (!GeneralFunctions.handleAjaxExceptions(response)) {
                return;
            }

            Backend.displayNotification(EALang.customer_saved);
            this.resetForm();
            $('#filter-customers .key').val('');
            this.filter('', response.id, true);
        }.bind(this), 'json').fail(GeneralFunctions.ajaxFailureHandler);
    };

    /**
     * Save a customer record to the database (via ajax post).
     *
     * @param {Object} customer Contains the customer data.
     */
    CustomersHelper.prototype.updateAppointment = function (appointment) {
        var postUrl = GlobalVariables.baseUrl + '/backend_api/ajax_update_appointment';
        var postData = {
            csrfToken: GlobalVariables.csrfToken,
            appointment_data: JSON.stringify(appointment)
        };

        $.post(postUrl, postData, function (response) {
            if (!GeneralFunctions.handleAjaxExceptions(response)) {
                return;
            }

            if (response.message === "SUCCESS") {
                var new_start_datetime = GeneralFunctions.formatDate(Date.parse(response.new_start_datetime), 'YMD', true)
                $('#label_start_datetime').html(new_start_datetime);
                $('#appointment-select-edit').hide();
                Backend.displayNotification("Appointent has been updated");
            } else {
                Backend.displayNotification("There was an issue update the appointment");
            }
        }.bind(this), 'json').fail(GeneralFunctions.ajaxFailureHandler);
    };

    /**
     * Delete a customer record from database.
     *
     * @param {Number} id Record id to be deleted.
     */
    CustomersHelper.prototype.delete = function (id) {
        var postUrl = GlobalVariables.baseUrl + '/backend_api/ajax_delete_customer';
        var postData = {
            csrfToken: GlobalVariables.csrfToken,
            customer_id: id
        };

        $.post(postUrl, postData, function (response) {
            if (!GeneralFunctions.handleAjaxExceptions(response)) {
                return;
            }

            Backend.displayNotification(EALang.customer_deleted);
            this.resetForm();
            this.filter($('#filter-customers .key').val());
        }.bind(this), 'json').fail(GeneralFunctions.ajaxFailureHandler);
    };

    /**
     * Validate a future data
    */
    CustomersHelper.prototype.validateAppointment= function () {
        var instance = this;
        $('#form-message').removeClass('alert-danger').hide();
        $('.has-error').removeClass('has-error');
        var today = new Date();

        try {
            var date = $('#start-datetime').val();
            var parseDate = new Date(date);

            // if you are scheduling tomorrows date, the time today can't be between 4:15pm - 11:59pm
            if (instance.isDateTomorrow(parseDate)) {
                if ((today.getHours() == 16 && today.getMinutes() >= 15) || (today.getHours() >= 17)) {
                    throw '* Sorry, you cant schedule after 4:15pm for tomorrow';
                }
            }

            if(date.length !== 19) {
                throw '* Date must use 0000-00-00 00:00:00 format';
            };

            if(!validator.isISO8601(date, {strict: true})) {
                throw '* Invalid Appointment Date!';
            }

            if(parseDate < today) {
                throw '* Invalid Appointment Date!!';
            }

            return true;
        } catch (message) {
            $('#form-message')
                .addClass('alert-danger')
                .text(message)
                .show();
            return false;
        }
    }

    CustomersHelper.prototype.isDateTomorrow = function (date) {
        var today = new Date();
        var tomorrow = new Date(today.getFullYear(), today.getMonth(), today.getDate() + 1);

        if (tomorrow.getFullYear() == date.getFullYear() && tomorrow.getMonth() == date.getMonth() && tomorrow.getDate() == date.getDate()) {
            return true;
        }

        return false;
    }


    /**
     * Validate customer data before save (insert or update).
     */
    CustomersHelper.prototype.validate = function () {
        $('#form-message')
            .removeClass('alert-danger')
            .hide();
        $('.has-error').removeClass('has-error');

        try {
            // Validate required fields.
            var missingRequired = false;

            $('.required').each(function () {
                if ($(this).val() == '') {
                    $(this).closest('.formGroup').addClass('has-error');
                    missingRequired = true;
                }
            });

            if (missingRequired) {
                throw EALang.fields_are_required;
            }

            // Validate email address.
            if (!GeneralFunctions.validateEmail($('#email').val())) {
                $('#email').closest('.formGroup').addClass('has-error');
                throw EALang.invalid_email;
            }

            return true;
        } catch (message) {
            $('#form-message')
                .addClass('alert-danger')
                .text(message)
                .show();
            return false;
        }
    };

    /**
     * Bring the customer form back to its initial state.
     */
    CustomersHelper.prototype.resetForm = function () {
        $('.record-details').find('input, textarea').val('');
        $('.record-details').find('input, textarea').prop('readonly', true);

        $('#customer-appointments').empty();
        $('#edit-customer, #delete-customer').prop('disabled', true);
        $('#add-edit-delete-group').show();
        $('#save-cancel-group').hide();

        $('.record-details .has-error').removeClass('has-error');
        $('.record-details #form-message').hide();

        $('#filter-customers button').prop('disabled', false);
        $('#filter-customers .selected').removeClass('selected');
        $('#filter-customers .results').css('color', '');
    };

    /**
     * Display a customer record into the form.
     *
     * @param {Object} customer Contains the customer record data.
     */
    CustomersHelper.prototype.display = function (customer) {
        // Patient
        $('#customer-id').val(customer.id);
        $('[data-patient-detail="first-name"]').html(customer.first_name);
        $('[data-patient-detail="middle-initial"]').html(customer.middle_initial);
        $('[data-patient-detail="last-name"]').html(customer.last_name);
        $('[data-patient-detail="gender"]').html(GeneralFunctions.formatString(customer.gender, 'titleCase'));
        $('[data-patient-detail="dob"]').html(GeneralFunctions.formatDate(customer.dob, 'MDY', false));
        $('[data-patient-detail="age"]').html(GeneralFunctions.getCalculatedAge(customer.dob));
        $('[data-patient-detail="email"]').html(customer.email);
        $('[data-patient-detail="patient-consent-email"]').html((customer.patient_consent == 1)? 'Yes' : 'No');
        $('[data-patient-detail="mobile-number"]').html(customer.mobile_number);
        $('[data-patient-detail="patient-consent-sms"]').html((customer.patient_consent_sms == 1)? 'Yes' : 'No');
        $('[data-patient-detail="phone-number"]').html(customer.phone_number);
        $('[data-patient-detail="address"]').html(customer.address);
        $('[data-patient-detail="apt"]').html(customer.apt);
        $('[data-patient-detail="city"]').html(customer.city);
        $('[data-patient-detail="state"]').html(customer.state);
        $('[data-patient-detail="zip-code"]').html(customer.zip_code);
        $('[data-patient-detail="ssn"]').html(customer.ssn);
        $('[data-patient-detail="provider-patient-id"]').html(customer.provider_patient_id);
        $('[data-patient-detail="doctor-npi"]').html(customer.doctor_npi);
        $('[data-patient-detail="doctor-first-name"]').html(customer.doctor_first_name);
        $('[data-patient-detail="doctor-last-name"]').html(customer.doctor_last_name);
        $('[data-patient-detail="doctor-phone-number"]').html(customer.doctor_phone_number);
        $('[data-patient-detail="doctor-address"]').html(customer.doctor_address);
        $('[data-patient-detail="doctor-city"]').html(customer.doctor_city);
        $('[data-patient-detail="doctor-state"]').html(customer.doctor_state);
        $('[data-patient-detail="doctor-zip-code"]').html(customer.doctor_zip_code);

        // fix if we ever need to schedule more than one appointment
        if (customer.appointments) {
          $('#appointment-hash').val(customer.appointments[0].hash);
        }

        $('#customer-appointments').empty();
        $.each(customer.appointments, function (index, appointment) {
            if (GlobalVariables.user.role_slug === Backend.DB_SLUG_PROVIDER && parseInt(appointment.id_users_provider) !== GlobalVariables.user.id) {
                return true; // continue
            }

            if (GlobalVariables.user.role_slug === Backend.DB_SLUG_SECRETARY && GlobalVariables.secretaryProviders.indexOf(appointment.id_users_provider) === -1) {
                return true; // continue
            }

            var start = GeneralFunctions.formatDate(Date.parse(appointment.start_datetime), 'YMD', true);

            var html =
                '<div class="appointment-row" data-id="' + appointment.id + '">' +
                    '<h3>Appointments</h3>' +
                    '<strong>' + appointment.service.name + '</strong>' +
                    '<p class="mb-0">' + appointment.provider.first_name + ' ' + appointment.provider.last_name + '</p>' +
                    '<p class="mb-0" id="label_start_datetime">' + start + '</p>' +
                    '<p class="mb-0" id="label_hash">Barcode: <span class="font-weight-bold">' + appointment.hash + '</span></p>' +
                    '<button id="edit-appointment" class="btn btn--simple px-0 ml-1"> ' +
                    '<i class="far fa-edit"></i> Edit</button>' +
                    '<div id="appointment-select-edit" style="display: none;">' +
                        '<input id="start-datetime" class="required textInput hasDatepicker mt-2 mb-3" value="' + appointment.start_datetime + '">' +
                        '<button id="save-appointment" class="btn btn-primary">Save</button>' +
                        '<button id="cancel-appointment" class="btn btn--simple">Cancel</button>' +
                        '<p class="mb-0" id="label_start_datetime">NOTE: Only use this if you need to take a timeslot outside of available hours, otherwise edit the patient on the Book Appointment page.</p>' +
                        '<input id="appointment-id" type="hidden" value="' + appointment.id + '">' +
                    '</div>' +
                    '<button id="download-patient-req-form" class="btn btn-primary fa-download d-block" onclick="location.href=\'/patient_download/req/' + appointment.hash + '\'">Download Req Form</button>' +
                '</div>';
            $('#customer-appointments').append(html);

            // this is isnt working
            $( "#start-datetime" ).datetimepicker("setDate", appointment.start_datetime);
        });
    };

    /**
     * Filter customer records.
     *
     * @param {String} key This key string is used to filter the customer records.
     * @param {Number} selectId Optional, if set then after the filter operation the record with the given
     * ID will be selected (but not displayed).
     * @param {Boolean} display Optional (false), if true then the selected record will be displayed on the form.
     */
    CustomersHelper.prototype.filter = function (key, selectId, display) {
        display = display || false;

        var postUrl = GlobalVariables.baseUrl + '/backend_api/ajax_filter_customers';
        var postData = {
            csrfToken: GlobalVariables.csrfToken,
            key: key
        };

        $.post(postUrl, postData, function (response) {
            if (!GeneralFunctions.handleAjaxExceptions(response)) {
                return;
            }

            this.filterResults = response;

            $('#filter-customers .results').html('');
            $.each(response, function (index, customer) {
                var html = this.getFilterHtml(customer);
                $('#filter-customers .results').append(html);
            }.bind(this));
            if (response.length == 0) {
                $('#filter-customers .results').html('<em>' + EALang.no_records_found + '</em>');
            }

            if (selectId != undefined) {
                this.select(selectId, display);
            }

        }.bind(this), 'json').fail(GeneralFunctions.ajaxFailureHandler);
    };

    /**
     * Get the filter results row HTML code.
     *
     * @param {Object} customer Contains the customer data.
     *
     * @return {String} Returns the record HTML code.
     */
    CustomersHelper.prototype.getFilterHtml = function (customer) {
        var name = customer.first_name + ' ' + customer.last_name;
        var info = customer.email;
        info = (customer.mobile_number != '' && customer.mobile_number != null)
            ? info + ', ' + customer.mobile_number : info;

        var html =
            '<div class="entry" data-id="' + customer.id + '">' +
            '<strong>' +
            name +
            '</strong><br>' +
            info +
            '</div><hr>';

        return html;
    };

    /**
     * Select a specific record from the current filter results.
     *
     * If the customer id does not exist in the list then no record will be selected.
     *
     * @param {Number} id The record id to be selected from the filter results.
     * @param {Boolean} display Optional (false), if true then the method will display the record
     * on the form.
     */
    CustomersHelper.prototype.select = function (id, display) {
        display = display || false;

        $('#filter-customers .selected').removeClass('selected');

        $('#filter-customers .entry').each(function () {
            if ($(this).attr('data-id') == id) {
                $(this).addClass('selected');
                return false;
            }
        });

        if (display) {
            $.each(this.filterResults, function (index, customer) {
                if (customer.id == id) {
                    this.display(customer);
                    $('#edit-customer, #delete-customer').prop('disabled', false);
                    return false;
                }
            }.bind(this));
        }
    };

    /**
     *  Decides if the line needs to be shown if the record in the database shows the checkbox was selected
     * @param {Element} el The element that needs to be updated in the DOM
     * @param {String} val The value in the database for that element
     */
    function showPUICheckBox(el, val) {
        console.log(val);
        if (val != '' && val != null) {
            if (val == '1') {
                el.html('Yes');
                el.parent().removeClass('hide');
            }
            else
            {
                el.parent().addClass('hide');
            }
        }
        else {
            el.parent().addClass('hide');
        }
    };

    window.CustomersHelper = CustomersHelper;
})();
