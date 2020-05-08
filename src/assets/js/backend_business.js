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

window.BackendBusiness = window.BackendBusiness || {};

/**
 * Backend Business
 *
 * This module contains functions that implement the business page functionality. Once the
 * initialize() method is called the page is fully functional and can serve the business creation
 * process.
 *
 * @module BackendBusiness
 */
(function (exports) {

    'use strict';

    /**
     * The page helper contains methods that implement each record type functionality
     * (for now there is only the BusinessHelper).
     *
     * @type {Object}
     */
    var helper = {};

    /**
     * This method initializes the book appointment page.
     *
     * @param {Boolean} bindEventHandlers (OPTIONAL) Determines whether the default
     * event handlers will be bound to the dom elements.
     */
    exports.initialize = function (bindEventHandlers, defaultEventHandlers) {
        bindEventHandlers = bindEventHandlers || true;
        defaultEventHandlers = defaultEventHandlers || true;

        helper = new BusinessHelper();
        helper.resetForm();

        // If the manage mode is true, the business data should be loaded by default.
        if (GlobalVariables.manage_mode) {
            _applyBusinessData(GlobalVariables.business);
        }

        if (defaultEventHandlers) {
            _bindEventHandlers();
        }

        if (window.console === undefined) {
            window.console = function () {
            }; // IE compatibility
        }

    };

    /**
     * This method binds the necessary event handlers for the book appointments page.
     */
    function _bindEventHandlers() {

        helper.bindEventHandlers();

        /**
         * Event: Submit Button "Clicked"
         *
         * This handler is triggered every time the user pressed the "submit" button.
         */
        $('#business-submit').click(function () {
            let mobileField = $('#mobile_phone');
            let smsConsentCheckbox = $('#consent_phone');
            // If mobile phone field is blank and sms consent is checked,
            // uncheck the checkbox.
            if (mobileField.val() == '' && smsConsentCheckbox.is(":checked")) {
                smsConsentCheckbox.prop("checked", false);
            }

            let emailField = $('#email');
            let emailConsentCheckbox = $('#consent_email');
            // If email field is blank and email consent is checked,
            // uncheck the checkbox.
            if (emailField.val() == '' && emailConsentCheckbox.is(":checked")) {
                emailConsentCheckbox.prop("checked", false);
            }

            // Loop through required fields
            let requiredFields = $('.required');
            let requiredFieldsCount = 0;
            for (let x = 0; x < requiredFields.length; x++) {
                if ($(requiredFields[x]).val() == '') {
                    requiredFieldsCount++
                };
            }

            // Loop through error messages
            let errorMessages = $('.errorMessage');
            let errorCount = 0;
            for (let x = 0; x < errorMessages.length; x++) {
                if ($(errorMessages[x]).html() != '') {
                    errorCount++;
                }
            }

            // If there are any error messages shown on the screen or required fields
            // are blank, do not let them progress
            if (errorCount > 0 || requiredFieldsCount > 0) {
                $('.required').each(function () {
                    if ($(this).val() == '') {
                        $(this).parents('.formGroup').addClass('has-error');
                    }
                });
                GeneralFunctions.showSubmitError(true, 'Please fill out all required fields.')
                return;
            } else {
                GeneralFunctions.showSubmitError(false);
                BackendBusiness.registerBusiness();
            }
        });
    }

    /**
     * Register an business to the database.
     *
     * This method will make an ajax call to the business controller that will register
     * the business to the database.
     */
    exports.registerBusiness = function () {
        var postData = $('#business-form').serialize();

        var postUrl = GlobalVariables.baseUrl + '/backend/business';

        $.ajax({
            url: postUrl,
            method: 'post',
            data: postData,
            dataType: 'json',
            beforeSend: function () {
                let submitButton = $('#business-submit');
                submitButton.html('<span class="spinner spinner--circle"></span>')
            }
        })
        .done(function (response) {
            if (!GeneralFunctions.handleAjaxExceptions(response)) {
                Backend.displayNotification(response['exceptions'][0]['message'], null, 'danger');

                return false;
            }
            window.location.href = GlobalVariables.baseUrl
                + '/backend/business_success/' + response.id
                + '/' + response.r_id + (response.manage_mode ? '?manage_mode=1' : '');
        })
        .fail(function (jqxhr, textStatus, errorThrown) {
            GeneralFunctions.ajaxFailureHandler(jqxhr, textStatus, errorThrown);
        })
        .always(function () {
            submitButton.remove();
        });
    };


    function _applyBusinessData(business) {
        $('#business_name').val(business.business_name);
        $('#owner_first_name').val(business.owner_first_name);
        $('#owner_last_name').val(business.owner_last_name);
        $('#business_phone').val(business.business_phone);
        $('#mobile_phone').val(business.mobile_phone);
        $('#email').val(business.email);
        $('#address').val(business.address);
        $('#city').val(business.city);
        $('#state').val(business.state);
        $('#zip-code').val(business.zip_code);
        $('#email').val(business.email);

        (business.consent_sms == '1') ? $('#consent_sms').prop('checked', true) : $('#consent_sms').prop('checked', false);
        (business.consent_email == '1') ? $('#consent_email').prop('checked', true) : $('#consent_email').prop('checked', false);
    }

    // Formatting inputs on blur
    $('[data-format-string]').on('blur', (e) => {
        let format = $(e.target).data('formatString');
        $(e.target).val(GeneralFunctions.formatString($(e.target).val(), format));
    });
})(window.BackendBusiness);
