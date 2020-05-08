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

window.BackendBookApi = window.BackendBookApi || {};

/**
 * Frontend Book API
 *
 * This module serves as the API consumer for the booking wizard of the app.
 *
 * @module BackendBookApi
 */
(function (exports) {

    'use strict';

    var unavailableDatesBackup;
    var selectedDateStringBackup;
    var processingUnavailabilities = false;

    /**
     * Get Available Hours
     *
     * This function makes an AJAX call and returns the available hours for the selected service,
     * provider and date.
     *
     * @param {String} selDate The selected date of which the available hours we need to receive.
     */
    exports.getAvailableHours = function (selDate) {
        $('#available-hours').empty();

        // Find the selected service duration (it is going to be send within the "postData" object).
        var selServiceDuration = 60; // Default value of duration (in minutes).
        $.each(GlobalVariables.availableServices, function (index, service) {
            if (service.id == $('#select-service').val()) {
                selServiceDuration = service.duration;
            }
        });

        // If the manage mode is true then the appointment's start date should return as available too.
        var appointmentId = BackendBook.manageMode ? GlobalVariables.appointmentData.id : undefined;

        // Make ajax post request and get the available hours.
        var postUrl = GlobalVariables.baseUrl + '/appointments/ajax_get_available_hours';
        var postData = {
            csrfToken: GlobalVariables.csrfToken,
            service_id: $('#select-service').val(),
            provider_id: $('#select-provider').val(),
            selected_date: selDate,
            service_duration: selServiceDuration,
            manage_mode: BackendBook.manageMode,
            appointment_id: appointmentId
        };

        $.post(postUrl, postData, function (response) {
            if (!GeneralFunctions.handleAjaxExceptions(response)) {
                return;
            }
            var timeFormat = GlobalVariables.timeFormat === 'regular' ? 'h:mm tt' : 'HH:mm';

            // The response contains the available hours for the selected provider and
            // service. Fill the available hours div with response data.
            if (response.length > 0) {
                var currColumn = 1;
                $('#available-hours').html('<div class="bookHours__item"></div>');

                $.each(response, function (index, availableHour) {
                    if ((currColumn * 10) < (index + 1)) {
                        currColumn++;
                        $('#available-hours').append('<div class="bookHours__item""></div>');
                    }

                    var slotId = availableHour.available_hours.replace(':', '');

                    $('#available-hours div:eq(' + (currColumn - 1) + ')').append(
                        '<div id="time-slots-wrapper-' + slotId + '">' +
                        '<span class="available-hour">' +
                        Date.parse(availableHour.available_hours).toString(timeFormat) +
                        '</span><span class="time-slots-remaining" id="time-slots-remaining-' + slotId + '">(<span id="avail_per_slot_' + slotId + '">' + availableHour.avail_per_slot + '</span>)</span>' +
                        '</div>');
                });

                if (BackendBook.manageMode) {
                    // Set the appointment's start time as the default selection.
                    $('.available-hour').removeClass('selected-hour');
                    $('.available-hour').filter(function () {
                        return $(this).text() === Date.parseExact(
                            GlobalVariables.appointmentData.start_datetime,
                            'yyyy-MM-dd HH:mm:ss').toString(timeFormat);
                    }).addClass('selected-hour');
                } else {
                    // Set the first available hour as the default selection.
                    $('.available-hour:eq(0)').addClass('selected-hour');
                }

                BackendBook.updateConfirmFrame();

            } else {
                $('#available-hours').text(EALang.no_available_hours);

                if (BackendBook.manageMode) {
                    var hour = Date.parse($('#original-select-start-date').val()).toString(timeFormat)
                    $('#available-hours').append('<span class="d-none selected-hour">' + hour + '</span>');
                }
            }
        }, 'json').fail(GeneralFunctions.ajaxFailureHandler);
    };

    exports.getMultipleAttendantsHours = function (selDate) {
        // Make ajax post request and get the available hours.
        var postUrl = GlobalVariables.baseUrl + '/appointments/ajax_get_multiple_attendants_hours';
        var postData = {
            csrfToken: GlobalVariables.csrfToken,
            service_id: $('#select-service').val(),
            provider_id: $('#select-provider').val(),
            selected_date: selDate,
        };

        $.post(postUrl, postData, function (response) {
            if (!GeneralFunctions.handleAjaxExceptions(response)) {
                return;
            }
            if (response.length > 0) {
                // update the available slot numbers
                var actualTimes = [];
                response.forEach(function(hour) {
                    if (typeof hour !== 'undefined') {
                        var newAvailPerSlot = hour.avail_per_slot
                        var slotParsedId = hour.available_hours.replace(':', '')
                        var slotId = 'avail_per_slot_' + slotParsedId;
                        var timeSlotsId = 'time-slots-remaining-' + slotParsedId;
                        actualTimes.push(slotParsedId);

                        // if available spots decreased
                        if ($('#'+slotId).html() > newAvailPerSlot) {
                            $('#'+slotId).html(newAvailPerSlot)
                            $('#'+timeSlotsId).addClass('hoursAvailableSpots--warning');

                            setTimeout(function(){
                              $('#'+timeSlotsId).css({"background-color": "#fff", "color": "#808080" });

                              setTimeout(function(){
                                  $('#'+timeSlotsId).removeClass('hoursAvailableSpots--warning');
                              }, 2000);
                            }, 2000);
                        }

                        // if available spots increased (rare)
                        if ($('#'+slotId).html() < newAvailPerSlot) {
                            $('#'+slotId).html(newAvailPerSlot)
                            $('#'+timeSlotsId).animate({backgroundColor: '#279989'}, 'slow');
                            setTimeout(function(){
                                $('#'+timeSlotsId).css({"background-color": "#fff", "color": "#808080" });
                            }, 2000);
                        }
                    }
                })

                // check if timeslots still matches from backend,
                // if not click refresh it means the availablity was removed from backend
                //this is a quick fix for if all slots get taken
                var possibleTimes = [
                    '0700', '0800', '0900', '1000', '1100', '1200',
                    '1300', '1400', '1500', '1600', '1700', '1800'
                ]

                possibleTimes.forEach(function (hourId) {
                    if (!actualTimes.includes(hourId)) {
                        $('#time-slots-wrapper-' + hourId).hide();
                    }
                });

                return true;
            } else {
            }
        }, 'json').fail(GeneralFunctions.ajaxFailureHandler);

    }

    exports.getAvailableAppointmentsLongRange = function (selDate) {
        // Make ajax post request and get the available hours.
        var postUrl = GlobalVariables.baseUrl + '/appointments/get_available_appointments_longrange';
        var postData = {
            csrfToken: GlobalVariables.csrfToken,
            service_id: $('#select-service').val(),
            provider_id: $('#select-provider').val(),
            selected_date: selDate,
        };

        $.post(postUrl, postData, function (response) {
            if (!GeneralFunctions.handleAjaxExceptions(response)) {
                return;
            }

            if (response.appointments_remaining != '') {
                // only show div after we get some data back
                $('#appointments_remaining_wrapper').show();
                $('#appointments_remaining').addClass('colorGreen');

                $('#appointments_remaining').html(response.appointments_remaining);
                $('#appointments_remaining_days').html(response.days);

                // If there are less than 500 apointments remaining, add yellow color,
                // remove green.
                if (response.appointments_remaining < 500) {
                  $('#appointments_remaining').addClass('colorYellow');
                  $('#appointments_remaining').removeClass('colorGreen');
                }

                // If there are less than 100 apointments remaining, add red color,
                // remove yellow and green.
                if (response.appointments_remaining < 100) {
                  $('#appointments_remaining').addClass('colorRed');
                  $('#appointments_remaining').removeClass('colorYellow');
                  $('#appointments_remaining').removeClass('colorGreen');
                }
                return true;
            }

            // If there are no appointments remaining, show banner and disable
            // fields and buttons.
            if (response.appointments_remaining == 0) {
              $('#no-appointments-banner').removeClass('hide');
              $("input, select, button").each(function() {
                $( this ).attr("disabled", true);
              });
            }
        }, 'json').fail(GeneralFunctions.ajaxFailureHandler);
    }

    /**
     * Checks if the hour is still available
     *
     * If it's not it will take the user back to the previous page, if good it will submit
     *
     * @param {String} selDate The selected date of which the available hours we need to receive.
     */
    exports.handleSubmitAppointment = function (selDate) {

        // Find the selected service duration (it is going to be send within the "postData" object).
        var selServiceDuration = 60; // Default value of duration (in minutes).
        $.each(GlobalVariables.availableServices, function (index, service) {
            if (service.id == $('#select-service').val()) {
                selServiceDuration = service.duration;
            }
        });

        // If the manage mode is true then the appointment's start date should return as available too.
        var appointmentId = BackendBook.manageMode ? GlobalVariables.appointmentData.id : undefined;

        // Make ajax post request and get the available hours.
        var postUrl = GlobalVariables.baseUrl + '/appointments/ajax_get_available_hours';
        var postData = {
            csrfToken: GlobalVariables.csrfToken,
            service_id: $('#select-service').val(),
            provider_id: $('#select-provider').val(),
            selected_date: selDate,
            service_duration: selServiceDuration,
            manage_mode: BackendBook.manageMode,
            appointment_id: appointmentId
        };

        $.ajax({
            url: postUrl,
            method: 'post',
            data: postData,
            dataType: 'json'
        })
        .done(function (response) {
            if (!GeneralFunctions.handleAjaxExceptions(response)) {
                return false;
            }

            // if they already have an appointment, bypass the data select checks
            let original_select_start_date = $('#original-select-start-date').val();
            let selected_hour = $('.selected-hour').text();

            if (original_select_start_date !== '' && selected_hour  == '') {
                $('#book-appointment-submit').trigger('click');
            } else if (response.length > 0 && selected_hour != '') {
                var selectedHour = Date.parse($('.selected-hour').text()).toString('HH:mm');

                var hoursList = [];
                // get simple hours list
                response.forEach( function(element){
                    hoursList.push(element.available_hours);
                });

                if (hoursList.includes(selectedHour)) {
                    // hack, triggering the click of confirm here
                    // it would take too long to re-write the confirm/post process
                    $('#book-appointment-submit').trigger('click');
                } else {
                    // hide the confirm page that still shows for some reason...
                    $('#wizard-frame-1').show('fade');

                    // because it's not hiding right away from some reason
                    setTimeout(function(){
                        $('#wizard-frame-2').hide('fade');
                        $('.active-step').removeClass('active-step');
                        $('#step-1').addClass('active-step');
                    }, 2000);

                    Backend.displayNotification('Timeslot ' + selectedHour + ' no longer available, please select another time', null, 'danger');

                    BackendBookApi.getAvailableHours($('#select-date').val());
                }
                return response;
            } else {
                return [];
            }
        })
        .fail(function (jqxhr, textStatus, errorThrown) {
            GeneralFunctions.ajaxFailureHandler(jqxhr, textStatus, errorThrown);
            return [];
        })
    };

    /**
     * Register an appointment to the database.
     *
     * This method will make an ajax call to the appointments controller that will register
     * the appointment to the database.
     */
    exports.registerAppointment = function () {
        var $captchaText = $('.captcha-text');

        if ($captchaText.length > 0) {
            $captchaText.closest('.formGroup').removeClass('has-error');
            if ($captchaText.val() === '') {
                $captchaText.closest('.formGroup').addClass('has-error');
                return;
            }
        }

        var formData = jQuery.parseJSON($('input[name="post_data"]').val());
        var postData = {
            csrfToken: GlobalVariables.csrfToken,
            post_data: formData
        };

        if ($captchaText.length > 0) {
            postData.captcha = $captchaText.val();
        }

        if (GlobalVariables.manageMode) {
            postData.exclude_appointment_id = GlobalVariables.appointmentData.id;
        }
        var postUrl = GlobalVariables.baseUrl + '/appointments/ajax_register_appointment';
        var $layer = $('<div/>');

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
            }
        })
            .done(function (response) {
                if (!GeneralFunctions.handleAjaxExceptions(response)) {
                    // dirty have for duplicate user
                    // close the error
                    $('.btn-default').trigger('click');

                    // go back and hide last pane, show first pane
                    $('#wizard-frame-1').show('fade');

                    // because it's not hiding right away from some reason
                    setTimeout(function(){
                        $('#wizard-frame-2').hide('fade');
                        $('.active-step').removeClass('active-step');
                        $('#step-1').addClass('active-step');
                    }, 2000);

                    Backend.displayNotification(response['exceptions'][0]['message'], null, 'danger');

                    return false;
                }

                if (response.captcha_verification === false) {
                    $('#captcha-hint')
                        .text(EALang.captcha_is_wrong)
                        .fadeTo(400, 1);

                    setTimeout(function () {
                        $('#captcha-hint').fadeTo(400, 0);
                    }, 3000);

                    $('.captcha-title small').trigger('click');

                    $captchaText.closest('.formGroup').addClass('has-error');

                    return false;
                }

                var query_params = '';
                if (GlobalVariables.manageMode) {
                    query_params = '?manage_mode=1'
                }

                window.location.href = GlobalVariables.baseUrl
                    + '/backend/book_success/' + response.appointment_id + query_params;
            })
            .fail(function (jqxhr, textStatus, errorThrown) {
                $('.captcha-title small').trigger('click');
                GeneralFunctions.ajaxFailureHandler(jqxhr, textStatus, errorThrown);
            })
            .always(function () {
                $layer.remove();
            });
    };

    /**
     * Get the unavailable dates of a provider.
     *
     * This method will fetch the unavailable dates of the selected provider and service and then it will
     * select the first available date (if any). It uses the "BackendBookApi.getAvailableHours" method to
     * fetch the appointment* hours of the selected date.
     *
     * @param {Number} providerId The selected provider ID.
     * @param {Number} serviceId The selected service ID.
     * @param {String} selectedDateString Y-m-d value of the selected date.
     */
    exports.getUnavailableDates = function (providerId, serviceId, selectedDateString) {
        if (processingUnavailabilities) {
            return;
        }

        var appointmentId = BackendBook.manageMode ? GlobalVariables.appointmentData.id : undefined;

        var url = GlobalVariables.baseUrl + '/appointments/ajax_get_unavailable_dates';
        var data = {
            provider_id: providerId,
            service_id: serviceId,
            selected_date: encodeURIComponent(selectedDateString),
            csrfToken: GlobalVariables.csrfToken,
            manage_mode: BackendBook.manageMode,
            appointment_id: appointmentId
        };

        $.ajax({
            url: url,
            type: 'GET',
            data: data,
            dataType: 'json'
        })
            .done(function (response) {
                unavailableDatesBackup = response;
                selectedDateStringBackup = selectedDateString;
                _applyUnavailableDates(response, selectedDateString, true);
            })
            .fail(GeneralFunctions.ajaxFailureHandler);
    };

    exports.applyPreviousUnavailableDates = function () {
        _applyUnavailableDates(unavailableDatesBackup, selectedDateStringBackup);
    };

    function _applyUnavailableDates(unavailableDates, selectedDateString, setDate) {
        setDate = setDate || false;

        processingUnavailabilities = true;

        // Select first enabled date.
        var selectedDate = Date.parse(selectedDateString);
        var numberOfDays = new Date(selectedDate.getFullYear(), selectedDate.getMonth() + 1, 0).getDate();

        if (setDate && !GlobalVariables.manageMode) {
            for (var i = 1; i <= numberOfDays; i++) {
                // Im not sure why these were select current date, that assumes the starting point
                // of the calc was always going to be todays date
                // var currentDate = new Date(selectedDate.getFullYear(), selectedDate.getMonth(), i);
                if (unavailableDates.indexOf(selectedDate.toString('yyyy-MM-dd')) === -1) {
                    $('#select-date').datepicker('setDate', selectedDate);
                    BackendBookApi.getAvailableHours(selectedDate.toString('yyyy-MM-dd'));
                    break;
                }
            }
        }

        // If all the days are unavailable then hide the appointments hours.
        if (unavailableDates.length === numberOfDays) {
            $('#available-hours').text(EALang.no_available_hours);
        }

        // Grey out unavailable dates.
        $('#select-date .ui-datepicker-calendar td:not(.ui-datepicker-other-month)').each(function (index, td) {
            selectedDate.set({day: index + 1});
            if ($.inArray(selectedDate.toString('yyyy-MM-dd'), unavailableDates) != -1) {
                $(td).addClass('ui-datepicker-unselectable ui-state-disabled');
            }
        });

        // If manage mode disable current date and hide
        if(BackendBook.manageMode) {
            let selectedDate =  $('#select-date').find('.ui-state-highlight');
            selectedDate.parent().addClass('ui-datepicker-unselectable ui-state-disabled');
            selectedDate.removeClass('ui-state-highlight');
        }

        processingUnavailabilities = false;
    }

    /**
     * Save the user's consent.
     *
     * @param {Object} consent Contains user's consents.
     */
    exports.saveConsent = function (consent) {
        var url = GlobalVariables.baseUrl + '/consents/ajax_save_consent';
        var data = {
            csrfToken: GlobalVariables.csrfToken,
            consent: consent
        };

        $.post(url, data, function (response) {
            if (!GeneralFunctions.handleAjaxExceptions(response)) {
                return;
            }
        }, 'json').fail(GeneralFunctions.ajaxFailureHandler);
    };

    /**
     * Delete personal information.
     *
     * @param {Number} customerToken Customer unique token.
     */
    exports.deletePersonalInformation = function (customerToken) {
        var url = GlobalVariables.baseUrl + '/privacy/ajax_delete_personal_information';
        var data = {
            csrfToken: GlobalVariables.csrfToken,
            customer_token: customerToken
        };

        $.post(url, data, function (response) {
            if (!GeneralFunctions.handleAjaxExceptions(response)) {
                return;
            }

            location.href = GlobalVariables.baseUrl + '?wasDeleted=1';
        }, 'json').fail(GeneralFunctions.ajaxFailureHandler);
    };

})(window.BackendBookApi);
