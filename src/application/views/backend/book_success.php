<div class="p-3">
    <div>
        <?php
            echo '
                <div class="d-flex align-items-center mb-5">
                  <i class="far fa-4x fa-calendar-check mr-3 colorGreen"></i>
                  <h1 class="h3 mb-0">' . ($manage_mode === '1' ? lang('appointment_updated') : lang('appointment_registered')) . '</h1>
                  <p>' . lang('appointment_details_was_sent_to_you') . '</p>
                </div>
            ';

            echo '
              <div class="mb-4">
                <h2>Appointment Details</h2>
                <p class="mb-1"><span class="font-weight-bold">Date and Time: </span>' . $appointment_data['start_datetime'] . '</p>
                <p><span class="font-weight-bold">Booked on: </span>' . $appointment_data['book_datetime'] . '</p>
              </div>
            ';

            echo '
              <div class="mb-4">
                <h2>Patient Details</h2>
                <p class="mb-1"><span class="font-weight-bold">First Name: </span>' . $customer_data['first_name'] . '</p>
                <p class="mb-1"><span class="font-weight-bold">Last Name: </span>' . $customer_data['last_name'] . '</p>
                <p class="mb-1"><span class="font-weight-bold">Phone Number: </span>' . $customer_data['mobile_number'] . '</p>
                <p class="mb-1"><span class="font-weight-bold">Email: </span>' . $customer_data['email'] . '</p>
                <p class="mb-1"><span class="font-weight-bold">Address: </span>' . (isset($customer_data['address']) ? $customer_data['address'] : '') . '</p>
                <p class="mb-1"><span class="font-weight-bold">City: </span>' . (isset($customer_data['city']) ? $customer_data['city'] : '') . '</p>
                <p class="mb-1 mb-3"><span class="font-weight-bold">Zip Code: </span>' . (isset($customer_data['zip_code']) ? $customer_data['zip_code'] : '') . '</p>
                <a href="' . site_url('backend') . '" class="btn btn-primary">' .
                    'Next Appointment' . '
                </a>
              </div>
            ';

            if ($this->config->item('google_sync_feature')) {
                echo '
                    <button id="add-to-google-calendar" class="btn btn-primary">
                        <span class="glyphicon glyphicon-plus"></span>
                        ' . lang('add_to_google_calendar') . '
                    </button>';
            }

            // Display exceptions (if any).
            if (isset($exceptions)) {
                echo '<div class="col-xs-12" style="margin:10px">';
                echo '<h4>Unexpected Errors</h4>';
                foreach($exceptions as $exc) {
                    echo exceptionToHtml($exc);
                }
                echo '</div>';
            }
        ?>
    </div>
</div>
