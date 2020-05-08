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

window.BackendReports = window.BackendReports || {};

/**
 * Backend Reports
 *
 * This module contains functions that implement the reports page functionality. Once the
 * initialize() method is called the page is fully functional and can serve the reports creation
 * process.
 *
 * @module BackendReports
 */
(function (exports) {

    'use strict';

    /**
     * The page helper contains methods that implement each record type functionality
     * (for now there is only the ReportsHelper).
     *
     * @type {Object}
     */
    var helper = {};

    /**
     * This method initializes the reports page.
     *
     * @param {Boolean} bindEventHandlers (OPTIONAL) Determines whether the default
     * event handlers will be bound to the dom elements.
     */
    exports.initialize = function (bindEventHandlers, defaultEventHandlers) {
        bindEventHandlers = bindEventHandlers || true;
        defaultEventHandlers = defaultEventHandlers || true;

        if (defaultEventHandlers) {
            _bindEventHandlers();
        }

        if (window.console === undefined) {
            window.console = function () {
            }; // IE compatibility
        }

    };

    /**
     * This method binds the necessary event handlers for the reports page.
     */
    function _bindEventHandlers() {
        $('#gen-appointment-count-report').click(function () {
            var dateStart = GeneralFunctions.formatDate($('#appointment-counts-start-date').val(), 'YMD');
            var dateEnd = GeneralFunctions.formatDate($('#appointment-counts-end-date').val(), 'YMD');
            // TODO: Assuming first provider at the moment
            var providerId = GlobalVariables.availableProviders[0].id;

            var url = GlobalVariables.baseUrl + '/report?name=appointmentCounts&dateStart=' + dateStart + '&dateEnd=' + dateEnd + '&providerId=' + providerId;
            document.location = url;
        });

        $('#gen-businesses-report').click(function () {
            var dateStart = GeneralFunctions.formatDate($('#businesses-start-date').val(), 'YMD');
            var dateEnd = GeneralFunctions.formatDate($('#businesses-counts-end-date').val(), 'YMD');
            var status = $('#businesses-status').val();

            var url = GlobalVariables.baseUrl + '/report?name=businessesRequested&dateStart=' + dateStart + '&dateEnd=' + dateEnd + '&status=' + status;
            document.location = url;
        });

        $('#gen-city-employee-report').click(function () {
            var dateStart = GeneralFunctions.formatDate($('#city-employee-start-date').val(), 'YMD');
            var dateEnd = GeneralFunctions.formatDate($('#city-employee-end-date').val(), 'YMD');

            var url = GlobalVariables.baseUrl + '/report?name=appointmentListCod&dateStart=' + dateStart + '&dateEnd=' + dateEnd;
            document.location = url;
        });
    };


})(window.BackendReports);
