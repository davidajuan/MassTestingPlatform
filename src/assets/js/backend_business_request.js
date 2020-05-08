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

window.BackendBusinessRequest = window.BackendBusinessRequest || {};

/**
 * Backend Business Request
 *
 * @module BackendBusinessRequest
 */
(function (exports) {
    'use strict';

    // Delete business request
    let deleteBusRequest = $('.table__body td').find('[data-id="delete-business-request"]');
    let deleteRequestConfirm = $('#confirm-delete-request');
    let deleteRequestModal = $('#delete-request-confirmation-modal');

    deleteBusRequest.on('click', function () {
      deleteRequestModal.modal('toggle');
      let business_name = $(this).data('business-name');
      let business_code = $(this).data('business-code');
      $('#delete-request-business-name').html(business_name);
      $('#delete-request-business-code').val(business_code);
    });

    // Delete request if delete confirm is clicked
    deleteRequestConfirm.on('click', () => {
      let business_code = $('#delete-request-business-code').val();
      let business_name = $('#approve-request-business-name').html();

      BackendBusinessRequestApi.changeBusinessRequestStatus(business_code, 0, 'deleted', business_name);
    });

    // Approve business request
    let approveBusRequest = $('.table__body td').find('[data-id="approve-business-request"]');
    let approveRequestConfirm = $('#confirm-approve-request');
    let approveRequestModal = $('#approve-request-confirmation-modal');

    approveBusRequest.on('click', function () {
      let business_name = $(this).data('business-name');
      let business_code = $(this).data('business-code');
      let approvedBusField = $('#approved-' + business_code);
      let priority_service = $('#priority-service-' + business_code).prop('checked') ? '1' : '0';
      let slots_approved = approvedBusField.val().trim();

      // errors
      if (slots_approved == '' || slots_approved == 0) {
        approvedBusField.parent('td').addClass('has-error');
      } else { // good to send
        approveRequestModal.modal('toggle');

        $('#approve-request-business-name').html(business_name);
        $('#approve-request-slots-approved').html(slots_approved);
        $('#approve-request-business-code').val(business_code);
        $('#approve-priority-service').val(priority_service);
      }
    });

    // Approve request if approve confirm is clicked
    approveRequestConfirm.on('click', () => {
      let business_code = $('#approve-request-business-code').val();
      let business_name = $('#approve-request-business-name').html();
      let slots_approved = $('#approve-request-slots-approved').html();
      let priority_service = $('#approve-priority-service').val();

      BackendBusinessRequestApi.changeBusinessRequestStatus(business_code, slots_approved, 'active', business_name, priority_service);
    });

    // Table row selection
    let tableBodyRow = $('.table__body tr');
    tableBodyRow.click(function() {
        // Add active class to row
        tableBodyRow.removeClass('is-active');
        $(this).addClass('is-active');

        // Grab row id
        var id = $(this).data('row');
        // Grab html of card with correct data
        var card = $('#card-' + id).html();
        // Insert that html
        $('#details').html(card);
    });

    // On load, click the first row
    $(function() {
        $('[data-row="0"]').click();
    });

    // Search for business name
    let businessSearch = $('#business-request-search');
    let searchInput = $('.search__input');
    businessSearch.submit(function(e) {
      let searchValue = searchInput.val();
      let query_params = GeneralFunctions.updateQueryStringParameter(window.location.search, 'query', searchValue);
      // clear what page you're on
      query_params = GeneralFunctions.removeURLParameter(query_params, 'page');

      window.location = '/backend/business_request'+ query_params;
      e.preventDefault();
    });

    // Clear business search
    let clearSearch = $('#clear-filter');
    clearSearch.on('click', function() {
      // clear what page you're on
      let query_params = GeneralFunctions.removeURLParameter(window.location.search, 'query');
      window.location = '/backend/business_request' + query_params;
    });

    // change status filter
    let statusFilter = $('#status-filter .dropdown-item');
    statusFilter.on('click', function(e) {
      e.preventDefault();

      let selectedStatus = $(this).data('status');
      let query_params;

      if (selectedStatus === 'clear') {
        query_params = GeneralFunctions.removeURLParameter(window.location.search, 'status');
      } else {
        query_params = GeneralFunctions.updateQueryStringParameter(window.location.search, 'status', selectedStatus)
      }

      // clear what page you're on
      query_params = GeneralFunctions.removeURLParameter(query_params, 'page');

      window.location = '/backend/business_request' + query_params;
    });

    // Grab value of the request status
    let requestStatus = $('input[name="requestStatus').val();
    let filterText = $('[data-filter="text"]');
    // Add status to dropdown filter text
    if (requestStatus != '') {
      filterText.text(requestStatus.charAt(0).toUpperCase() + requestStatus.slice(1));
    } else {
        filterText.text('Filter By Status');
    }

    // If request status is deleted, show Denied on the frontend
    if (requestStatus == 'deleted') {
      filterText.text('Denied')
    }
})(window.BackendBusinessRequest);
