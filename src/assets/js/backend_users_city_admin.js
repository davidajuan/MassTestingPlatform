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
     * City Admin Helper
     *
     * This class contains the Secretaries helper class declaration, along with the "Secretaries"
     * tab event handlers. By dividing the backend/users tab functionality into separate files
     * it is easier to maintain the code.
     *
     * @class CityAdminHelper
     */
    var CityAdminHelper = function () {
        this.filterResults = {}; // Store the results for later use.
    };

    /**
     * Bind the event handlers for the backend/users "Secretaries" tab.
     */
    CityAdminHelper.prototype.bindEventHandlers = function () {
        /**
         * Event: Filter Secretaries Form "Submit"
         *
         * Filter the city admin records with the given key string.
         */
        $('#city-admin').on('submit', '#filter-city-admin form', function () {
            var key = $('#filter-city-admin .key').val();
            $('#filter-city-admin .selected').removeClass('selected');
            this.resetForm();
            this.filter(key);
            return false;
        }.bind(this));

        /**
         * Event: Clear Filter Results Button "Click"
         */
        $('#city-admin').on('click', '#filter-city-admin .clear', function () {
            this.filter('');
            $('#filter-city-admin .key').val('');
            this.resetForm();
        }.bind(this));

        /**
         * Event: Filter city admin Row "Click"
         *
         * Display the selected city admin data to the user.
         */
        $('#city-admin').on('click', '.city-admin-row', function (e) {
            if ($('#filter-city-admin .filter').prop('disabled')) {
                $('#filter-city-admin .results').css('color', '#AAA');
                return; // exit because we are currently on edit mode
            }

            var cityAdminId = $(e.currentTarget).attr('data-id');
            var cityAdmin = {};

            $.each(this.filterResults, function (index, item) {
                if (item.id === cityAdminId) {
                  cityAdmin = item;
                    return false;
                }
            });

            this.display(cityAdmin);

            $('#filter-city-admin .selected').removeClass('selected');
            $(e.currentTarget).addClass('selected');
            $('#edit-city-admin, #delete-city-admin').prop('disabled', false);
        }.bind(this));

        /**
         * Event: Add New City Admin Button "Click"
         */
        $('#city-admin').on('click', '#add-city-admin', function () {
            this.resetForm();
            $('#filter-city-admin button').prop('disabled', true);
            $('#filter-city-admin .results').css('color', '#AAA');

            $('#city-admin .add-edit-delete-group').hide();
            $('#city-admin .save-cancel-group').show();
            $('#city-admin .record-details').find('input, textarea').prop('readonly', false);
            $('#city-admin .record-details').find('select').prop('disabled', false);
            $('#city-admin-password, #city-admin-password-confirm').addClass('required');
            $('#city-admin-notifications').prop('disabled', false);
            $('#city-admin-providers input:checkbox').prop('disabled', false);
        }.bind(this));

        /**
         * Event: Edit City Admin Button "Click"
         */
        $('#city-admin').on('click', '#edit-city-admin', function () {
            $('#filter-city-admin button').prop('disabled', true);
            $('#filter-city-admin .results').css('color', '#AAA');
            $('#city-admin .add-edit-delete-group').hide();
            $('#city-admin .save-cancel-group').show();
            $('#city-admin .record-details').find('input, textarea').prop('readonly', false);
            $('#city-admin .record-details').find('select').prop('disabled', false);
            $('#city-admin-password, #city-admin-password-confirm').removeClass('required');
            $('#city-admin-notifications').prop('disabled', false);
            $('#city-admin-providers input:checkbox').prop('disabled', false);
        });

        /**
         * Event: Delete City Admin Button "Click"
         */
        $('#city-admin').on('click', '#delete-city-admin', function () {
            var cityAdminId = $('#city-admin-id').val();
            var buttons = [
                {
                    text: EALang.delete,
                    click: function () {
                        this.delete(cityAdminId);
                        $('#message_box').dialog('close');
                    }.bind(this)
                },
                {
                    text: EALang.cancel,
                    click: function () {
                        $('#message_box').dialog('close');
                    }
                }
            ];

            GeneralFunctions.displayMessageBox(EALang.delete_city_admin,
                EALang.delete_record_prompt, buttons);
        }.bind(this));

        /**
         * Event: Save City Admin Button "Click"
         */
        $('#city-admin').on('click', '#save-city-admin', function () {
            var cityAdmin = {
                first_name: $('#city-admin-first-name').val(),
                last_name: $('#city-admin-last-name').val(),
                email: $('#city-admin-email').val(),
                mobile_number: $('#city-admin-mobile-number').val(),
                phone_number: $('#city-admin-phone-number').val(),
                address: $('#city-admin-address').val(),
                city: $('#city-admin-city').val(),
                state: $('#city-admin-state').val(),
                zip_code: $('#city-admin-zip-code').val(),
                notes: $('#city-admin-notes').val(),
                settings: {
                    username: $('#city-admin-username').val(),
                    notifications: $('#city-admin-notifications').hasClass('active'),
                    calendar_view: $('#city-admin-calendar-view').val()
                }
            };

            // Include city admin services.
            cityAdmin.providers = [];
            $('#city-admin-providers input:checkbox').each(function () {
                if ($(this).prop('checked')) {
                  cityAdmin.providers.push($(this).attr('data-id'));
                }
            });

            // Include password if changed.
            if ($('#city-admin-password').val() !== '') {
                cityAdmin.settings.password = $('#city-admin-password').val();
            }

            // Include ID if changed.
            if ($('#city-admin-id').val() !== '') {
                cityAdmin.id = $('#city-admin-id').val();
            }

            if (!this.validate()) {
                return;
            }

            this.save(cityAdmin);
        }.bind(this));

        /**
         * Event: Cancel City Admin Button "Click"
         *
         * Cancel add or edit of an city admin record.
         */
        $('#city-admin').on('click', '#cancel-city-admin', function () {
            var id = $('#city-admin-id').val();
            this.resetForm();
            if (id != '') {
                this.select(id, true);
            }
        }.bind(this));
    };

    /**
     * Save city admin record to database.
     *
     * @param {Object} cityAdmin Contains the admin record data. If an 'id' value is provided
     * then the update operation is going to be executed.
     */
    CityAdminHelper.prototype.save = function (cityAdmin) {
        var postUrl = GlobalVariables.baseUrl + '/backend_api/ajax_save_city_admin';
        var postData = {
            csrfToken: GlobalVariables.csrfToken,
            cityadmin: JSON.stringify(cityAdmin)
        };

        $.post(postUrl, postData, function (response) {
            if (!GeneralFunctions.handleAjaxExceptions(response)) {
                return;
            }
            Backend.displayNotification(EALang.city_admin_saved);
            this.resetForm();
            $('#filter-city-admin .key').val('');
            this.filter('', response.id, true);
        }.bind(this), 'json').fail(GeneralFunctions.ajaxFailureHandler);
    };

    /**
     * Delete a city admin record from database.
     *
     * @param {Number} id Record id to be deleted.
     */
    CityAdminHelper.prototype.delete = function (id) {
        var postUrl = GlobalVariables.baseUrl + '/backend_api/ajax_delete_city_admin';
        var postData = {
            csrfToken: GlobalVariables.csrfToken,
            cityadmin_id: id
        };

        $.post(postUrl, postData, function (response) {
            if (!GeneralFunctions.handleAjaxExceptions(response)) {
                return;
            }
            Backend.displayNotification(EALang.delete_city_admin);
            this.resetForm();
            this.filter($('#filter-city-admin .key').val());
        }.bind(this), 'json').fail(GeneralFunctions.ajaxFailureHandler);
    };

    /**
     * Validates a city admin record.
     *
     * @return {Boolean} Returns the validation result.
     */
    CityAdminHelper.prototype.validate = function () {
        $('#city-admin .has-error').removeClass('has-error');
        $('#city-admin .form-message').removeClass('alert-danger');

        try {
            // Validate required fields.
            var missingRequired = false;
            $('#city-admin .required').each(function () {
                if ($(this).val() == '' || $(this).val() == undefined) {
                    $(this).closest('.formGroup').addClass('has-error');
                    missingRequired = true;
                }
            });
            if (missingRequired) {
                throw 'Fields with * are  required.';
            }

            // Validate passwords.
            if ($('#city-admin-password').val() != $('#city-admin-password-confirm').val()) {
                $('#city-admin-password, #city-admin-password-confirm').closest('.formGroup').addClass('has-error');
                throw 'Passwords mismatch!';
            }

            if ($('#city-admin-password').val().length < BackendUsers.MIN_PASSWORD_LENGTH
                && $('#city-admin-password').val() != '') {
                $('#city-admin-password, #city-admin-password-confirm').closest('.formGroup').addClass('has-error');
                throw 'Password must be at least ' + BackendUsers.MIN_PASSWORD_LENGTH
                + ' characters long.';
            }

            // Validate user email.
            if (!GeneralFunctions.validateEmail($('#city-admin-email').val())) {
                $('#city-admin-email').closest('.formGroup').addClass('has-error');
                throw 'Invalid email address!';
            }

            // Check if username exists
            if ($('#city-admin-username').attr('already-exists') == 'true') {
                $('#city-admin-username').closest('.formGroup').addClass('has-error');
                throw 'Username already exists.';
            }

            return true;
        } catch (message) {
            $('#city-admin .form-message')
                .addClass('alert-danger')
                .text(message)
                .show();
            return false;
        }
    };

    /**
     * Resets the admin tab form back to its initial state.
     */
    CityAdminHelper.prototype.resetForm = function () {
        $('#city-admin .record-details').find('input, textarea').val('');
        $('#city-admin .add-edit-delete-group').show();
        $('#city-admin .save-cancel-group').hide();
        $('#edit-city-admin, #delete-city-admin').prop('disabled', true);
        $('#city-admin .record-details').find('input, textarea').prop('readonly', true);
        $('#city-admin .record-details').find('select').prop('disabled', true);
        $('#city-admin .form-message').hide();
        $('#city-admin-notifications').removeClass('active');
        $('#city-admin-notifications').prop('disabled', true);
        $('#city-admin-providers input:checkbox').prop('checked', false);
        $('#city-admin-providers input:checkbox').prop('disabled', true);
        $('#city-admin .has-error').removeClass('has-error');

        $('#filter-city-admin .selected').removeClass('selected');
        $('#filter-city-admin button').prop('disabled', false);
        $('#filter-city-admin .results').css('color', '');
    };

    /**
     * Display a city admin record into the admin form.
     *
     * @param {Object} cityAdmin Contains the city admin record data.
     */
    CityAdminHelper.prototype.display = function (cityAdmin) {
        $('#city-admin-id').val(cityAdmin.id);
        $('#city-admin-first-name').val(cityAdmin.first_name);
        $('#city-admin-last-name').val(cityAdmin.last_name);
        $('#city-admin-email').val(cityAdmin.email);
        $('#city-admin-mobile-number').val(cityAdmin.mobile_number);
        $('#city-admin-phone-number').val(cityAdmin.phone_number);
        $('#city-admin-address').val(cityAdmin.address);
        $('#city-admin-city').val(cityAdmin.city);
        $('#city-admin-state').val(cityAdmin.state);
        $('#city-admin-zip-code').val(cityAdmin.zip_code);
        $('#city-admin-notes').val(cityAdmin.notes);

        $('#city-admin-username').val(cityAdmin.settings.username);
        $('#city-admin-calendar-view').val(cityAdmin.settings.calendar_view);
        if (cityAdmin.settings.notifications == true) {
            $('#city-admin-notifications').addClass('active');
        } else {
            $('#city-admin-notifications').removeClass('active');
        }

        $('#city-admin-providers input:checkbox').prop('checked', false);
        $.each(cityAdmin.providers, function (index, providerId) {
            $('#city-admin-providers input:checkbox').each(function () {
                if ($(this).attr('data-id') == providerId) {
                    $(this).prop('checked', true);
                }
            });
        });
    };

    /**
     * Filters city admin records depending a string key.
     *
     * @param {String} key This is used to filter the city admin records of the database.
     * @param {Numeric} selectId Optional, if provided the given ID will be selected in the filter results
     * (only selected, not displayed).
     * @param {Bool} display Optional (false).
     */
    CityAdminHelper.prototype.filter = function (key, selectId, display) {
        display = display || false;

        var postUrl = GlobalVariables.baseUrl + '/backend_api/ajax_filter_city_admin';
        var postData = {
            csrfToken: GlobalVariables.csrfToken,
            key: key
        };

        $.post(postUrl, postData, function (response) {
            if (!GeneralFunctions.handleAjaxExceptions(response)) {
                return;
            }

            this.filterResults = response;

            $('#filter-city-admin .results').html('');
            $.each(response, function (index, cityAdmin) {
                var html = this.getFilterHtml(cityAdmin);
                $('#filter-city-admin .results').append(html);
            }.bind(this));

            if (response.length == 0) {
                $('#filter-city-admin .results').html('<em>' + EALang.no_records_found + '</em>')
            }

            if (selectId != undefined) {
                this.select(selectId, display);
            }
        }.bind(this), 'json').fail(GeneralFunctions.ajaxFailureHandler);
    };

    /**
     * Get an city admin row html code that is going to be displayed on the filter results list.
     *
     * @param {Object} cityAdmin Contains the city admin record data.
     *
     * @return {String} The html code that represents the record on the filter results list.
     */
    CityAdminHelper.prototype.getFilterHtml = function (cityAdmin) {
        var name = cityAdmin.first_name + ' ' + cityAdmin.last_name;
        var info = cityAdmin.email;

        info = (cityAdmin.mobile_number != '' && cityAdmin.mobile_number != null)
            ? info + ', ' + cityAdmin.mobile_number : info;

        info = (cityAdmin.phone_number != '' && cityAdmin.phone_number != null)
            ? info + ', ' + cityAdmin.phone_number : info;

        var html =
            '<div class="city-admin-row entry" data-id="' + cityAdmin.id + '">' +
            '<strong>' + name + '</strong><br>' +
            info + '<br>' +
            '</div><hr>';

        return html;
    };

    /**
     * Select a specific record from the current filter results. If the city admin id does not exist
     * in the list then no record will be selected.
     *
     * @param {Number} id The record id to be selected from the filter results.
     * @param {Boolean} display Optional (false), if true the method will display the record in the form.
     */
    CityAdminHelper.prototype.select = function (id, display) {
        display = display || false;

        $('#filter-city-admin .selected').removeClass('selected');

        $('#filter-city-admin .city-admin-row').each(function () {
            if ($(this).attr('data-id') == id) {
                $(this).addClass('selected');
                return false;
            }
        });

        if (display) {
            $.each(this.filterResults, function (index, admin) {
                if (admin.id == id) {
                    this.display(admin);
                    $('#edit-city-admin, #delete-city-admin').prop('disabled', false);
                    return false;
                }
            }.bind(this));
        }
    };

    window.CityAdminHelper = CityAdminHelper;

})();
