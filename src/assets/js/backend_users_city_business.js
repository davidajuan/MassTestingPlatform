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
     * This class contains the City Business helper class declaration, along with the "City Busines Request Admins"
     * tab event handlers. By dividing the backend/users tab functionality into separate files
     * it is easier to maintain the code.
     *
     * @class CityBusinessHelper
     */
    var CityBusinessHelper = function () {
        this.filterResults = {}; // Store the results for later use.
    };

    /**
     * Bind the event handlers for the backend/users "City Busines Request Admins" tab.
     */
    CityBusinessHelper.prototype.bindEventHandlers = function () {
        /**
         * Event: Filter City Business Form "Submit"
         *
         * Filter the city business records with the given key string.
         */
        $('#city-business').on('submit', '#filter-city-business form', function () {
            var key = $('#filter-city-business .key').val();
            $('#filter-city-business .selected').removeClass('selected');
            this.resetForm();
            this.filter(key);
            return false;
        }.bind(this));

        /**
         * Event: Clear Filter Results Button "Click"
         */
        $('#city-business').on('click', '#filter-city-business .clear', function () {
            this.filter('');
            $('#filter-city-business .key').val('');
            this.resetForm();
        }.bind(this));

        /**
         * Event: Filter City Business Row "Click"
         *
         * Display the selected city business data to the user.
         */
        $('#city-admin').on('click', '.city-business-row', function (e) {
            if ($('#filter-city-admin .filter').prop('disabled')) {
                $('#filter-city-admin .results').css('color', '#AAA');
                return; // exit because we are currently on edit mode
            }

            var cityBusinessId = $(e.currentTarget).attr('data-id');
            var cityBusiness = {};

            $.each(this.filterResults, function (index, item) {
                if (item.id === cityBusinessId) {
                  cityBusiness = item;
                    return false;
                }
            });

            this.display(cityBusiness);

            $('#filter-city-admin .selected').removeClass('selected');
            $(e.currentTarget).addClass('selected');
            $('#edit-city-admin, #delete-city-admin').prop('disabled', false);
        }.bind(this));

        /**
         * Event: Add New City Business Button "Click"
         */
        $('#city-business').on('click', '#add-city-business', function () {
          console.log('hi')
            this.resetForm();
            $('#filter-city-business button').prop('disabled', true);
            $('#filter-city-business .results').css('color', '#AAA');

            $('#city-business .add-edit-delete-group').hide();
            $('#city-business .save-cancel-group').show();
            $('#city-business .record-details').find('input, textarea').prop('readonly', false);
            $('#city-business .record-details').find('select').prop('disabled', false);
            $('#city-business-password, #city-business-password-confirm').addClass('required');
            $('#city-business-notifications').prop('disabled', false);
            $('#city-business-providers input:checkbox').prop('disabled', false);
        }.bind(this));

        /**
         * Event: Edit City Business Button "Click"
         */
        $('#city-business').on('click', '#edit-city-business', function () {
            $('#filter-city-business button').prop('disabled', true);
            $('#filter-city-business .results').css('color', '#AAA');
            $('#city-business .add-edit-delete-group').hide();
            $('#city-business .save-cancel-group').show();
            $('#city-business .record-details').find('input, textarea').prop('readonly', false);
            $('#city-business .record-details').find('select').prop('disabled', false);
            $('#city-business-password, #city-business-password-confirm').removeClass('required');
            $('#city-business-notifications').prop('disabled', false);
            $('#city-business-providers input:checkbox').prop('disabled', false);
        });

        /**
         * Event: Delete City Business Button "Click"
         */
        $('#city-business').on('click', '#delete-city-business', function () {
            var cityBusinessId = $('#city-business-id').val();
            var buttons = [
                {
                    text: EALang.delete,
                    click: function () {
                        this.delete(cityBusinessId);
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

            GeneralFunctions.displayMessageBox(EALang.delete_city_business_request_admin,
                EALang.delete_record_prompt, buttons);
        }.bind(this));

        /**
         * Event: Save City Business Button "Click"
         */
        $('#city-business').on('click', '#save-city-business', function () {
            var cityBusiness = {
                first_name: $('#city-business-first-name').val(),
                last_name: $('#city-business-last-name').val(),
                email: $('#city-business-email').val(),
                mobile_number: $('#city-business-mobile-number').val(),
                phone_number: $('#city-business-phone-number').val(),
                address: $('#city-business-address').val(),
                city: $('#city-business-city').val(),
                state: $('#city-business-state').val(),
                zip_code: $('#city-business-zip-code').val(),
                notes: $('#city-business-notes').val(),
                settings: {
                    username: $('#city-business-username').val(),
                    notifications: $('#city-business-notifications').hasClass('active'),
                    calendar_view: $('#city-business-calendar-view').val()
                }
            };

            // Include city business services.
            cityBusiness.providers = [];
            $('#city-business-providers input:checkbox').each(function () {
                if ($(this).prop('checked')) {
                  cityBusiness.providers.push($(this).attr('data-id'));
                }
            });

            // Include password if changed.
            if ($('#city-business-password').val() !== '') {
              cityBusiness.settings.password = $('#city-business-password').val();
            }

            // Include ID if changed.
            if ($('#city-business-id').val() !== '') {
              cityBusiness.id = $('#city-business-id').val();
            }

            if (!this.validate()) {
                return;
            }

            this.save(cityBusiness);
        }.bind(this));

        /**
         * Event: Cancel City Business Button "Click"
         *
         * Cancel add or edit of an city business record.
         */
        $('#city-business').on('click', '#cancel-city-business', function () {
            var id = $('#city-business-id').val();
            this.resetForm();
            if (id != '') {
                this.select(id, true);
            }
        }.bind(this));
    };

    /**
     * Save city business record to database.
     *
     * @param {Object} cityBusiness Contains the admin record data. If an 'id' value is provided
     * then the update operation is going to be executed.
     */
    CityBusinessHelper.prototype.save = function (cityBusiness) {
        var postUrl = GlobalVariables.baseUrl + '/backend_api/ajax_save_city_business';
        var postData = {
            csrfToken: GlobalVariables.csrfToken,
            citybusiness: JSON.stringify(cityBusiness)
        };

        $.post(postUrl, postData, function (response) {
            if (!GeneralFunctions.handleAjaxExceptions(response)) {
                return;
            }
            Backend.displayNotification(EALang.city_business_request_admin_saved);
            this.resetForm();
            $('#filter-city-business .key').val('');
            this.filter('', response.id, true);
        }.bind(this), 'json').fail(GeneralFunctions.ajaxFailureHandler);
    };

    /**
     * Delete a city business record from database.
     *
     * @param {Number} id Record id to be deleted.
     */
    CityBusinessHelper.prototype.delete = function (id) {
        var postUrl = GlobalVariables.baseUrl + '/backend_api/ajax_delete_city_business';
        var postData = {
            csrfToken: GlobalVariables.csrfToken,
            citybusiness_id: id
        };

        $.post(postUrl, postData, function (response) {
            if (!GeneralFunctions.handleAjaxExceptions(response)) {
                return;
            }
            Backend.displayNotification(EALang.delete_city_business_request_admin);
            this.resetForm();
            this.filter($('#filter-city-business .key').val());
        }.bind(this), 'json').fail(GeneralFunctions.ajaxFailureHandler);
    };

    /**
     * Validates a city business record.
     *
     * @return {Boolean} Returns the validation result.
     */
    CityBusinessHelper.prototype.validate = function () {
        $('#city-business .has-error').removeClass('has-error');
        $('#city-business .form-message').removeClass('alert-danger');

        try {
            // Validate required fields.
            var missingRequired = false;
            $('#city-business .required').each(function () {
                if ($(this).val() == '' || $(this).val() == undefined) {
                    $(this).closest('.formGroup').addClass('has-error');
                    missingRequired = true;
                }
            });
            if (missingRequired) {
                throw 'Fields with * are  required.';
            }

            // Validate passwords.
            if ($('#city-business-password').val() != $('#city-business-password-confirm').val()) {
                $('#city-business-password, #city-business-password-confirm').closest('.formGroup').addClass('has-error');
                throw 'Passwords mismatch!';
            }

            if ($('#city-business-password').val().length < BackendUsers.MIN_PASSWORD_LENGTH
                && $('#city-business-password').val() != '') {
                $('#city-business-password, #city-business-password-confirm').closest('.formGroup').addClass('has-error');
                throw 'Password must be at least ' + BackendUsers.MIN_PASSWORD_LENGTH
                + ' characters long.';
            }

            // Validate user email.
            if (!GeneralFunctions.validateEmail($('#city-business-email').val())) {
                $('#city-business-email').closest('.formGroup').addClass('has-error');
                throw 'Invalid email address!';
            }

            // Check if username exists
            if ($('#city-business-username').attr('already-exists') == 'true') {
                $('#city-business-username').closest('.formGroup').addClass('has-error');
                throw 'Username already exists.';
            }

            return true;
        } catch (message) {
            $('#city-business .form-message')
                .addClass('alert-danger')
                .text(message)
                .show();
            return false;
        }
    };

    /**
     * Resets the admin tab form back to its initial state.
     */
    CityBusinessHelper.prototype.resetForm = function () {
        $('#city-business .record-details').find('input, textarea').val('');
        $('#city-business .add-edit-delete-group').show();
        $('#city-business .save-cancel-group').hide();
        $('#edit-city-business, #delete-city-business').prop('disabled', true);
        $('#city-business .record-details').find('input, textarea').prop('readonly', true);
        $('#city-business .record-details').find('select').prop('disabled', true);
        $('#city-business .form-message').hide();
        $('#city-business-notifications').removeClass('active');
        $('#city-business-notifications').prop('disabled', true);
        $('#city-business-providers input:checkbox').prop('checked', false);
        $('#city-business-providers input:checkbox').prop('disabled', true);
        $('#city-business .has-error').removeClass('has-error');

        $('#filter-city-business .selected').removeClass('selected');
        $('#filter-city-business button').prop('disabled', false);
        $('#filter-city-business .results').css('color', '');
    };

    /**
     * Display a city business record into the admin form.
     *
     * @param {Object} cityBusines Contains the city business record data.
     */
    CityBusinessHelper.prototype.display = function (cityBusines) {
        $('#city-business-id').val(cityBusines.id);
        $('#city-business-first-name').val(cityBusines.first_name);
        $('#city-business-last-name').val(cityBusines.last_name);
        $('#city-business-email').val(cityBusines.email);
        $('#city-business-mobile-number').val(cityBusines.mobile_number);
        $('#city-business-phone-number').val(cityBusines.phone_number);
        $('#city-business-address').val(cityBusines.address);
        $('#city-business-city').val(cityBusines.city);
        $('#city-business-state').val(cityBusines.state);
        $('#city-business-zip-code').val(cityBusines.zip_code);
        $('#city-business-notes').val(cityBusines.notes);

        $('#city-business-username').val(cityBusines.settings.username);
        $('#city-business-calendar-view').val(cityBusines.settings.calendar_view);
        if (cityBusines.settings.notifications == true) {
            $('#city-business-notifications').addClass('active');
        } else {
            $('#city-business-notifications').removeClass('active');
        }

        $('#city-business-providers input:checkbox').prop('checked', false);
        $.each(cityBusines.providers, function (index, providerId) {
            $('#city-business-providers input:checkbox').each(function () {
                if ($(this).attr('data-id') == providerId) {
                    $(this).prop('checked', true);
                }
            });
        });
    };

    /**
     * Filters city business records depending a string key.
     *
     * @param {String} key This is used to filter the city business records of the database.
     * @param {Numeric} selectId Optional, if provided the given ID will be selected in the filter results
     * (only selected, not displayed).
     * @param {Bool} display Optional (false).
     */
    CityBusinessHelper.prototype.filter = function (key, selectId, display) {
        display = display || false;

        var postUrl = GlobalVariables.baseUrl + '/backend_api/ajax_filter_city_business';
        var postData = {
            csrfToken: GlobalVariables.csrfToken,
            key: key
        };

        $.post(postUrl, postData, function (response) {
            if (!GeneralFunctions.handleAjaxExceptions(response)) {
                return;
            }

            this.filterResults = response;

            $('#filter-city-business .results').html('');
            $.each(response, function (index, cityBusines) {
                var html = this.getFilterHtml(cityBusines);
                $('#filter-city-business .results').append(html);
            }.bind(this));

            if (response.length == 0) {
                $('#filter-city-business .results').html('<em>' + EALang.no_records_found + '</em>')
            }

            if (selectId != undefined) {
                this.select(selectId, display);
            }
        }.bind(this), 'json').fail(GeneralFunctions.ajaxFailureHandler);
    };

    /**
     * Get an city business row html code that is going to be displayed on the filter results list.
     *
     * @param {Object} cityBusines Contains the city business record data.
     *
     * @return {String} The html code that represents the record on the filter results list.
     */
    CityBusinessHelper.prototype.getFilterHtml = function (cityBusines) {
        var name = cityBusines.first_name + ' ' + cityBusines.last_name;
        var info = cityBusines.email;

        info = (cityBusines.mobile_number != '' && cityBusines.mobile_number != null)
            ? info + ', ' + cityBusines.mobile_number : info;

        info = (cityBusines.phone_number != '' && cityBusines.phone_number != null)
            ? info + ', ' + cityBusines.phone_number : info;

        var html =
            '<div class="city-business-row entry" data-id="' + cityBusines.id + '">' +
            '<strong>' + name + '</strong><br>' +
            info + '<br>' +
            '</div><hr>';

        return html;
    };

    /**
     * Select a specific record from the current filter results. If the city business id does not exist
     * in the list then no record will be selected.
     *
     * @param {Number} id The record id to be selected from the filter results.
     * @param {Boolean} display Optional (false), if true the method will display the record in the form.
     */
    CityBusinessHelper.prototype.select = function (id, display) {
        display = display || false;

        $('#filter-city-business .selected').removeClass('selected');

        $('#filter-city-business .city-business-row').each(function () {
            if ($(this).attr('data-id') == id) {
                $(this).addClass('selected');
                return false;
            }
        });

        if (display) {
            $.each(this.filterResults, function (index, admin) {
                if (admin.id == id) {
                    this.display(admin);
                    $('#edit-city-business, #delete-city-business').prop('disabled', false);
                    return false;
                }
            }.bind(this));
        }
    };

    window.CityBusinessHelper = CityBusinessHelper;

})();
