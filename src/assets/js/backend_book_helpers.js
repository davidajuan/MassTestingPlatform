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
            $('.searchResults .selected').removeClass('selected');
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
         * Event: Filter Result Item "Click"
         *
         * Display the customer data of the selected row.
         */
        $(document).on('click', '.searchResults__item', function () {
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
            $('.searchResults .selected').removeClass('selected');
            $(this).addClass('selected');
            $('#edit-customer, #delete-customer').prop('disabled', false);
        });

        /**
         * Event: Edit Customer Button "Click"
         */
        $("body").on("click", "#edit-customer", function() {
            var appointment_hash = $(this).data('hash');
            if (appointment_hash) {
                window.location.href = '/backend?appointment_hash=' + appointment_hash;
            }
        });


        $("body").on("click", "#edit-new-appointment", function() {
            var patient_id = $(this).data('id');
            if (patient_id) {
                window.location.href = '/backend?patient_id=' + patient_id;
            }
        });


        /**
         * Event: Cancel Customer edit Button "Click"
         */
        $("#edit-customer-cancel").click(function() {
            window.location.href = '/';
        });
  };

    /**
     * Bring the customer form back to its initial state.
     */
    CustomersHelper.prototype.resetForm = function () {
        $('#customer-appointments').empty();
        $('.searchResults__items').empty();
        $('.searchResults').removeClass('searchResults--is-shown');
        $('.search').removeClass('search--is-shown');
        $('.searchResults .selected').removeClass('selected');
        $('#customer-appointments').removeClass('searchResults__details');
    };

    /**
     * Display a customer record into the form.
     *
     * @param {Object} customer Contains the customer record data.
     */
    CustomersHelper.prototype.display = function (customer) {
        $('#customer-appointments').empty();
        $('#customer-appointments').append('<h3 class="h5 searchResults__detailsHeading">Appointment Details</h3>');
        $.each(customer.appointments, function (index, appointment) {
            if (GlobalVariables.user.role_slug === Backend.DB_SLUG_PROVIDER && parseInt(appointment.id_users_provider) !== GlobalVariables.user.id) {
                return true; // continue
            }

            if (GlobalVariables.user.role_slug === Backend.DB_SLUG_SECRETARY && GlobalVariables.secretaryProviders.indexOf(appointment.id_users_provider) === -1) {
                return true; // continue
            }

            $('#customer-appointments').addClass('searchResults__details');
            var start = GeneralFunctions.formatDate(Date.parse(appointment.start_datetime), 'YMD', true);

            var html =
                '<div class="searchResults__detail" data-id="' + appointment.id + '">' +
                '<strong>' + appointment.service.name + '</strong>' +
                '<p class="mb-0">' + appointment.provider.first_name + ' ' + appointment.provider.last_name + '</p>' +
                '<p class="mb-0" id="label_start_datetime">' + start + '</p>' +
                '<p class="mb-0" id="label_hash">Barcode: <span class="font-weight-bold">' + appointment.hash + '</span></p>' +
                '<button id="edit-customer" data-hash="' + appointment.hash + '" class="btn btn--simple px-0 ml-1"> ' +
                '<i class="far fa-edit"></i> Edit Info/Appointment</button>' +
                '</div>';
            $('#customer-appointments').append(html);
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
      if (key != '') {
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

            $('.searchResults').addClass('searchResults--is-shown');
            $('.search').addClass('search--is-shown');
            $('.searchResults__items').html('');
            $.each(response, function (index, customer) {
                var html = this.getFilterHtml(customer);
                $('.searchResults__items').append(html);
            }.bind(this));
            if (response.length == 0) {
                $('.searchResults__items').html('<em>' + EALang.no_records_found + '</em>');
            }

            if (selectId != undefined) {
                this.select(selectId, display);
            }

        }.bind(this), 'json').fail(GeneralFunctions.ajaxFailureHandler);
      }

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
        var info = new Date(customer.dob).toLocaleDateString("en-US");
        info = (customer.mobile_number != '' && customer.mobile_number != null)
            ? info + ', ' + customer.mobile_number : info;

        var html =
            '<div class="searchResults__item" data-id="' + customer.id + '">' +
            '<strong>' +
            name +
            '</strong><br>' +
            info +
            '<div>' +
            '<button id="edit-new-appointment" data-id="' + customer.patient_id + '" class="btn btn--simple px-0 pb-0"> ' +
            '<i class="far fa-calendar-plus mr-2"></i>Add new appointment</button>' +
            '</div>' +
            '</div>';

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

        $('.searchResults .selected').removeClass('selected');

        $('.searchResults .searchResults__item').each(function () {
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

    window.CustomersHelper = CustomersHelper;
})();
