var $form = $('.upload');
var $input    = $form.find('input[type="file"]'),
    $csrf    = $form.find('input[name="csrfToken"]'),
    $uploadButton = $form.find('.upload__button'),
    $label = $form.find('.upload__label');

var droppedFiles = false;
var ajaxData = null;

// Listen to drag events
$form.on('drag dragstart dragend dragover dragenter dragleave drop', function(e) {
  e.preventDefault();
  e.stopPropagation();
})
.on('dragover dragenter', function() {
  $form.addClass('is-dragover');
})
.on('dragleave drop', function() {
  $form.removeClass('is-dragover');
})
.on('drop', function(e) {
  droppedFiles = e.originalEvent.dataTransfer.files;
  // Reinit Request - Since we are only sending 1 file atm
  ajaxData = new FormData();
  ajaxData.append( $input.attr('name'), droppedFiles[0]);
  ajaxData.append( $csrf.attr('name'), $csrf.val());
  ajaxData.append( 'serviceId', $form.find('#select-service').children("option:selected").val());
  ajaxData.append( 'providerId', $form.find('#select-provider').children("option:selected").val());
  showFiles(droppedFiles);
  testFile();
});

$input.on('change', function(e) {
   // Reinit Request - Since we are only sending 1 file atm
   ajaxData = new FormData();
   ajaxData.append( $input.attr('name'), e.target.files[0]);
   ajaxData.append( $csrf.attr('name'), $csrf.val());
   ajaxData.append( 'serviceId', $form.find('#select-service').children("option:selected").val());
   ajaxData.append( 'providerId', $form.find('#select-provider').children("option:selected").val());
   showFiles(e.target.files);
   testFile();
});

$uploadButton.on('click', function(e) {
  insertFile();
})

// Show file data
function showFiles(files) {
  $('.fileInformation').show();
  var fileInformation = '<div>'
  + encodeURI(files[0].name) + ' / ' + (files[0].size / (1024 * 1024)).toFixed(2) + 'MB'
  + '</div>'
  $('.fileInformation').html(fileInformation)
};

// Progess bar elements
$progressWrapper = $('.progress');
$progressBar = $('.progress-bar');

// File data when upload completes
$uploadData = $('.uploadData');
// Error container if file needs changes
$uploadDataError = $('.uploadDataError');

// Status of the file upload
$uploadStatus = $('.uploadStatus');
$uploadStatusSuccess = $('.uploadStatus--success');
$uploadStatusError = $('.uploadStatus--error');
$helperText = $('.js-helperText');

// Sending AJAX request to test the file
function testFile() {
  $.ajax({
    url: '/importappointments/test',
    type: 'post',
    data: ajaxData,
    dataType: 'json',
    processData: false,
    contentType: false,
    cache: false,
    beforeSend: function() {
      // Reset values to default
      var percentVal = '0%';
      $progressBar.width(percentVal);
      $uploadStatus.html('');
      $helperText.hide();
      $uploadButton.hide();
      $uploadData.html('');
      $uploadDataError.html('');
    },
    xhr: function() {
      var xhr = new window.XMLHttpRequest();
      xhr.upload.addEventListener("progress", function(evt) {
        if (evt.lengthComputable) {
          $progressWrapper.show();
          var percentComplete = (evt.loaded / evt.total) * 100;
          $progressBar.width(percentComplete + '%');
          $progressBar.text(percentComplete + '%')
        }
      }, false);
      return xhr;
    },
    success: function(response) {
      if (response.status === "success") {
        // Show button, helper text and hide progress bar
        $uploadButton.show();
        $helperText.show();
        $progressWrapper.hide();
        // Show upload success message
        $uploadStatusSuccess.show();
        $uploadStatusSuccess.append('<p class="mb-3">' + response.message + '</p>');

        response.successList.forEach(function(row) {
          $uploadData.removeClass('hide');
          $uploadData.addClass('d-md-flex align-items-start flex-wrap');
          // Display file contents with patient data
          $uploadData.append('<dl class="uploadData__item row px-0 mx-0 mb-5">'
          + '<dt class="col-sm-8 h4 mb-3 colorGreenDark">Patient Information</dt><dd class="col-sm-2"></dd>'
          + '<dt class="col-sm-5">Date and Time</dt><dd class="col-sm-5">' + displayHelper(row.appt_date) + ' ' + displayHelper(row.appt_time) + '</dd>'
          + '<dt class="col-sm-5">Name</dt><dd class="col-sm-5">' + displayHelper(row.first_name) + ' ' + displayHelper(row.last_name) + '</dd>'
          + '<dt class="col-sm-5">Email</dt><dd class="col-sm-5">' + displayHelper(row.email) + '</dd>'
          + '<dt class="col-sm-5">Mobile Number</dt><dd class="col-sm-5">' + displayHelper(row.email) + '</dd>'
          + '<dt class="col-sm-5">Address</dt><dd class="col-sm-5">' + displayHelper(row.address) + ' ' + displayHelper(row.city) + ', ' + displayHelper(row.state) + ' ' + displayHelper(row.zip_code) + '</dd>'
          + '<dt class="col-sm-5">County</dt><dd class="col-sm-5">' + displayHelper(row.county) + '</dd>'
          + '<dt class="col-sm-5">First Responder</dt><dd class="col-sm-5">' + displayHelper(row.first_responder) + '</dd>'
          + '<dt class="col-sm-5">Gender</dt><dd class="col-sm-5">' + displayHelper(row.gender) + '</dd>'
          + '<dt class="col-sm-5">DOB</dt><dd class="col-sm-5">' + displayHelper(row.DOB) + '</dd>'
          + '<dt class="col-sm-5">Email Consent</dt><dd class="col-sm-5">' + displayHelper(row["Email Consent"]) + '</dd>'
          + '<dt class="col-sm-5">Text Message Consent</dt><dd class="col-sm-5">' + displayHelper(row["Text Message Consent"]) + '</dd>'
          + '<dt class="col-sm-5">Primary Care Physician</dt><dd class="col-sm-5">' + displayHelper(row.pcp_first_name) + ' ' + displayHelper(row.pcp_last_name) + '</dd>'
          + '<dt class="col-sm-5">Business Code</dt><dd class="col-sm-5">' + displayHelper(row.business_code) + '</dd>'
          + '</dl>'
          );

          // Display file contents with physician data
          $uploadData.append('<dl class="uploadData__item row px-0 mx-0 mb-5">'
          + '<dt class="col-sm-8 h4 mb-3 colorGreenDark">Doctor Information</dt><dd class="col-sm-2"></dd>'
          + '<dt class="col-sm-5">Physician Name</dt><dd class="col-sm-5">' + displayHelper(row.doctor_first_name) + ' ' + displayHelper(row.doctor_last_name) + '</dd>'
          + '<dt class="col-sm-5">Physician NPI</dt><dd class="col-sm-5">' + displayHelper(row.doctor_npi) + '</dd>'
          + '<dt class="col-sm-5">Physician Address</dt><dd class="col-sm-5">' + displayHelper(row.doctor_address) + ' ' + displayHelper(row.doctor_city) + ', ' + displayHelper(row.doctor_state) + ' ' + displayHelper(row.doctor_zip_code) + '</dd>'
          + '<dt class="col-sm-5">Physician Number</dt><dd class="col-sm-5">' + displayHelper(row.doctor_phone_number) + '</dd>'
          + '<dt class="col-sm-5">Rx Date</dt><dd class="col-sm-5">' + displayHelper(row.rx_date) + '</dd>'
          );
        })
      } else if (response.status === "error"){
        // Show upload error message
        $uploadStatusError.show();
        $uploadStatusError.append('<p class="mb-3">' + response.message + '</p>');

        if (response.errorList.fileErrors) {
          response.errorList.fileErrors.forEach(function(row) {
            $uploadDataError.append('<p>' + row +'</p>');
          });
        }

        if (response.errorList.rowErrors) {
          response.errorList.rowErrors.forEach(function(row) {
            $uploadDataError.append('<div class="d-flex">'
            + '<p><strong>Row ' + row.row +':</strong></p>'
            + '<p class="ml-2">' + row.message + '</p>'
            + '</div>'
            );
          });
        }
      }
    },
    error: function(response) {
      $uploadStatusError.show();
      $uploadStatusError.append('<p class="mb-3">There was a fatal error when trying to upload.</p>');
    }
  });
}

/**
 * Display empty string if it's undefined
 * @param {string} input
 */
function displayHelper(input) {
    return (input == undefined ? '' : input);
}

// Sending AJAX request to insert the file
function insertFile() {
  TODO: // What is causing the page to reload on successful import?
  $.ajax({
    url: '/importappointments/insert',
    type: 'post',
    data: ajaxData,
    dataType: 'json',
    processData: false,
    contentType: false,
    cache: false,
    async: false, // Need to wait for the upload to complete and get a response before reloading page
    success: function(response) {
      if (response.status !== "success") {
        // note: this should really never happen, since we test the file first
        $uploadStatusError.append('<p class="mb-3">File was not uploaded</p>');
        // list of errors
        $dataError.append(response.errorList)
      }
    },
    error: function(response) {
      $uploadStatusError.show();
      $uploadStatusError.append('<p class="mb-3">There was a fatal error when trying to upload.</p>');
    }
  });
}
