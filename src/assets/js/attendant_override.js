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
     * Class AttendantOverride
     *
     * Contains the attendant override functionality.
     *
     * @class AttendantOverride
     */
    var AttendantOverride = function () {
        /**
         * This flag is used when trying to cancel row editing. It is
         * true only whenever the user presses the cancel button.
         *
         * @type {Boolean}
         */
        this.enableCancel = false;

        /**
         * This flag determines whether the jeditables are allowed to submit. It is
         * true only whenever the user presses the save button.
         *
         * @type {Boolean}
         */
        this.enableSubmit = false;
    };

    /**
     * Setup the dom elements of a given attendant override.
     *
     * @param {Object} attendantOverride Contains the attendant override for a given day.
     */
    AttendantOverride.prototype.setup = function (attendantOverride) {
        $.each(attendantOverride, function (index, override) {
            if (override != null) {

                var day = Date.parse(override.start_datetime).toString('MM/dd/yyyy');
                var start_time = Date.parse(override.start_datetime).toString('h:mm tt');
                var end_time = Date.parse(override.end_datetime).toString('h:mm tt');

                var tr = this.displayHTML(override.id, day, start_time, end_time, override.attendants_number);

                $('.attendant-override tbody').append(tr);
            } else {
                $('#' + index).prop('checked', false);
                $('#' + index + '-start').prop('disabled', true);
                $('#' + index + '-end').prop('disabled', true);
            }
        }.bind(this));

        // Make override cells editable.
        this.editableAttOverrideDay($('.attendant-override .override-day'));
        this.editableAttOverrideTime($('.attendant-override').find('.override-start, .override-end'));
        this.editableAttOverrideNumber($('.attendant-override').find('.override-number'));
    };

    /**
     * Enable editable override day.
     *
     * This method makes editable the override day cells.
     *
     * @param {Object} $selector The jquery selector ready for use.
     */
    AttendantOverride.prototype.editableAttOverrideDay = function ($selector) {
        $selector.editable(function (value, settings) {
            return value;
        }, {
            event: 'edit',
            height: '40px',
            width: '130px',
            submit: '<button type="button" class="hide submit-editable">Submits</button>',
            cancel: '<button type="button" class="hide cancel-editable">Cancel</button>',
            onblur: 'ignore',
            onreset: function (settings, td) {
                if (!this.enableCancel) {
                    return false; // disable ESC button
                }
            }.bind(this),
            onsubmit: function (settings, td) {
                if (!this.enableSubmit) {
                    return false; // disable Enter button
                }
            }.bind(this),
        });
    };

    /**
     * Enable editable override time.
     *
     * This method makes editable the override time cells.
     *
     * @param {Object} $selector The jquery selector ready for use.
     */
    AttendantOverride.prototype.editableAttOverrideTime = function ($selector) {
        $selector.editable(function (value, settings) {
            // Do not return the value because the user needs to press the "Save" button.
            return value;
        }, {
            event: 'edit',
            height: '40px',
            width: '100px',
            submit: '<button type="button" class="hide submit-editable">Submit</button>',
            cancel: '<button type="button" class="hide cancel-editable">Cancel</button>',
            onblur: 'ignore',
            onreset: function (settings, td) {
                if (!this.enableCancel) {
                    return false; // disable ESC button
                }
            }.bind(this),
            onsubmit: function (settings, td) {
                if (!this.enableSubmit) {
                    return false; // disable Enter button
                }
            }.bind(this)
        });
    };

    /**
     * Enable editable override time.
     *
     * This method makes editable the override time cells.
     *
     * @param {Object} $selector The jquery selector ready for use.
     */
    AttendantOverride.prototype.editableAttOverrideNumber = function ($selector) {
        $selector.editable(function (value, settings) {
            // Do not return the value because the user needs to press the "Save" button.
            return value;
        }, {
            event: 'edit',
            height: '40px',
            width: '100px',
            submit: '<button type="button" class="hide submit-editable">Submit</button>',
            cancel: '<button type="button" class="hide cancel-editable">Cancel</button>',
            onblur: 'ignore',
            onreset: function (settings, td) {
                if (!this.enableCancel) {
                    return false; // disable ESC button
                }
            }.bind(this),
            onsubmit: function (settings, td) {
                if (!this.enableSubmit) {
                    return false; // disable Enter button
                }
            }.bind(this)
        });
    };

    /**
     * Binds the event handlers for the attendant override dom elements.
     */
    AttendantOverride.prototype.bindEventHandlers = function () {
        var instance = this;

        /**
         * Event: Add Override Button "Click"
         *
         * A new row is added on the table and the user can enter the new override
         * data. After that he can either press the save or cancel button.
         */
        $('.add-override').click(function () {
            var tr = this.displayHTML('', 'MM/DD/YYYY', (GlobalVariables.timeFormat === 'regular' ? '9:00 AM' : '09:00'), (GlobalVariables.timeFormat === 'regular' ? '10:00 AM' : '10:00'), '');
            $('.attendant-override').prepend(tr);

            // Bind editable and event handlers.
            tr = $('.attendant-override tr')[1];
            this.editableAttOverrideDay($(tr).find('.override-day'));
            this.editableAttOverrideTime($(tr).find('.override-start, .override-end'));
            this.editableAttOverrideNumber($(tr).find('.override-number'));
            $(tr).find('.edit-override').trigger('click');
        }.bind(this));

        /**
         * Event: Edit Override Button "Click"
         *
         * Enables the row editing for the "attendant-override" table rows.
         */
        $(document).on('click', '.edit-override', function () {
            // Reset previous editable tds
            var $previousEdt = $(this).closest('table').find('.editable').get();
            $.each($previousEdt, function (index, editable) {
                if (editable.reset !== undefined) {
                    editable.reset();
                }
            });

            // Make all cells in current row editable.
            $(this).parent().parent().children().trigger('edit');
            $('.ui_tpicker_minute').addClass('ui_tpicker_unit_hide');
            $(this).parent().parent().find('.override-start input, .override-end input').timepicker({
                timeFormat: GlobalVariables.timeFormat === 'regular' ? 'h:00 TT' : 'HH',
                currentText: EALang.now,
                closeText: EALang.close,
                timeOnlyTitle: EALang.select_time,
                timeText: EALang.time,
                hourText: EALang.hour,
            });

            var dateToday = new Date();
            $(this).parent().parent().find('.override-day input').datepicker({
                minDate: dateToday,
                closeText: "Close",
                currentText: 'Now',
              });

            $(this).parent().parent().find('.override-day input').focus();

            // Show save - cancel buttons.
            $(this).closest('table').find('.edit-override, .delete-override').addClass('d-none');
            $(this).parent().find('.save-override, .cancel-override').removeClass('d-none');
            $(this).closest('tr').find('select,input:text').addClass('textInput input-sm required');

        });

        /**
         * Event: Delete Override Button "Click"
         *
         * Removes the current line from the "attendant-override" table.
         */
        $(document).on('click', '.delete-override', function (e) {
          var element = $(e.target);
          var id = element.closest('tr').data("id");
          var buttons = [
              {
                  text: EALang.delete,
                  click: function () {
                    instance.delete(id);

                    Backend.displayNotification("Capacity override deleted.");
                    element.parent().parent().remove();
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

          GeneralFunctions.displayMessageBox('Delete Override',
              'Are you sure you want to delete the attendant override?', buttons);
        });

        /**
         * Event: Cancel Override Button "Click"
         *
         * Bring the ".attendant-override" table back to its initial state.
         *
         * @param {jQuery.Event} e
         */
        $(document).on('click', '.cancel-override', function (e) {
            var element = e.target;
            var $modifiedRow = $(element).closest('tr');
            this.enableCancel = true;
            $modifiedRow.find('.cancel-editable').trigger('click');
            this.enableCancel = false;

            $(element).closest('table').find('.edit-override, .delete-override').removeClass('d-none');
            $modifiedRow.find('.save-override, .cancel-override').addClass('d-none');
            $('.add-override').prop('disabled', false);
        }.bind(this));

        /**
         * Event: Save Override Button "Click"
         *
         * Save the editable values and restore the table to its initial state.
         *
         * @param {jQuery.Event} e
         */
        $(document).on('click', '.save-override', function (e) {
            // Override's start time must always be prior to Override's end.
            var element = e.target,
                $modifiedRow = $(element).closest('tr'),
                start = Date.parse($modifiedRow.find('.override-start input').val()),
                end = Date.parse($modifiedRow.find('.override-end input').val()),
                id = $(e.target).closest('tr').data("id");

            if (start > end) {
                $modifiedRow.find('.override-end input').val(start.addHours(1).toString(GlobalVariables.timeFormat === 'regular' ? 'h:00 tt' : 'HH:mm'));
            }

            // Validate required fields.
            var missingRequiredField = false;
            $modifiedRow.find($('.required')).each(function () {
                if ($(this).val() == '') {
                    $(this).parents('form').addClass('has-error');
                    missingRequiredField = true;
                } else {
                  missingRequiredField = false;
                }
            });

            if (missingRequiredField) {
              GeneralFunctions.showSubmitError(true, 'Please fill out all required fields');
              return; // Validation failed, do not continue.
            } else {
              GeneralFunctions.showSubmitError(false);
            }

            var service_capacity = {
                "date": $modifiedRow.find('.override-day input').val(),
                "start_time": $modifiedRow.find('.override-start input').val(),
                "end_time": $modifiedRow.find('.override-end input').val(),
                "attendants_number": $modifiedRow.find('.override-number input').val(),
                "id_services": $('#service-id').val(),
                "id": id
            };

            this.enableSubmit = true;
            $modifiedRow.find('.editable .submit-editable').trigger('click');
            this.enableSubmit = false;
            $modifiedRow.find('.save-override, .cancel-override').addClass('d-none');
            $(element).closest('table').find('.edit-override, .delete-override').removeClass('d-none');

            instance.save(service_capacity, e);

            $('.add-override').prop('disabled', false);
        }.bind(this));

        /**
         * Event: Load data when Attendant Overrides "Click"
         */
        $('#services-page').on('click', '.display-overrides', function () {
            var id_services = $('#service-id').val();
            instance.resetList();

            if (id_services) {
                instance.get(id_services);

            } else {
                Backend.displayNotification("Please select service to update.", null, "danger");
            }
        });
    };


    /**
     * Save a service service capacity record from database.
     *
     * @param {Number} id Record ID to be deleted.
     */
    AttendantOverride.prototype.save = function (service_capacity, e) {
        var postUrl = GlobalVariables.baseUrl + '/backend_api/ajax_save_service_capacity';
        var postData = {
            csrfToken: GlobalVariables.csrfToken,
            service_capacity: service_capacity
        };
        var element = e.target,
            $modifiedRow = $(element).closest('tr');

        $.post(postUrl, postData, function (response) {
            if (!GeneralFunctions.handleAjaxExceptions(response)) {
                return;
            }

            $modifiedRow.attr('data-id', response.id)

            Backend.displayNotification("Capacity override saved.");
        }.bind(this), 'json').fail(GeneralFunctions.ajaxFailureHandler);
    };

    /**
     * Delete a service record from database.
     *
     * @param {Number} id Record ID to be deleted.
     */
    AttendantOverride.prototype.delete = function (id) {
        var postUrl = GlobalVariables.baseUrl + '/backend_api/ajax_delete_service_capacity';
        var postData = {
            csrfToken: GlobalVariables.csrfToken,
            id: id
        };

        $.post(postUrl, postData, function (response) {
            if (!GeneralFunctions.handleAjaxExceptions(response)) {
                return;
            }

            Backend.displayNotification("Capacity override deleted.");

            // remove it from dom here
        }.bind(this), 'json').fail(GeneralFunctions.ajaxFailureHandler);
    };

    /**
     * Filters service records depending a string key.
     *
     * @param {String} key This is used to filter the service records of the database.
     * @param {Number} selectId Optional, if set then after the filter operation the record with this
     * ID will be selected (but not displayed).
     * @param {Boolean} display Optional (false), if true then the selected record will be displayed on the form.
     */
    AttendantOverride.prototype.get = function (id_services) {
        var instance = this;
        var postUrl = GlobalVariables.baseUrl + '/backend_api/ajax_get_service_capacity';
        var postData = {
            csrfToken: GlobalVariables.csrfToken,
            id_services: id_services
        };

        $.post(postUrl, postData, function (response) {
            if (!GeneralFunctions.handleAjaxExceptions(response)) {
                return;
            }

            if (response.length >  0) {
                instance.setup(response);
            }
        }.bind(this), 'json').fail(GeneralFunctions.ajaxFailureHandler);
    };

    AttendantOverride.prototype.resetList = function () {
        $('table.attendant-override tbody').empty();
    };


    AttendantOverride.prototype.displayHTML = function (id, day, start_time, end_time, attendants_number) {
        var trHtml = (id ? '<tr class="tableRow" data-id="' + id + '">' : '<tr>');
        var html =
            trHtml +
            '<td class="override-day editable align-middle">' + day + '</td>' +
            '<td class="override-start editable align-middle">' + start_time + '</td>' +
            '<td class="override-end editable align-middle">' + end_time + '</td>' +
            '<td class="override-number editable align-middle">' + attendants_number + '</td>' +
            '<td>' +
            '<button type="button" class="btn btn-default btn-sm edit-override" title="' + EALang.edit + '">' +
            '<i class="far fa-edit pointerEventsNone"></i>' +
            '</button>' +
            '<button type="button" class="btn btn-default btn-sm delete-override" title="' + EALang.delete + '">' +
            '<i class="far fa-trash-alt pointerEventsNone"></i>' +
            '</button>' +
            '<button type="button" class="btn btn-default btn-sm save-override d-none" title="' + EALang.save + '">' +
            '<i class="far fa-check-circle pointerEventsNone"></i>' +
            '</button>' +
            '<button type="button" class="btn btn-default btn-sm cancel-override d-none" title="' + EALang.cancel + '">' +
            '<i class="fas fa-ban pointerEventsNone"></i>' +
            '</button>' +
            '</td>' +
            '</tr>';

        return html;
    };

    window.AttendantOverride = AttendantOverride;

})();
