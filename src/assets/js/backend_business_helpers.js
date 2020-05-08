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
     * BusinessHelper Class
     *
     * This class contains the methods that are used in the backend businesss page.
     *
     * @class BusinessHelper
     */
    function BusinessHelper() {
        this.filterResults = {};
    }

    /**
     * Binds the default event handlers of the backend businesss page.
     */
    BusinessHelper.prototype.bindEventHandlers = function () {
        var instance = this;

        /**
         * Event: Filter businesss Form "Submit"
         */
        $('#filter-business form').submit(function (event) {
            var key = $('#filter-business .key').val();
            $('.searchResults .selected').removeClass('selected');
            instance.resetForm();
            instance.filter(key);
            return false;
        });

        /**
         * Event: Filter businesss Clear Button "Click"
         */
        $('#filter-business .clear').click(function () {
            $('#filter-business .key').val('');
            instance.filter('');
            instance.resetForm();
        });

        /**
         * Event: Filter Result Item "Click"
         *
         * Display the business data of the selected row.
         */
        $(document).on('click', '.searchResults__item', function () {
            var businessHash = $(this).attr('data-hash');

            window.location.href = '/backend/business?hash=' + businessHash;
        });


        /**
         * Event: Cancel business edit Button "Click"
         */
        $("#edit-business-cancel").click(function() {
            window.location.href = '/';
        });
  };

    /**
     * Bring the business form back to its initial state.
     */
    BusinessHelper.prototype.resetForm = function () {
        $('#business-appointments').empty();
        $('.searchResults__items').empty();
        $('.searchResults').removeClass('searchResults--is-shown');
        $('.search').removeClass('search--is-shown');
        $('.searchResults .selected').removeClass('selected');
        $('#business-appointments').removeClass('searchResults__details');
    };

    /**
     * Filter business records.
     *
     * @param {String} key This key string is used to filter the business records.
     * @param {Number} selectId Optional, if set then after the filter operation the record with the given
     * ID will be selected (but not displayed).
     * @param {Boolean} display Optional (false), if true then the selected record will be displayed on the form.
     */
    BusinessHelper.prototype.filter = function (key, selectId, display) {
      if (key != '') {
        display = display || false;

        var postUrl = GlobalVariables.baseUrl + '/backend_api/ajax_filter_business';
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
            $.each(response, function (index, business) {
                var html = this.getFilterHtml(business);
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
     * @param {Object} business Contains the business data.
     *
     * @return {String} Returns the record HTML code.
     */
    BusinessHelper.prototype.getFilterHtml = function (business) {
        var html =
            '<div class="searchResults__item" data-hash="' + business.hash + '">' +
            '<strong>' +
            business.business_name +
            '</strong><br>' +
            business.address +
            '</div>';

        return html;
    };

    window.BusinessHelper = BusinessHelper;
})();
