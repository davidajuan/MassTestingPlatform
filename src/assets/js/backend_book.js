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

window.BackendBook = window.BackendBook || {};

/**
 * Frontend Book
 *
 * This module contains functions that implement the book appointment page functionality. Once the
 * initialize() method is called the page is fully functional and can serve the appointment booking
 * process.
 *
 * @module BackendBook
 */
(function (exports) {

    'use strict';

    /**
     * The page helper contains methods that implement each record type functionality
     * (for now there is only the CustomersHelper).
     *
     * @type {Object}
     */
    var helper = {};

    /**
     * Contains terms and conditions consent.
     *
     * @type {Object}
     */
    var termsAndConditionsConsent;

    /**
     * Contains privacy policy consent.
     *
     * @type {Object}
     */
    var privacyPolicyConsent;

    /**
     * Determines the functionality of the page.
     *
     * @type {Boolean}
     */
    exports.manageMode = false;

    /**
     * This method initializes the book appointment page.
     *
     * @param {Boolean} bindEventHandlers (OPTIONAL) Determines whether the default
     * event handlers will be bound to the dom elements.
     * @param {Boolean} manageMode (OPTIONAL) Determines whether the customer is going
     * to make  changes to an existing appointment rather than booking a new one.
     */
    exports.initialize = function (bindEventHandlers, manageMode, defaultEventHandlers) {
        bindEventHandlers = bindEventHandlers || true;
        manageMode = manageMode || false;
        defaultEventHandlers = defaultEventHandlers || false;

        helper = new CustomersHelper();
        helper.resetForm();
        helper.filter('');
        _setActivityCookie();

        if (defaultEventHandlers) {
            _bindEventHandlers();
        }

        if (window.console === undefined) {
            window.console = function () {
            }; // IE compatibility
        }

        if (GlobalVariables.wasDeleted) {
            Backend.displayNotification('Patient and appointment was deleted.');
        }

        if (GlobalVariables.displayCookieNotice) {
            cookieconsent.initialise({
                palette: {
                    popup: {
                        background: '#ffffffbd',
                        text: '#666666'
                    },
                    button: {
                        background: '#3DD481',
                        text: '#ffffff'
                    }
                },
                content: {
                    message: EALang.website_using_cookies_to_ensure_best_experience,
                    dismiss: 'OK'
                },
            });

            $('.cc-link').replaceWith(
                $('<a/>', {
                    'data-toggle': 'modal',
                    'data-target': '#cookie-notice-modal',
                    'href': '#',
                    'class': 'cc-link',
                    'text': $('.cc-link').text()
                })
            );
        }

        BackendBook.manageMode = manageMode;

        // Initialize page's components (tool tips, date pickers etc).
        $('.book-step').qtip({
            position: {
                my: 'top center',
                at: 'bottom center'
            },
            style: {
                classes: 'qtip-green qtip-shadow custom-qtip'
            }
        });
        var previousDate = (BackendBook.manageMode)? new Date($('#original-select-start-date').val()) : '';
        var previousDay = (BackendBook.manageMode)? GeneralFunctions.formatDate(previousDate, 'DMY') : '';
        var date = new Date();
        var dtm = date.setDate(date.getDate() + 1);
        var defaultDate = (BackendBook.manageMode)? new Date(previousDay) : new Date(dtm);

        // If you are on the edit page, we need to change the default date
        if (GlobalVariables.appointmentData.start_datetime) {
            defaultDate = new Date(GlobalVariables.appointmentData.start_datetime);
        }

        // Set up calendar
        $('#select-date').datepicker({
            dateFormat: 'dd-mm-yy',
            firstDay: 0,
            minDate: 1,
            maxDate: 8, // because sundays are off
            defaultDate: defaultDate,

            dayNames: [
                EALang.sunday, EALang.monday, EALang.tuesday, EALang.wednesday,
                EALang.thursday, EALang.friday, EALang.saturday],
            dayNamesShort: [EALang.sunday.substr(0, 3), EALang.monday.substr(0, 3),
                EALang.tuesday.substr(0, 3), EALang.wednesday.substr(0, 3),
                EALang.thursday.substr(0, 3), EALang.friday.substr(0, 3),
                EALang.saturday.substr(0, 3)],
            dayNamesMin: [EALang.sunday.substr(0, 2), EALang.monday.substr(0, 2),
                EALang.tuesday.substr(0, 2), EALang.wednesday.substr(0, 2),
                EALang.thursday.substr(0, 2), EALang.friday.substr(0, 2),
                EALang.saturday.substr(0, 2)],
            monthNames: [EALang.january, EALang.february, EALang.march, EALang.april,
                EALang.may, EALang.june, EALang.july, EALang.august, EALang.september,
                EALang.october, EALang.november, EALang.december],
            prevText: EALang.previous,
            nextText: EALang.next,
            currentText: EALang.now,
            closeText: EALang.close,

            onSelect: function (dateText, instance) {
                BackendBookApi.getAvailableHours($(this).datepicker('getDate').toString('yyyy-MM-dd'));
                BackendBook.updateConfirmFrame();
            },

            onChangeMonthYear: function (year, month, instance) {
                var currentDate = new Date(year, month - 1, 1);
                BackendBookApi.getUnavailableDates($('#select-provider').val(), $('#select-service').val(),
                    currentDate.toString('yyyy-MM-dd'));
            }
        });

        // Continuously check available slots numbers for long range
        exports.check_available_appointments_long_range = () => {
            BackendBookApi.getAvailableAppointmentsLongRange($('#select-date').datepicker('getDate').toString('yyyy-MM-dd'));
        }

        // Wait 3 seconds
        setTimeout(BackendBook.check_available_appointments_long_range, 3000);

        // Continuously check available slots numbers
        function check_available_slots_per_hour() {
            BackendBookApi.getMultipleAttendantsHours($('#select-date').datepicker('getDate').toString('yyyy-MM-dd'));

            // Continue checking only if there is some activity
            if (Cookies.get("lastActivity") === '1') {
                setTimeout(check_available_slots_per_hour, 10000);
            }
        }

        // Wait 3 seconds
        setTimeout(check_available_slots_per_hour, 3000);

        // Bind the event handlers (might not be necessary every time we use this class).
        if (bindEventHandlers) {
            _bindEventHandlers();
        }

        // If the manage mode is true, the appointments data should be loaded by default.
        if (BackendBook.manageMode) {
            _applyAppointmentData(GlobalVariables.appointmentData,
                GlobalVariables.providerData, GlobalVariables.customerData);
        } else {
            var $selectProvider = $('#select-provider');
            var $selectService = $('#select-service');

            // Check if a specific service was selected (via URL parameter).
            var selectedServiceId = GeneralFunctions.getUrlParameter(location.href, 'service');

            if (selectedServiceId && $selectService.find('option[value="' + selectedServiceId + '"]').length > 0) {
                $selectService.val(selectedServiceId);
            }

            // Load the available hours.
            $selectService.trigger('change');

            // Check if a specific provider was selected.
            var selectedProviderId = GeneralFunctions.getUrlParameter(location.href, 'provider');

            if (selectedProviderId && $selectProvider.find('option[value="' + selectedProviderId + '"]').length === 0) {
                // Select a service of this provider in order to make the provider available in the select box.
                for (var index in GlobalVariables.availableProviders) {
                    var provider = GlobalVariables.availableProviders[index];

                    if (provider.id === selectedProviderId && provider.services.length > 0) {
                        $selectService
                            .val(provider.services[0])
                            .trigger('change');
                    }
                }
            }

            if (selectedProviderId && $selectProvider.find('option[value="' + selectedProviderId + '"]').length > 0) {
                $selectProvider
                    .val(selectedProviderId)
                    .trigger('change');
            }

            // If there is customer data, pre fill that data
            if ( Object.keys(GlobalVariables.customerData).length !== 0 && GlobalVariables.customerData.constructor === Object) {
                _applyCustomerData(GlobalVariables.customerData);
            }
        }
    };

    /**
     * This method binds the necessary event handlers for the book appointments page.
     */
    function _bindEventHandlers() {
        helper.bindEventHandlers();

        // Updated click activity
        $("body").click(function(){
            _setActivityCookie();
        });

        // Update tab activity
        $("body").keyup(function(e){
            var code = e.keyCode || e.which;
            if (code == '9') {
                _setActivityCookie();
            }
        });

        /**
         * Event: Selected Provider "Changed"
         *
         * Whenever the provider changes the available appointment date - time periods must be updated.
         */
        $('#select-provider').change(function () {
            BackendBookApi.getUnavailableDates($(this).val(), $('#select-service').val(),
                $('#select-date').datepicker('getDate').toString('yyyy-MM-dd'));
            BackendBook.updateConfirmFrame();
        });

        /**
         * Event: Selected Service "Changed"
         *
         * When the user clicks on a service, its available providers should
         * become visible.
         */
        $('#select-service').change(function () {
            var currServiceId = $('#select-service').val();
            $('#select-provider').empty();

            $.each(GlobalVariables.availableProviders, function (indexProvider, provider) {
                $.each(provider.services, function (indexService, serviceId) {
                    // If the current provider is able to provide the selected service,
                    // add him to the select input.
                    if (serviceId == currServiceId) {
                        var optionHtml = '<option value="' + provider.id + '">'
                            + provider.first_name + ' ' + provider.last_name
                            + '</option>';
                        $('#select-provider').append(optionHtml);
                    }
                });
            });

            // Add the "Any Provider" entry.
            if ($('#select-provider option').length >= 1) {
                $('#select-provider').append(new Option('- ' + EALang.any_provider + ' -', 'any-provider'));
            }

            BackendBookApi.getUnavailableDates($('#select-provider').val(), $(this).val(),
                $('#select-date').datepicker('getDate').toString('yyyy-MM-dd'));
            BackendBookApi.getAvailableHours($('#select-date').datepicker('getDate').toString('yyyy-MM-dd'));

            BackendBook.updateConfirmFrame();
            _updateServiceDescription($('#select-service').val(), $('#service-description'));
        });

        /**
         * Event: Next Step Button "Clicked"
         *
         * This handler is triggered every time the user pressed the "next" button on the book wizard.
         * Some special tasks might be performed, depending the current wizard step.
         */
        $('.button-next').click(function () {

            // If we are on the first step and there is no provider selected do not continue
            // with the next step.
            if ($(this).attr('data-step_index') === '1' && $('#select-provider').val() == null) {
                return;
            }

            // If we are on the 2nd tab, then the user should have an appointment hour
            // selected.
            if ($(this).attr('data-step_index') === '1') {
                // Let go through if it's in manage mode, assuming to use their original date
                if ($('.selected-hour').length == 0 && !BackendBook.manageMode) {
                    if ($('#select-hour-prompt').length == 0) {
                        $('#available-hours').append('<br><br>'
                            + '<span id="select-hour-prompt" class="text-danger">'
                            + EALang.appointment_hour_missing
                            + '</span>');
                    }
                    return;
                }
            }

            // General Error Messages

            // If there are any error messages shown on the screen, do not let them progress
            let errorMessages = $('.errorMessage').not('.errorMessage--email');
            let errorCount = 0;
            for(let x = 0; x < errorMessages.length; x++) {
                if($(errorMessages[x]).html() != '') errorCount++;
            }
            if(errorCount > 0) {
                GeneralFunctions.showSubmitError(true, 'Please fill out all required fields.')
                return;
            }
            else {
                GeneralFunctions.showSubmitError(false);
            }


            // Radio Button Validations
            // Caller
            if(!GeneralFunctions.checkRadiosForSelection('caller'))
            {
                GeneralFunctions.showSubmitError(true, 'Please select a caller at the top');
                return;
            }

            // Check if D.O.B is not empty.
            if ($('[data-dob]').val() != '') {
              var patientAge = GeneralFunctions.getCalculatedAge($('[data-dob]').val());
              // If the patient age is under 16, throw an error.
              if (patientAge < 16) {
                $('[data-dob]').parent('.formGroup').addClass('has-error');
                GeneralFunctions.showSubmitError(true, 'The patient must be over 16 years old.');
                return;
              }
            }

            // If we are on the 3rd tab then we will need to validate the user's
            // input before proceeding to the next step.
            if ($(this).attr('data-step_index') === '1') {
                if (!_validateCustomerForm()) {
                    GeneralFunctions.showSubmitError(true, 'Please fill out all required fields');
                    return; // Validation failed, do not continue.
                } else {
                    GeneralFunctions.showSubmitError(false);

                    BackendBook.updateConfirmFrame();

                    BackendBookApi.handleSubmitAppointment($('#select-date').val());
                }
            }

            // Display the next step tab (uses jquery animation effect).
            var nextTabIndex = parseInt($(this).attr('data-step_index')) + 1;

            $('#filter-customers').hide('fade');
            $('#customer-edit').hide('fade');
            $('.booking-header-bar').hide('fade');
            // FIXME: Remove this. It's targeting a section and hiding.
            // If an error occurs, part of the form is now missing and now we can't resubmit.
            // $(this).parents().eq(1).hide('fade', function () {
            //     //$('#wizard-frame-' + nextTabIndex).show('fade');
            // });
        });

        /**
         * Event: Refresh Hours Button "Clicked"
         *
         * This handler is triggered every time the user pressed the "Refresh Hours" button.
         */
        $('#refresh_hours').click(function () {
            BackendBook.check_available_appointments_long_range();
            BackendBookApi.getAvailableHours($('#select-date').val());
        });

        /**
         * Event: Back Step Button "Clicked"
         *
         * This handler is triggered every time the user pressed the "back" button on the
         * book wizard.
         */
        $('.button-back').click(function () {
            var prevTabIndex = parseInt($(this).attr('data-step_index')) - 1;

            $(this).parents().eq(1).hide('fade', function () {
                $('#wizard-frame-' + prevTabIndex).show('fade');
            });
        });

        /**
         * Event: Available Hour "Click"
         *
         * Triggered whenever the user clicks on an available hour
         * for his appointment.
         */
        $('#available-hours').on('click', '.available-hour', function () {
            $('.selected-hour').removeClass('selected-hour');
            $(this).addClass('selected-hour');
            BackendBook.updateConfirmFrame();
        });

        if (BackendBook.manageMode) {
            /**
             * Event: Cancel Appointment Button "Click"
             *
             * When the user clicks the "Cancel" button this form is going to be submitted. We need
             * the user to confirm this action because once the appointment is cancelled, it will be
             * delete from the database.
             *
             * @param {jQuery.Event} event
             */
            $('#cancel-appointment').click(function (event) {
                var buttons = [
                    {
                        text: 'OK',
                        click: function () {
                            if ($('#cancel-reason').val() === '') {
                                $('#cancel-reason').css('border', '2px solid red');
                                return;
                            }
                            $('#cancel-appointment-form textarea').val($('#cancel-reason').val());
                            $('#cancel-appointment-form').submit();
                        }
                    },
                    {
                        text: EALang.cancel,
                        click: function () {
                            $('#message_box').dialog('close');
                        }
                    }
                ];

                GeneralFunctions.displayMessageBox(EALang.cancel_appointment_title,
                    EALang.write_appointment_removal_reason, buttons);

                $('#message_box').append('<textarea id="cancel-reason" rows="3"></textarea>');
                $('#cancel-reason').css('width', '100%');
                return false;
            });

            // Open confirm modal if delete is clicked
            $('#delete-personal-information').on('click', function () {
              $('#delete-patient-confirmation-modal').modal('toggle');
            });

            // Delete patient if delete confirm is clicked
            $('#confirm-delete-patient').on('click', () => {

                let patients_fn = $('#first-name').val();
                let delete_fn = $('#delete-first-name').val();

                if(patients_fn == delete_fn) {
                    $('.deleteErrorMessage').html('');
                    $('#delete-first-name').parent().removeClass('has-error');
                    BackendBookApi.deletePersonalInformation(GlobalVariables.customerToken);
                } else if (delete_fn == '') {
                    $('.deleteErrorMessage').html('Please confirm the patients name');
                    $('#delete-first-name').parent().addClass('has-error');
                }
                else {
                    $('.deleteErrorMessage').html('That name does not match the record you are editing.');
                    $('#delete-first-name').parent().addClass('has-error');
                }

            })
        }

        /**
         * Event: Book Appointment Form "Submit"
         *
         * Before the form is submitted to the server we need to make sure that
         * in the meantime the selected appointment date/time wasn't reserved by
         * another customer or event.
         *
         * @param {jQuery.Event} event
         */
        $('#book-appointment-submit').click(function (event) {
            BackendBookApi.registerAppointment();
        });

        /**
         * Event: Refresh captcha image.
         *
         * @param {jQuery.Event} event
         */
        $('.captcha-title small').click(function (event) {
            $('.captcha-image').attr('src', GlobalVariables.baseUrl + '/captcha?' + Date.now());
        });


        $('#select-date').on('mousedown', '.ui-datepicker-calendar td', function (event) {
            setTimeout(function () {
                BackendBookApi.applyPreviousUnavailableDates(); // New jQuery UI version will replace the td elements.
            }, 300); // There is no draw event unfortunately.
        })
    }

    /**
     * This function validates the customer's data input. The user cannot continue
     * without passing all the validation checks.
     *
     * @return {Boolean} Returns the validation result.
     */
    function _validateCustomerForm() {
        $('#wizard-frame-1 .has-error').removeClass('has-error');
        $('#wizard-frame-1 label.text-danger').removeClass('text-danger');

        try {
            // Validate required fields.
            var missingRequiredField = false;
            $('.required').each(function () {
                if ($(this).val() == '') {
                    $(this).parents('.formGroup').addClass('has-error');
                    missingRequiredField = true;
                }
            });
            if (missingRequiredField) {
                throw EALang.fields_are_required;
            }

            var $acceptToTermsAndConditions = $('#accept-to-terms-and-conditions');
            if ($acceptToTermsAndConditions.length && !$acceptToTermsAndConditions.prop('checked')) {
                $acceptToTermsAndConditions.parents('label').addClass('text-danger');
                throw EALang.fields_are_required;
            }

            var $acceptToPrivacyPolicy = $('#accept-to-privacy-policy');
            if ($acceptToPrivacyPolicy.length && !$acceptToPrivacyPolicy.prop('checked')) {
                $acceptToPrivacyPolicy.parents('label').addClass('text-danger');
                throw EALang.fields_are_required;
            }


            // Validate email address.
            if (($('#email').val() != '') && !GeneralFunctions.validateEmail($('#email').val())) {
                $('#email').parents('.formGroup').addClass('has-error');
                throw EALang.invalid_email;
            }

            return true;
        } catch (exc) {
            $('#form-message').text(exc);
            return false;
        }
    }

    /**
     * Every time this function is executed, it updates the confirmation page with the latest
     * customer settings and input for the appointment booking.
     */
    exports.updateConfirmFrame = function () {

        // Since first name is required, if its not populated then we break out of this function
        let first_name = $('#first-name').val();
        if(first_name == '') {
            return;
        }

        let pre_selected_date = $('#original-select-start-date').val();
        let pre_selected_end_date = $('#original-select-end-date').val();
        var selServiceId = $('#select-service').val();
        var servicePrice;
        var serviceCurrency;

        $.each(GlobalVariables.availableServices, function (index, service) {
            if (service.id == selServiceId) {
                servicePrice = '<br>' + service.price;
                serviceCurrency = service.currency;
                return false; // break loop
            }
        });

        // Update appointment form data for submission to server when the user confirms
        // the appointment.
        var postData = {};

        // Massage field values
        var dob = $('#dob').val() ? GeneralFunctions.formatDate($('#dob').val(), 'YMD') : $('#dob').val();

        var rx_date;
        try {
            var rx_date_raw = $('#rx-date').val() ? GeneralFunctions.formatDate($('#rx-date').val(), 'YMD') : $('#rx-date').val();
            var epoch = Date.parse(`${rx_date_raw} 00:00:00 AM`);
            rx_date = new Date(epoch).toString('yyyy-MM-dd HH:mm:ss');
        } catch (error) {}

        var patient_consent = $('#patient-consent').prop('checked') ? '1' : '0';
        var patient_consent_sms = $('#patient-consent-sms').prop('checked') ? '1' : '0';
        var first_responder = $('#first-responder').prop('checked') ? '1' : '0';
        var city_worker = $('#city-worker').prop('checked') ? '1' : '0';

        // Assign FE data to BE object
        postData.customer = {
            address: $('#address').val(),
            apt: $('#apt').val(),
            caller: $('input[name=caller]:checked').val(),
            city: $('#city').val(),
            city_worker: city_worker,
            county: $('#county').val(),
            dob: dob,
            doctor_address: $('#doctor-address').val(),
            doctor_city: $('#doctor-city').val(),
            doctor_first_name: $('#doctor-first-name').val(),
            doctor_last_name: $('#doctor-last-name').val(),
            doctor_npi: $('#doctor-npi').val(),
            doctor_phone_number: $('#doctor-phone-number').val(),
            doctor_state: $('#doctor-state').val(),
            doctor_zip_code: $('#doctor-zip-code').val(),
            email: $('#email').val(),
            first_name: $('#first-name').val(),
            first_responder: first_responder,
            gender: $('#gender').val(),
            last_name: $('#last-name').val(),
            middle_initial: $('#middle-initial').val(),
            mobile_number: $('#mobile-number').val(),
            patient_consent: patient_consent,
            patient_consent_sms: patient_consent_sms,
            pcp_first_name: $('#pcp-first-name').val(),
            pcp_last_name: $('#pcp-last-name').val(),
            pcp_phone_number: $('#pcp-phone-number').val(),
            pcp_address: $('#pcp-address').val(),
            pcp_city: $('#pcp-city').val(),
            pcp_state: $('#pcp-state').val(),
            pcp_zip_code: $('#pcp-zip-code').val(),
            phone_number: $('#phone-number').val(),
            provider_patient_id: $('#provider-patient-id').val(),
            rx_date: rx_date,
            ssn: $('#ssn').val(),
            state: $('#state').val(),
            zip_code: $('#zip-code').val()
        };

        var start_datetime, end_datetime, updateTime = true;

        // If not in edit mode
        if(!BackendBook.manageMode) {
            let start_date = $('#select-date').datepicker('getDate');
            let selected_hour = Date.parse($('.selected-hour').text());
            if(start_date != null && selected_hour != null) {
                start_datetime = start_date.toString('yyyy-MM-dd')
                + ' ' + selected_hour.toString('HH:mm') + ':00';
                end_datetime = _calcEndDatetime();
            }
        }
        else {
            // Edit mode
            if($('.selected-hour').length > 0) {
                start_datetime = $('#select-date').datepicker('getDate').toString('yyyy-MM-dd')
                + ' ' + Date.parse($('.selected-hour').text()).toString('HH:mm') + ':00';
                end_datetime = _calcEndDatetime();

                var rescheduleAllowed = $('#rescheduleAllowed').val();

                // If the time was selected but its not different
                if(start_datetime != '' && (pre_selected_date == start_datetime)) {
                    updateTime = false;
                }
                if(start_datetime != '' && (pre_selected_date != start_datetime)) {
                    updateTime = true;
                }

                // dont try to update the time if you cant reschedule
                if (rescheduleAllowed !== '1') {
                    updateTime = false;
                }
            }
            else {
                updateTime = false;
                start_datetime = pre_selected_date;
                end_datetime = pre_selected_end_date;
            }

        }

        postData.appointment = {
            notes: $('#notes').val(),
            is_unavailable: false,
            id_users_provider: $('#select-provider').val(),
            id_services: $('#select-service').val(),
            business_code: $('#business-code').val(),
            start_datetime: start_datetime,
            end_datetime: end_datetime,
            updateTime: updateTime
        };

        postData.manage_mode = BackendBook.manageMode;

        if (BackendBook.manageMode) {
            postData.appointment.id = GlobalVariables.appointmentData.id;
            postData.customer.id = GlobalVariables.customerData.id;
        }
        $('input[name="csrfToken"]').val(GlobalVariables.csrfToken);
        $('input[name="post_data"]').val(JSON.stringify(postData));
    };

    /**
     * This method calculates the end datetime of the current appointment.
     * End datetime is depending on the service and start datetime fields.
     *
     * @return {String} Returns the end datetime in string format.
     */
    function _calcEndDatetime() {
        // Find selected service duration.
        var selServiceDuration = undefined;

        $.each(GlobalVariables.availableServices, function (index, service) {
            if (service.id == $('#select-service').val()) {
                selServiceDuration = service.duration;
                return false; // Stop searching ...
            }
        });

        // Add the duration to the start datetime.
        var startDatetime = $('#select-date').datepicker('getDate').toString('dd-MM-yyyy')
            + ' ' + Date.parse($('.selected-hour').text()).toString('HH:mm');
        startDatetime = Date.parseExact(startDatetime, 'dd-MM-yyyy HH:mm');
        var endDatetime = undefined;

        if (selServiceDuration !== undefined && startDatetime !== null) {
            endDatetime = startDatetime.add({'minutes': parseInt(selServiceDuration)});
        } else {
            endDatetime = new Date();
        }

        return endDatetime.toString('yyyy-MM-dd HH:mm:ss');
    }

    function _setActivityCookie() {
        var date = new Date();
        var minutes = 1;
        date.setTime(date.getTime() + (minutes * 60 * 1000));
        Cookies.set("lastActivity", "1", { expires: date, path: '/' });
    }

    /**
     * This method applies the appointment's data to the wizard so
     * that the user can start making changes on an existing record.
     *
     * @param {Object} appointment Selected appointment's data.
     * @param {Object} provider Selected provider's data.
     * @param {Object} customer Selected customer's data.
     *
     * @return {Boolean} Returns the operation result.
     */
    function _applyAppointmentData(appointment, provider, customer) {
        try {
            // Select Service & Provider
            $('#select-service').val(appointment.id_services).change();
            $('#select-provider').val(appointment.id_users_provider);

            // fill in business code and trigger validation
            if (appointment.business_code !== '') {
                $('#business-code').val(appointment.business_code);
                $('#business-code').blur();
            }

            $('#city-worker').val(appointment.city_worker);
            // trigger the click event, so we can make the fields optional
            if (appointment.city_worker === 1) {
                $('#city-worker').click();
            }

            // Set Appointment Date
            $('#select-date').datepicker('setDate',
                Date.parseExact(appointment.start_datetime, 'yyyy-MM-dd HH:mm:ss'));
            BackendBookApi.getAvailableHours($('#select-date').val());

            _applyCustomerData(customer);

            BackendBook.updateConfirmFrame();

            return true;
        } catch (exc) {
            return false;
        }
    }

    function _applyCustomerData(customer) {
        // Apply Customer's Data to Edit Fields
        $('#' + customer.caller).trigger('click'); // trigger the click to hide/show things
        $('#address').val(customer.address);
        $('#city').val(customer.city);
        $('#county').val(customer.county);
        $('#apt').val(customer.apt);
        var dob = customer.dob.split(' ');
        var dobDate = dob[0].split('-');
        dobDate = dobDate[1] + '-' + dobDate[2] + '-' + dobDate[0];
        $('[data-age]').html(GeneralFunctions.getCalculatedAge(dobDate));
        $('#dob').val(dobDate);
        $('#doctor-npi').val(customer.doctor_npi);
        $('#doctor-first-name').val(customer.doctor_first_name);
        $('#doctor-last-name').val(customer.doctor_last_name);
        $('#doctor-phone-number').val(customer.doctor_phone_number);
        $('#doctor-address').val(customer.doctor_address);
        $('#doctor-city').val(customer.doctor_city);
        $('#doctor-state').val(customer.doctor_state);
        $('#doctor-zip-code').val(customer.doctor_zip_code);
        $('#email').val(customer.email);
        $('#first-name').val(customer.first_name);
        $('#last-name').val(customer.last_name);
        $('#middle-initial').val(customer.middle_initial);
        $('#gender').val(customer.gender);
        $('#mobile-number').val(customer.mobile_number);
        $('#phone-number').val(customer.phone_number);
        $('#provider-patient-id').val(customer.provider_patient_id);
        $('#state').val(customer.state);
        $('#zip-code').val(customer.zip_code);
        $('#patient-consent').val(customer.patient_consent);
        $('#patient-consent-sms').val(customer.patient_consent_sms);
        $('#pcp-first-name').val(customer.pcp_first_name);
        $('#pcp-last-name').val(customer.pcp_last_name);
        $('#pcp-phone-number').val(customer.pcp_phone_number);
        $('#pcp-address').val(customer.pcp_address);
        $('#pcp-city').val(customer.pcp_city);
        $('#pcp-state').val(customer.pcp_state);
        $('#pcp-zip-code').val(customer.pcp_zip_code);

        // Rearrange the RX date
        var rx_date = customer.rx_date.split(' ');
        var date = rx_date[0].split('-');
        $('#rx-date').val(date[1] + '-' + date[2] + '-' + date[0]);

        $('#ssn').val(customer.ssn);

        (customer.patient_consent == '1') ? $('#patient-consent').prop('checked', true) : $('#patient-consent').prop('checked', false);
        (customer.patient_consent_sms == '1') ? $('#patient-consent-sms').prop('checked', true) : $('#patient-consent-sms').prop('checked', false);
        (customer.caller == 'patient') ? $('#patient').prop('checked', true) : null;
        (customer.caller == 'provider') ? $('#provider').prop('checked', true) : null;
        (customer.caller == 'essential-worker') ? $('#essential-worker').prop('checked', true) : null;

        var amPm = new Date(customer.rx_date).toString("tt");
        (amPm == 'AM')? $('#rx-am').prop('checked', true) : $('#rx-am').prop('checked', false);
        (amPm == 'PM')? $('#rx-pm').prop('checked', true) : $('#rx-pm').prop('checked', false);

        (customer.first_responder == '1') ? $('#first-responder').prop('checked', true) : $('#first-responder').prop('checked', false);
    }

    /**
     * This method updates a div's html content with a brief description of the
     * user selected service (only if available in db). This is useful for the
     * customers upon selecting the correct service.
     *
     * @param {Number} serviceId The selected service record id.
     * @param {Object} $div The destination div jquery object (e.g. provide $('#div-id')
     * object as value).
     */
    function _updateServiceDescription(serviceId, $div) {
        var html = '';

        $.each(GlobalVariables.availableServices, function (index, service) {
            if (service.id == serviceId) { // Just found the service.
                html = '<strong>' + service.name + ' </strong>';

                if (service.description != '' && service.description != null) {
                    html += '<br>' + service.description + '<br>';
                }

                if (service.duration != '' && service.duration != null) {
                    html += '[' + EALang.duration + ' ' + service.duration + ' ' + EALang.minutes + ']';
                }

                if (service.price != '' && service.price != null) {
                    html += '[' + EALang.price + ' ' + service.price + ' ' + service.currency + ']';
                }

                html += '<br>';

                return false;
            }
        });

        $div.html(html);

        if (html != '') {
            $div.show();
        } else {
            $div.hide();
        }
    }

    // Update age on blur of dob field
    $('[data-dob]').on('blur', () => {
        if($('[data-dob]').val() != '') {
            $('#ageCalculated').removeClass('hide');
            $('[data-age]').html(GeneralFunctions.getCalculatedAge($('[data-dob]').val()));
        }
    });

    var doctorNpi = $('#doctor-npi');

    // If patient is checked and
    // make NPI field not required.
    $('#patient').on('click', () => {
        doctorNpi.removeClass('required');
        doctorNpi.removeAttr('data-validate');
    })

    // If provider is checked and
    // make NPI field required.
    $('#provider').on('click', () => {
        doctorNpi.addClass('required');
        doctorNpi.attr('data-validate', 'required');
    })


    // Formatting inputs on blur
    $('[data-format-string]').on('blur', (e) => {
        let format = $(e.target).data('formatString');
        $(e.target).val(GeneralFunctions.formatString($(e.target).val(), format));
    });

    // Show calendar when clicking edit
    $('.editAppt').on('click', ()=>{
        $('.sidePanel__schedule-appointment').removeClass('hide');
    });

    // City worker Toggle
    $('#city-worker').on('click', () => {
        if($('#city-worker').is(':checked')) {
            $('#business-code').val('');
            $('#business-code').parent().removeClass('has-error');
            $('#business-code').parents().next('.errorMessage').html('');
            $('#business-code').attr('data-validate', '');
            $('#essential-worker').click();
            $('#business-code-container').addClass('hide');
            $('#doctor-section').addClass('hide');
            $('#pcp-section').removeClass('hide');
            requirePhysicianInfo(false);
            $('#button-next-1').prop('disabled', false);
            $('.submitErrorMessage').html('');

            // capture the last used service id
            var last_used_service_id = $('#select-service').val();
            $('#last_used_service_id').val(last_used_service_id);

            // Show the calendar spinner
            $('.bookAppointment__calendarSpinner').removeClass('hide');

            // Wait a second, hide the calendar spinner
            setTimeout(function() {
              $('.bookAppointment__calendarSpinner').addClass('hide');
            }, 1000)

            // change the service to be for business
            var business_service_id = $('#business_service_id').val();
            $('#select-service').val(business_service_id);
            $('#select-service').change();

        }
        else {
            if($('#essential-worker').is(':checked')) {
                if(!BackendBook.manageMode) {
                    $('#button-next-1').prop('disabled', true);
                    $('.submitErrorMessage').html('Business code must be validated.').removeClass('hide');
                }
                $('#business-code-container').removeClass('hide');
                $('#business-code').attr('data-validate', 'required, businessCode');
                requirePhysicianInfo(false);
            }
            else {
                $('#button-next-1').prop('disabled', false);
                $('.submitErrorMessage').html('');
                $('[data-toggle-business-code="show"]').removeClass('hide');
                requirePhysicianInfo(true);
            }

            // Show the calendar spinner
            $('.bookAppointment__calendarSpinner').removeClass('hide');

            // Wait a second, hide the calendar spinner
            setTimeout(function() {
              $('.bookAppointment__calendarSpinner').addClass('hide');
            }, 1000)

            // revert back to last used id
            var last_used_service_id = $('#last_used_service_id').val();
            $('#select-service').val(last_used_service_id);
            $('#select-service').change();
        }
    });

    // Toggle Business Code on caller click
    $('input[name="caller"]').on('click', (e) => {
        let business_code_container = $('#business-code-container');
        let business_code = $('#business-code');

        if(e.target.id == 'essential-worker') {
            if(!BackendBook.manageMode) {
                if(!$('#city-worker').is(':checked')) {
                    $('#business-code').attr('data-validate', 'required, businessCode');
                }
                else {
                    $('#business-code').parent().next('.errorMessage').html('');
                }
            }

            if(!$('#city-worker').is(':checked')) {
                if(!BackendBook.manageMode) {
                    $('#button-next-1').prop('disabled', true);
                    $('.submitErrorMessage').html('Business code must be validated.').removeClass('hide');
                }
                business_code_container.removeClass('hide');
                business_code.focus();
                requirePhysicianInfo(false);
                $('#doctor-section').addClass('hide');
                $('#pcp-section').removeClass('hide');
            }
        } else {
            $('#business-code-container').children('.errorMessage').html('');
            $('#business-code-container').children('.formGroup').removeClass('has-error');
            $('#busCodeError').addClass('hide');
            $('#business-code').attr('data-validate', 'businessCode');
            $('#button-next-1').prop('disabled', false);
            $('.submitErrorMessage').html('');
            if($('#city-worker').is(':checked')) {
                $('#city-worker').prop('checked', false);
            }
            business_code_container.addClass('hide');
            if(!BackendBook.manageMode) {
                business_code.val('');
            }
            business_code.blur();
            business_code.parent().removeClass('has-error');
            business_code.parent().next('.errorMessage').html('');
            requirePhysicianInfo(true);
            $('#doctor-section').removeClass('hide');
            $('#pcp-section').addClass('hide');
        }
    });

    /**
     * This method sets selected fields to either required of not required
     *
     * @param {Boolean} required To set required or not
     */
    function requirePhysicianInfo(required) {
        let fields = [
            'doctor-first-name',
            'doctor-last-name',
            'doctor-phone-number',
            'doctor-address',
            'doctor-city',
            'doctor-state',
            'doctor-zip-code',
            'rx-date',
        ]

        if(!required) {
            for(let i = 0; i < fields.length; i++) {
                let field = document.getElementById(fields[i]);
                let validations = field.dataset['validate'].replace(/\s/g,'').split(',');
                validations = validations.filter(e => e !== 'required').join();
                field.dataset['validate'] = validations;
                field.classList.remove('required');
            }
        }
        else {
            for(let i = 0; i < fields.length; i++) {
                let field = document.getElementById(fields[i]);
                let validations = field.dataset['validate'].replace(/\s/g,'').split(',');
                validations.unshift('required');
                field.dataset['validate'] = validations.join();
                field.classList.add('required');
            }
        }
    }

    // Grab offset of header
    let topofDiv = $(".header").offset().top;
    // Grab height of header
    let height = $(".header").outerHeight();
    // Side panel elements
    let sidePanel = $('.sidePanel');
    let sidePanelContent = $('.sidePanel__content');
    let sidePanelTrigger = $('.sidePanelTrigger');

    // Open the side panel on click
    sidePanelTrigger.on('click', function(e) {
      e.stopPropagation();
      sidePanel.toggleClass('is-opened');
      sidePanelTrigger.toggleClass('is-active')
    });

    // Close the side panel if the event was outside of the sidepanel
    $('body').on('click', function(e) {
      if (!sidePanel.is(e.target) && sidePanel.has(e.target).length === 0) {
          sidePanel.removeClass('is-opened');
          sidePanelTrigger.removeClass('is-active');
      }
    });

    // When you scroll pass header, add fixed class
    $(window).scroll(function(){
        if($(window).scrollTop() > (topofDiv + height)){
          sidePanelContent.addClass('is-fixed');
          sidePanelTrigger.addClass('is-fixed');
        }
        else{
          sidePanelContent.removeClass('is-fixed');
          sidePanelTrigger.removeClass('is-fixed');
        }
    });
})(window.BackendBook);
