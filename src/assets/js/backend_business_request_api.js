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

window.BackendBusinessRequestApi = window.BackendBusinessRequestApi || {};

/**
 * Frontend Book API
 *
 * This module serves as the API consumer for the booking wizard of the app.
 *
 * @module BackendBusinessRequestApi
 */
(function (exports) {

    'use strict';

    /**
     * Approve a business request
     */
    exports.changeBusinessRequestStatus = function (business_code, slots_approved, status, business_name, priority_service) {
        var postData = {
            csrfToken: GlobalVariables.csrfToken,
            business_code: business_code,
            slots_approved: slots_approved,
            status: status,
            business_name: business_name,
            priority_service: priority_service
        };

        var postUrl = GlobalVariables.baseUrl + '/backend_api/ajax_business_request_status_change';
        var $layer = $('<div/>');
        var $modalSpinner = $('.modalSpinner');

        $.ajax({
            url: postUrl,
            method: 'post',
            data: postData,
            dataType: 'json',
            beforeSend: function (jqxhr, settings) {
                $layer
                    .appendTo('body')
                    .css({
                        background: 'white',
                        position: 'fixed',
                        top: '0',
                        left: '0',
                        height: '100vh',
                        width: '100vw',
                        opacity: '0.5'
                    });
                // Show modal spinner
                $modalSpinner.removeClass('hide');
            }
        })
        .done(function (response) {
            if (!GeneralFunctions.handleAjaxExceptions(response)) {
                Backend.displayNotification('issues', null, 'danger');
                return false;
            }

            var query_params = GeneralFunctions.updateQueryStringParameter(window.location.search, 'status', response.status)

            window.location.href = GlobalVariables.baseUrl
                + '/backend/business_request' + query_params;
        })
        .fail(function (jqxhr, textStatus, errorThrown) {
            GeneralFunctions.ajaxFailureHandler(jqxhr, textStatus, errorThrown);
        })
        .always(function () {
            $layer.remove();
            $modalSpinner.addClass('hide');
        });
    };


})(window.BackendBusinessRequestApi);
