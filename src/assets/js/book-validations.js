//  https://github.com/validatorjs/validator.js
$(document).on('blur','input:not([type=file]), select', function() {
    // Get the value and trim white space
    let el = $(this);
    let eVal = el.val().trim();
    $(this).val(eVal);

    // Get the validations from the inputs data attributes
    // remove white space if any and add into an array
    let validationString = el.attr('data-validate')
    let validations = [];
    if(validationString) {
        validationString = validationString.replace(/\s/g,'');
        validations = validationString.split(',');
    }

    // Global Variables
    let failedValidation = false;
    let validationMessage = '';
    let today = new Date();

    // Loop through the validation
    for(let x = 0; x < validations.length; x++) {
        switch(validations[x]) {
            case 'required':
                if(el[0].type == 'text' || el[0].type == 'tel' || el[0].tagName == 'SELECT') {
                    if(validator.isEmpty(eVal)) {
                        failedValidation = true;
                        validationMessage = '* This is a required field';
                    };
                }
                else if(el[0].type == 'radio') {
                    if(!checkRadios(el[0].name)) {
                        failedValidation = true;
                        validationMessage = '* A selection is required';
                    }
                }
                break;
            case 'alpha':
                if(!validator.isEmpty(eVal) && !validator.isAlpha(eVal)) {
                    failedValidation = true;
                    validationMessage = '* Please only use letters';
                };
                break;
            case 'name':
                if(!validator.isEmpty(eVal)) {
                    let regex = new RegExp("^[a-zA-Z\-\ ']+$");
                    let nameTest = regex.test(eVal);
                    if(!nameTest) {
                        failedValidation = true;
                        validationMessage = '* Please only use letters, hyphens or apostrophes';
                    };
                }
                break;
            case 'specialChars':
                if(!validator.isEmpty(eVal)) {
                    let regex = new RegExp("^[a-zA-Z0-9\-\ ']+$");
                    let sc = regex.test(eVal);
                    if(!sc) {
                        failedValidation = true;
                        validationMessage = '* Please only use letters, numbers, hyphens or apostrophes';
                    };
                }
                break;
            case 'address':
                if(!validator.isEmpty(eVal)) {
                    let regex = new RegExp("^[a-zA-Z0-9\-\'\. ]+$");
                    let sc = regex.test(eVal);
                    if(!sc) {
                        failedValidation = true;
                        validationMessage = '* Please only use letters, numbers, hyphens, apostrophes or periods.';
                    };
                }
                break;
            case 'numeric':
                if(!validator.isNumeric(eVal)) {
                    failedValidation = true;
                    validationMessage = '* Please only use numbers';
                };
                break;
            case 'email':
                if(eVal != '') {
                    let cleanEmail = validator.normalizeEmail(eVal);
                        cleanEmail = validator.escape(cleanEmail);
                        if(!validator.isEmail(cleanEmail)) {
                            failedValidation = true;
                            validationMessage = '* Please enter a valid email address';
                        };
                }
                break;
            case 'date':
                if(eVal != '') {
                    if(!validator.isLength(eVal,{min: 10, max: 10})) {
                        failedValidation = true;
                        validationMessage = '* Date must use 00-00-0000 format';
                    };
                    let subDate = eVal.split('-');
                    if(parseInt(subDate[2]) < 1900) {
                        failedValidation = true;
                        validationMessage = '* Invalid Date';
                  };
                    let d = subDate[2] + '-' + subDate[0] + '-' + subDate[1];
                    if(!validator.isISO8601(d, options = {strict: true})) {
                        failedValidation = true;
                        validationMessage = '* Invalid Date';
                 }
                }
                break;
            case 'futureDate':
                if(eVal != '') {
                    let submDate = eVal.split('-');
                        submDate = submDate[2] + '-' + submDate[0] + '-' + submDate[1];
                        submDate = new Date(submDate);
                    if(today < submDate) {
                        failedValidation = true;
                        validationMessage = '* Invalid Date';
                    }
                }
                break;
            case 'phone':
                if(eVal != '') {
                    if(!validator.isLength(eVal,{min: 12, max: 12})) {
                        failedValidation = true;
                        validationMessage = '* Phone must use 000-000-0000 format';
                    };
                }
                break;
            case 'altPhone':
                if(eVal.length > 0) {
                    if(!validator.isLength(eVal,{min: 12, max: 12})) {
                        failedValidation = true;
                        validationMessage = '* Phone must use 000-000-0000 format';
                    };
                }
                break;
            case 'zipcode':
                if(eVal != '') {
                    if(!validator.isLength(eVal,{min: 5, max: 5})) {
                        failedValidation = true;
                        validationMessage = '* Zipcode must use be 5 digits';
                    };
                }
                break;
            case 'ssn':
                if(eVal.length > 0) {
                    if(!validator.isLength(eVal,{min: 4, max: 4})) {
                        failedValidation = true;
                        validationMessage = '* SSN must use be 4 digits';
                    };
                }
                break;
            case 'time':
                if(!validator.isLength(eVal,{min: 5, max: 5})) {
                    failedValidation = true;
                    validationMessage = '* Time must use the 00:00 format';
                };
                let subTime = eVal.split(':');
                if((parseInt(subTime[0]) > 12 || parseInt(subTime[0]) < 1) || parseInt(subTime[1]) > 59) {
                    failedValidation = true;
                    validationMessage = '* Invalid Time';
                };
                break;
            case 'dnsCheck':
                // To prevent unnecessary ajax calls, we store the last value that was checked, and run only if value changes
                if (eVal.length > 0 && window.dnsCheckEmail !== eVal) {
                    window.dnsCheckEmail = eVal;
                    let message = checkDNS(eVal, el);

                    if(message != '') {
                        failedValidation = true;
                        validationMessage = message;
                    }

                }
                break;
            case 'businessCode':
            if(eVal != '') {
                if(eVal.length != 8) {
                  failedValidation = true;
                  validationMessage = '* Business Code must be 8 characters';
                } else {
                  let el = $('#business-code');
                  checkBusinessCode(el.val(), el);
                }
            }
        }

        if(failedValidation) break;
    }

    if(failedValidation) {
        // Show the error
        $(el).parent().addClass('has-error');
        //  If radio group show the parents next span
        // else show in the next span in the parent
        if(el[0].type =='radio') {
            $(el).parent().next('.errorMessage').html(validationMessage);
        }
        // If it's the input icon group
        else if($(el).parent().hasClass('inputIconGroup')) {
          $(el).parents().next('.errorMessage').html(validationMessage);
        }
        else {
            $(el).next('.errorMessage').html(validationMessage);
        }
    }
    else {
        // Hide error
        $(el).parent().removeClass('has-error');
        if($(el)[0].type =='radio') {
            $(el).parent().next('.errorMessage').html('');
        }
        else {
            $(el).next('.errorMessage').html('');
        }
    }
});

function checkRadios(name){
    var radios = document.getElementsByName(name);

    for (var i = 0, len = radios.length; i < len; i++) {
         if (radios[i].checked) {
             return true;
         }
    }

    return false;
}

// capture the last used service id
var last_used_service_id = $('#select-service').val();
$('#last_used_service_id').val(last_used_service_id);

/**
 * Make a database request to check
 * @param {string} businessCode
 * @param {Element} el
 * @returns {boolean} true if valid, otherwise false
 */
function checkBusinessCode(business_code, el) {
    // Get message container
    let errorContainer = $(el).parents().next('.errorMessage');

    // Business code states
    let busCodeSuccess = $('#busCodeSuccess');
    let busCodeError = $('#busCodeError');
    // Calendar spinner
    let calendarSpinner = $('.bookAppointment__calendarSpinner');

    // Clear out error message
    errorContainer.html('');

     // Make database request
     var urlEndpoint = GlobalVariables.baseUrl + '/businesscodevalidate';
     $.ajax({
         method: 'POST',
         url: urlEndpoint,
         data: {
             business_code: business_code,
             csrfToken: GlobalVariables.csrfToken,
         },
         dataType: 'json',
         async: false,
         beforeSend: function () {
             // Hide icons
             busCodeSuccess.addClass('hide');
             busCodeError.addClass('hide');
             // Show the calendar spinner
             calendarSpinner.removeClass('hide');
             // Remove any errors
             $(el).parents('.formGroup').removeClass('has-error');
         }
     })
     .done(function(response) {
         if (response.valid) {
             // Wait a second, hide the calendar spinner
             setTimeout(function() {
               calendarSpinner.addClass('hide');
               // Show success icon
               busCodeSuccess.removeClass('hide');
             }, 1000)

             // valid
             errorContainer.html('');
             $('#button-next-1').prop('disabled', false);
             $('.submitErrorMessage').html('');

             // if priority, use priority_service_id, otherwise business_service_id
             var new_service_id = response.priority_service ? 'priority_service_id' : 'business_service_id';
             new_service_id = $('#' + new_service_id).val();

             // change to new service id
             $('#select-service').val(new_service_id);
             $('#select-service').change();
             console.log('User this ' + new_service_id)
         } else {
             // Business Code is not active
             errorContainer.html(response.message);
             $(el).parents('.formGroup').addClass('has-error');

             // revert back to last used id
             var last_used_service_id = $('#last_used_service_id').val();
             $('#select-service').val(last_used_service_id);
             $('#select-service').change();

             // Show the calendar spinner
             calendarSpinner.removeClass('hide');

             // Wait a second, hide the calendar spinner
             setTimeout(function() {
               // Show error icon
               busCodeError.removeClass('hide');
               calendarSpinner.addClass('hide');
             }, 1000)
         }
     })
     .fail(function(jqXHR, response) {
        errorContainer.html('There was a problem receiving data');
     });
    return true;
}

/**
 * Make a network request to check
 * @param {string} email
 * @returns {boolean} true if valid, otherwise false
 */
function checkDNS(email, el) {
    let passingError = $('.errorMessage--email');
    let errorMessage = '* This email address has a low chance of receiving an email';
    let message = ''
    passingError.html('');

    // Make network request
    var urlEndpoint = GlobalVariables.baseUrl + '/emailvalidate';
    $.ajax({
        method: 'POST',
        url: urlEndpoint,
        data: {
            email: email,
            csrfToken: GlobalVariables.csrfToken,
        },
        dataType: 'json',
        async: false,
    })
    .done(function(response) {
        if (response.valid === true) {
            // valid
            return true;
        }
        else if (response.status === 'success' && response.valid === false) {
            // invalid from service
            message = errorMessage;
        }
        else {
            passingError.html(response.message);
            message = '';
        }
    })
    .fail(function(jqXHR, response) {
        passingError.html('* Email validator service is down');
        message = '';
    });

    return message;
}
