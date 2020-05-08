<?php defined('BASEPATH') OR exit('No direct script access allowed');

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

use \EA\Engine\Types\Text;
use \EA\Engine\Types\Email;

/**
 * Appointments Controller
 *
 * @package Controllers
 */
class Appointments extends CI_Controller {
    /**
     * Class Constructor
     */
    public function __construct()
    {
        parent::__construct();

        $this->load->library('session');
        $this->load->helper('installation');

        // Set user's selected language.
        if ($this->session->userdata('language'))
        {
            $this->config->set_item('language', $this->session->userdata('language'));
            $this->lang->load('translations', $this->session->userdata('language'));
        }
        else
        {
            $this->lang->load('translations', $this->config->item('language')); // default
        }

        // Common helpers
        $this->load->helper('google_analytics');
    }

    /**
     * Default callback method of the application.
     *
     * This method creates the appointment book wizard. If an appointment hash
     * is provided then it means that the customer followed the appointment
     * manage link that was send with the book success email.
     *
     * @param string $appointment_hash DB appointment hash of an existing record (default '').
     */
    public function index($appointment_hash = '')
    {
        if ( ! is_ea_installed())
        {
            redirect('installation/index');
            return;
        }

        // A public page isn't a thing right now.. send em to the backend
        redirect('backend');

        $this->load->model('appointments_model');
        $this->load->model('providers_model');
        $this->load->model('services_model');
        $this->load->model('customers_model');
        $this->load->model('settings_model');

        try
        {
            $available_services = $this->services_model->get_available_services();
            $available_providers = $this->providers_model->get_available_providers();
            $company_name = $this->settings_model->get_setting('company_name');
            $date_format = $this->settings_model->get_setting('date_format');
            $time_format = $this->settings_model->get_setting('time_format');
            $display_cookie_notice = $this->settings_model->get_setting('display_cookie_notice');
            $cookie_notice_content = $this->settings_model->get_setting('cookie_notice_content');
            $display_terms_and_conditions = $this->settings_model->get_setting('display_terms_and_conditions');
            $terms_and_conditions_content = $this->settings_model->get_setting('terms_and_conditions_content');
            $display_privacy_policy = $this->settings_model->get_setting('display_privacy_policy');
            $privacy_policy_content = $this->settings_model->get_setting('privacy_policy_content');

            // Remove the data that are not needed inside the $available_providers array.
            foreach ($available_providers as $index => $provider)
            {
                $stripped_data = [
                    'id' => $provider['id'],
                    'first_name' => $provider['first_name'],
                    'last_name' => $provider['last_name'],
                    'services' => $provider['services']
                ];
                $available_providers[$index] = $stripped_data;
            }

            // If an appointment hash is provided then it means that the customer
            // is trying to edit a registered appointment record.
            if ($appointment_hash !== '')
            {
                // Load the appointments data and enable the manage mode of the page.
                $manage_mode = TRUE;

                $results = $this->appointments_model->get_batch(['hash' => $appointment_hash]);

                if (count($results) === 0)
                {
                    // The requested appointment doesn't exist in the database. Display
                    // a message to the customer.
                    $view = [
                        'message_title' => $this->lang->line('appointment_not_found'),
                        'message_text' => $this->lang->line('appointment_does_not_exist_in_db'),
                        'message_icon' => base_url('assets/img/error.png')
                    ];
                    $this->load->view('appointments/message', $view);
                    return;
                }

                $appointment = $results[0];
                $provider = $this->providers_model->get_row($appointment['id_users_provider']);
                $customer = $this->customers_model->get_row($appointment['id_users_customer']);

                $customer_token = md5(uniqid(mt_rand(), true));

                $this->load->driver('cache', ['adapter' => 'file']);

                $this->cache->save('customer-token-' . $customer_token, $customer['id'], 600); // save for 10 minutes
            }
            else
            {
                // The customer is going to book a new appointment so there is no
                // need for the manage functionality to be initialized.
                $manage_mode = FALSE;
                $customer_token = FALSE;
                $appointment = [];
                $provider = [];
                $customer = [];
            }

            // Load the book appointment view.
            $view = [
                'available_services' => $available_services,
                'available_providers' => $available_providers,
                'company_name' => $company_name,
                'manage_mode' => $manage_mode,
                'customer_token' => $customer_token,
                'date_format' => $date_format,
                'time_format' => $time_format,
                'appointment_data' => $appointment,
                'provider_data' => $provider,
                'customer_data' => $customer,
                'display_cookie_notice' => $display_cookie_notice,
                'cookie_notice_content' => $cookie_notice_content,
                'display_terms_and_conditions' => $display_terms_and_conditions,
                'terms_and_conditions_content' => $terms_and_conditions_content,
                'display_privacy_policy' => $display_privacy_policy,
                'privacy_policy_content' => $privacy_policy_content,
            ];
        }
        catch (Exception $exc)
        {
            $view['exceptions'][] = $exc;
        }

        $this->load->view('appointments/book', $view);
    }

    /**
     * Cancel an existing appointment.
     *
     * This method removes an appointment from the company's schedule. In order for the appointment to be deleted, the
     * hash string must be provided. The customer can only cancel the appointment if the edit time period is not over
     * yet. Provide the $_POST['cancel_reason'] parameter to describe the cancellation reason.
     *
     * @param string $appointment_hash This is used to distinguish the appointment record.
     */
    public function cancel($appointment_hash)
    {
        try
        {
            $this->load->model('appointments_model');
            $this->load->model('providers_model');
            $this->load->model('customers_model');
            $this->load->model('services_model');
            $this->load->model('settings_model');

            // Check whether the appointment hash exists in the database.
            $records = $this->appointments_model->get_batch(['hash' => $appointment_hash]);
            if (count($records) == 0)
            {
                throw new Exception('No record matches the provided hash.');
            }

            $appointment = $records[0];
            $provider = $this->providers_model->get_row($appointment['id_users_provider']);
            $customer = $this->customers_model->get_row($appointment['id_users_customer']);
            $service = $this->services_model->get_row($appointment['id_services']);

            $company_settings = [
                'company_name' => $this->settings_model->get_setting('company_name'),
                'company_email' => $this->settings_model->get_setting('company_email'),
                'company_link' => $this->settings_model->get_setting('company_link'),
                'date_format' => $this->settings_model->get_setting('date_format'),
                'time_format' => $this->settings_model->get_setting('time_format')
            ];

            // :: DELETE APPOINTMENT RECORD FROM THE DATABASE.
            if ( ! $this->appointments_model->delete($appointment['id']))
            {
                throw new Exception('Appointment could not be deleted from the database.');
            }

            // :: SYNC APPOINTMENT REMOVAL WITH GOOGLE CALENDAR
            if ($appointment['id_google_calendar'] != NULL)
            {
                try
                {
                    $google_sync = filter_var($this->providers_model
                        ->get_setting('google_sync', $appointment['id_users_provider']), FILTER_VALIDATE_BOOLEAN);

                    if ($google_sync == TRUE)
                    {
                        $google_token = json_decode($this->providers_model
                            ->get_setting('google_token', $provider['id']));
                        $this->load->library('Google_sync');
                        $this->google_sync->refresh_token($google_token->refresh_token);
                        $this->google_sync->delete_appointment($provider, $appointment['id_google_calendar']);
                    }
                }
                catch (Exception $exc)
                {
                    $exceptions[] = $exc;
                }
            }

            // :: SEND NOTIFICATION EMAILS TO CUSTOMER AND PROVIDER
            try
            {
                $this->config->load('email');
                $email = new \EA\Engine\Notifications\Email($this, $this->config->config);

                $send_provider = filter_var($this->providers_model
                    ->get_setting('notifications', $provider['id']), FILTER_VALIDATE_BOOLEAN);

                if ($send_provider === TRUE)
                {
                    $email->sendDeleteAppointment($appointment, $provider,
                        $service, $customer, $company_settings, new Email($provider['email']),
                        new Text($this->input->post('cancel_reason')));
                }

                $send_customer = filter_var($this->settings_model->get_setting('customer_notifications'),
                    FILTER_VALIDATE_BOOLEAN);

                if ($send_customer === TRUE)
                {
                    $email->sendDeleteAppointment($appointment, $provider,
                        $service, $customer, $company_settings, new Email($customer['email']),
                        new Text($this->input->post('cancel_reason')));
                }

            }
            catch (Exception $exc)
            {
                $exceptions[] = $exc;
            }
        }
        catch (Exception $exc)
        {
            // Display the error message to the customer.
            $exceptions[] = $exc;
        }

        $view = [
            'message_title' => $this->lang->line('appointment_cancelled_title'),
            'message_text' => $this->lang->line('appointment_cancelled'),
            'message_icon' => base_url('assets/img/success.png')
        ];

        if (isset($exceptions))
        {
            $view['exceptions'] = $exceptions;
        }

        $this->load->view('appointments/message', $view);
    }

    /**
     * GET an specific appointment book and redirect to the success screen.
     *
     * @param int $appointment_id Contains the ID of the appointment to retrieve.
     */
    public function book_success($appointment_id)
    {
        // If the appointment id doesn't exist or zero redirect to index.
        if ( ! $appointment_id)
        {
            redirect('appointments');
        }
        $this->load->model('appointments_model');
        $this->load->model('providers_model');
        $this->load->model('services_model');
        $this->load->model('settings_model');
        //retrieve the data needed in the view
        $appointment = $this->appointments_model->get_row($appointment_id);
        $provider = $this->providers_model->get_row($appointment['id_users_provider']);
        $service = $this->services_model->get_row($appointment['id_services']);
        $company_name = $this->settings_model->get_setting('company_name');
        //get the exceptions
        $exceptions = $this->session->flashdata('book_success');
        // :: LOAD THE BOOK SUCCESS VIEW
        $view = [
            'appointment_data' => $appointment,
            'provider_data' => $provider,
            'service_data' => $service,
            'company_name' => $company_name,
        ];
        if ($exceptions)
        {
            $view['exceptions'] = $exceptions;
        }
        $this->load->view('appointments/book_success', $view);
    }

    /**
     * [AJAX] Get the available appointment hours for the given date.
     *
     * This method answers to an AJAX request. It calculates the available hours for the given service, provider and
     * date.
     *
     * Required POST parameters:
     *
     * - int $_POST['service_id'] Selected service record ID.
     * - int|string $_POST['provider_id'] Selected provider record id, can also be 'any-provider'.
     * - string $_POST['selected_date'] Selected date for availabilities.
     * - int $_POST['service_duration'] Selected service duration in minutes.
     * - string $_POST['manage_mode'] Contains either 'true' or 'false' and determines the if current user
     * is managing an already booked appointment or not.
     *
     * Outputs a JSON string with the availabilities.
     *
     * @deprecated Since v1.3.0, this method will be replaced with a future release.
     */
    public function ajax_get_available_hours()
    {
        $this->load->model('providers_model');
        $this->load->model('appointments_model');
        $this->load->model('settings_model');
        $this->load->model('services_model');

        try
        {
            // Do not continue if there was no provider selected (more likely there is no provider in the system).
            if (empty($this->input->post('provider_id')))
            {
                $this->output
                    ->set_content_type('application/json')
                    ->set_output(json_encode([]));
                return;
            }

            // If manage mode is TRUE then the following we should not consider the selected
            // appointment when calculating the available time periods of the provider.
            $exclude_appointments = ($this->input->post('manage_mode') === 'true')
                ? [$this->input->post('appointment_id')]
                : [];

            // If the user has selected the "any-provider" option then we will need to search
            // for an available provider that will provide the requested service.
            if ($this->input->post('provider_id') === ANY_PROVIDER)
            {
                $_POST['provider_id'] = $this->_search_any_provider($this->input->post('service_id'),
                    $this->input->post('selected_date'));
                if ($this->input->post('provider_id') === NULL)
                {
                    $this->output
                        ->set_content_type('application/json')
                        ->set_output(json_encode([]));
                    return;
                }
            }

            $service = $this->services_model->get_row($this->input->post('service_id'));
            $provider = $this->providers_model->get_row($_POST['provider_id']);

            $empty_periods = $this->_get_provider_available_time_periods($this->input->post('provider_id'),
                $this->input->post('service_id'),
                $this->input->post('selected_date'), $exclude_appointments);

            $available_hours = $this->_calculate_available_hours($empty_periods, $this->input->post('selected_date'),
                $this->input->post('service_duration'),
                filter_var($this->input->post('manage_mode'), FILTER_VALIDATE_BOOLEAN),
                $service['availabilities_type']);

            $available_hours = $this->_get_multiple_attendants_hours($this->input->post('selected_date'), $service,
                $provider);

            // array structure changed.. dont sort for now
            // $available_hours = array_values($available_hours);
            // sort($available_hours, SORT_STRING);
            // $available_hours = array_values($available_hours);

            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode($available_hours));
        }
        catch (Exception $exc)
        {
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'exceptions' => [exceptionToJavaScript($exc)]
                ]));
        }
    }

    /**
     * [AJAX] Register the appointment to the database.
     *
     * Outputs a JSON string with the appointment ID.
     */
    public function ajax_register_appointment()
    {
        try
        {
            $post_data = $this->input->post('post_data'); // alias
            $post_data['manage_mode'] = filter_var($post_data['manage_mode'], FILTER_VALIDATE_BOOLEAN);
            $this->load->model('appointments_model');
            $this->load->model('providers_model');
            $this->load->model('services_model');
            $this->load->model('user_model');
            $this->load->model('customers_model');
            $this->load->model('settings_model');

            // Validate the CAPTCHA string.
            if ($this->settings_model->get_setting('require_captcha') === '1'
                && $this->session->userdata('captcha_phrase') !== $this->input->post('captcha'))
            {
                $this->output
                    ->set_content_type('application/json')
                    ->set_output(json_encode([
                        'captcha_verification' => FALSE
                    ]));
                return;
            }

            // Handle appointment data
            $appointment = $_POST['post_data']['appointment'];

            // Check appointment availability.
            if ($this->shouldAddUpdateTime($appointment) && !$this->_check_datetime_availability())
            {
                throw new Exception($this->lang->line('requested_hour_is_unavailable'));
            }
            // Reload potentially changed data
            $appointment = $_POST['post_data']['appointment'];

            // if it is an editted appointment, and the appointment date hasnt changed, dont notify
            $shouldTrySendNotifications = true;
            if ($post_data['manage_mode']) {
                $old_appointment = $this->appointments_model->get_row($appointment['id']);

                if ($old_appointment['start_datetime'] === $appointment['start_datetime']) {
                    $shouldTrySendNotifications = false;
                }
            }

            // Handle customer data
            $customer = $_POST['post_data']['customer'];

            // Check to override CoD business code
            // if city work, give them a business code
            $appointment['business_code'] = $customer['city_worker'] ? Config::BUSINESS_CODE_CITY_WORKER : $appointment['business_code'];

            // only if creating new record
            if (!isset($customer['id'])) {
                // Generate unique PatientId,
                $customer['patient_id'] = $this->customers_model->generate_unique_patient_id($appointment['start_datetime']);
            }

            // Transaction Start
            $this->db->trans_begin();

            // If customer array has 'id', then it's treated as an UPDATE/EDIT
            $customer_id = $this->customers_model->add($customer, true, $appointment);

            if ($this->shouldAddUpdateTime($appointment)) {
                unset($appointment['updateTime']);
                $initials = $this->customers_model->parseNameInitials($customer['first_name'], $customer['last_name']);
                $appointment['id_users_customer'] = $customer_id;
                $appointment['is_unavailable'] = (int)$appointment['is_unavailable']; // needs to be type casted
                $appointment['id'] = $this->appointments_model->add($appointment, $initials);
                $appointment['hash'] = $this->appointments_model->get_value('hash', $appointment['id']);
            }

            // Transaction Commit
            $this->db->trans_commit();

            $provider = $this->providers_model->get_row($appointment['id_users_provider']);
            $service = $this->services_model->get_row($appointment['id_services']);

            $company_settings = [
                'company_name' => $this->settings_model->get_setting('company_name'),
                'company_link' => $this->settings_model->get_setting('company_link'),
                'company_email' => $this->settings_model->get_setting('company_email'),
                'date_format' => $this->settings_model->get_setting('date_format'),
                'time_format' => $this->settings_model->get_setting('time_format')
            ];

            // :: SYNCHRONIZE APPOINTMENT WITH PROVIDER'S GOOGLE CALENDAR
            // The provider must have previously granted access to his google calendar account
            // in order to sync the appointment.
            try
            {
                $google_sync = filter_var($this->providers_model->get_setting('google_sync',
                    $appointment['id_users_provider']), FILTER_VALIDATE_BOOLEAN);

                if ($google_sync == TRUE)
                {
                    $google_token = json_decode($this->providers_model
                        ->get_setting('google_token', $appointment['id_users_provider']));

                    $this->load->library('google_sync');
                    $this->google_sync->refresh_token($google_token->refresh_token);

                    // TODO: What does manage_mode do?
                    if ($post_data['manage_mode'] === FALSE)
                    {
                        // Add appointment to Google Calendar.
                        $google_event = $this->google_sync->add_appointment($appointment, $provider,
                            $service, $customer, $company_settings);
                        $appointment['id_google_calendar'] = $google_event->id;
                        $this->appointments_model->add($appointment);
                    }
                    else
                    {
                        // Update appointment to Google Calendar.
                        $appointment['id_google_calendar'] = $this->appointments_model
                            ->get_value('id_google_calendar', $appointment['id']);

                        $this->google_sync->update_appointment($appointment, $provider,
                            $service, $customer, $company_settings);
                    }
                }
            }
            catch (Exception $exc)
            {
                $this->logger->error($exc->getMessage());
                $this->logger->error($exc->getTraceAsString());
            }

            $blnUpdate = $post_data['manage_mode'] ? true : false;

            if ($shouldTrySendNotifications) {
                // :: Send email notification to patient
                try
                {
                    $this->load->library('ses_email');
                    $email = new \EA\Engine\Notifications\Email($this, $this->config->config, $this->ses_email, $blnUpdate);
                    $email->sendAppointmentDetails($customer, $appointment, Config::SES_EMAIL_ADDRESS, Config::SES_EMAIL_NAME);
                }
                catch (Exception $exc)
                {
                    log_message('error', $exc->getMessage());
                    log_message('error', $exc->getTraceAsString());
                }

                // :: Send SMS notification to patient
                try
                {
                    $this->load->library('sns_sms');
                    $sms = new \EA\Engine\Notifications\Sms($this, $this->config->config, $this->sns_sms);
                    $sms->sendAppointmentDetails($customer, $appointment, $blnUpdate);
                }
                catch (Exception $exc)
                {
                    log_message('error', $exc->getMessage());
                    log_message('error', $exc->getTraceAsString());
                }
            }

            // Rock Connections Dashboard Hook
            try {
                // Only run this in production
                if (ENVIRONMENT === 'production' && defined("Config::METRICS_WEBHOOK_URL") && Config::METRICS_WEBHOOK_URL) {
                    $this->load->library('Rc_dashboard');
                    $dateCurr = new DateTime();
                    $apptRange = $this->rc_dashboard->generateScheduledTotalRange();

                    $user = $this->user_model->get_settings($appointment['id_users_provider']);
                    $business_service_id = $user['settings']['business_service_id'] ?? null;

                    $data = [
                        'start_datetime' => $appointment['start_datetime'],
                        'zip_code' => $customer['zip_code'],
                        'city' => $customer['city'],
                        'dob' => $customer['dob'],
                        'caller' => $customer['caller'],
                        'availableToday' => $this->appointments_model->get_available_appointments_longrange($dateCurr->format('Y-m-d'), $service, $provider, 1, false)['appointments_remaining'] ?? null,
                        'availableTotal' => $this->appointments_model->get_available_appointments_longrange($dateCurr->format('Y-m-d'), $service, $provider, 7, false)['appointments_remaining'] ?? null,
                        'scheduledToday' => $this->appointments_model->count_created_appointments($dateCurr, [$service['id'], $business_service_id]),
                        'scheduledTotal' => $this->appointments_model->count_scheduled_appointments($apptRange[0], $apptRange[1], [$service['id'], $business_service_id]),
                    ];

                    $req = $this->rc_dashboard->buildBody($data);
                    $success = $this->rc_dashboard->pushToWebhook($req);
                }
                else {
                    // We don't have a NonProd endpoint to test with
                    $this->logger->debug(sprintf('Skipping webhook to RC dashboard'));
                }
            } catch (Exception $e) {
                $this->logger->error(sprintf('Error trying to call RC Dashboard webhook, Exception: %s', $e));
            }

            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'appointment_id' => $appointment['id']
                ]));
        }
        catch (Exception $exc)
        {
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'exceptions' => [exceptionToJavaScript($exc)]
                ]));
        }
    }

    /**
     * [AJAX] Get Unavailable Dates
     *
     * Get an array with the available dates of a specific provider, service and month of the year. Provide the
     * "provider_id", "service_id" and "selected_date" as GET parameters to the request. The "selected_date" parameter
     * must have the Y-m-d format.
     *
     * Outputs a JSON string with the unavailable dates. that are unavailable.
     *
     * @deprecated Since v1.3.0, this method will be replaced with a future release.
     */
    public function ajax_get_unavailable_dates()
    {
        try
        {
            $provider_id = $this->input->get('provider_id');
            $service_id = $this->input->get('service_id');
            $selected_date_string = $this->input->get('selected_date');
            $selected_date = new DateTime($selected_date_string);
            $number_of_days_in_month = (int)$selected_date->format('t');
            $unavailable_dates = [];
            $manage_mode = filter_var($this->input->get('manage_mode'), FILTER_VALIDATE_BOOLEAN);

            $exclude_appointments = ($_REQUEST['manage_mode'] === 'true')
                ? [$_REQUEST['appointment_id']]
                : [];

            $provider_list = ($provider_id === ANY_PROVIDER) ? $this->_search_providers_by_service($service_id) : [$provider_id] ;

            $this->load->model('providers_model');

            // Get the service record.
            $this->load->model('services_model');
            $service = $this->services_model->get_row($service_id);

            for ($i = 1; $i <= $number_of_days_in_month; $i++)
            {
                $current_date = new DateTime($selected_date->format('Y-m') . '-' . $i);

                if ($current_date < new DateTime(date('Y-m-d 00:00:00')))
                {
                    // Past dates become immediately unavailable.
                    $unavailable_dates[] = $current_date->format('Y-m-d');
                    continue;
                }

                // Finding at least one slot of availablity
                foreach ($provider_list as $curr_provider_id)
                {
                    // Get the provider record.
                    $curr_provider = $this->providers_model->get_row($curr_provider_id);

                    $empty_periods = $this->_get_provider_available_time_periods($curr_provider_id,
                        $service_id,
                        $current_date->format('Y-m-d'), $exclude_appointments);

                    $available_hours = $this->_calculate_available_hours($empty_periods, $current_date->format('Y-m-d'),
                        $service['duration'], $manage_mode, $service['availabilities_type']);
                    if (! empty($available_hours)) break;

                    $available_hours = $this->_get_multiple_attendants_hours($current_date->format('Y-m-d'), $service,
                        $curr_provider);
                    if (! empty($available_hours)) break;
                }

                // No availability amongst all the provider
                if (empty($available_hours))
                {
                    $unavailable_dates[] = $current_date->format('Y-m-d');
                }
            }

            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode($unavailable_dates));
        }
        catch (Exception $exc)
        {
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'exceptions' => [exceptionToJavaScript($exc)]
                ]));
        }
    }

    /**
     * Check whether the provider is still available in the selected appointment date.
     *
     * It might be times where two or more customers select the same appointment date and time. This shouldn't be
     * allowed to happen, so one of the two customers will eventually get the preferred date and the other one will have
     * to choose for another date. Use this method just before the customer confirms the appointment details. If the
     * selected date was taken in the mean time, the customer must be prompted to select another time for his
     * appointment.
     *
     * @return bool Returns whether the selected datetime is still available.
     */
    protected function _check_datetime_availability(): bool
    {
        $this->load->model('services_model');
        $this->load->model('appointments_model');

        $appointment = $_POST['post_data']['appointment'];

        $service_duration = $this->services_model->get_value('duration', $appointment['id_services']);

        $exclude_appointments = (isset($appointment['id'])) ? [$appointment['id']] : [];

        $attendants_number = $this->services_model->get_value('attendants_number', $appointment['id_services']);

        if ($attendants_number > 1)
        {
            // Exclude all the appointments that will are currently registered.
            $exclude = $this->appointments_model->get_batch([
                'id_services' => $appointment['id_services'],
                'start_datetime' => $appointment['start_datetime']
            ]);

            if ( ! empty($exclude) && count($exclude) < $attendants_number)
            {
                foreach ($exclude as $entry)
                {
                    $exclude_appointments[] = $entry['id'];
                }
            }
        }

        if ($appointment['id_users_provider'] === ANY_PROVIDER)
        {
            $appointment['id_users_provider'] = $this->_search_any_provider($appointment['id_services'],
                date('Y-m-d', strtotime($appointment['start_datetime'])));
            // Raw POST gets modified here, which is needed downstream.
            $_POST['post_data']['appointment']['id_users_provider'] = $appointment['id_users_provider'];
            $this->logger->debug('_check_datetime_availability(): selected provider is always available');
            return TRUE; // The selected provider is always available.
        }

        $available_periods = $this->_get_provider_available_time_periods(
            $appointment['id_users_provider'], $appointment['id_services'],
            date('Y-m-d', strtotime($appointment['start_datetime'])),
            $exclude_appointments);

        // Set up appointment times
        $appt_start = new DateTime($appointment['start_datetime']);
        $appt_start = $appt_start->format('H:i');

        $appt_end = new DateTime($appointment['start_datetime']);
        $appt_end->add(new DateInterval('PT' . $service_duration . 'M'));
        $appt_end = $appt_end->format('H:i');
        $this->logger->debug("_check_datetime_availability(): appointment: {$appt_start}, {$appt_end}");

        // Flag to check if avail
        $is_still_available = FALSE;

        $this->logger->debug('_check_datetime_availability(): checking available periods: ' . count($available_periods));
        foreach ($available_periods as $period)
        {
            $this->logger->debug("_check_datetime_availability(): period: {$period['start']}, {$period['end']}");

            $period_start = date('H:i', strtotime($period['start']));
            $period_end = date('H:i', strtotime($period['end']));

            if ($period_start <= $appt_start && $period_end >= $appt_end)
            {
                $is_still_available = TRUE;
                break;
            }
        }
        $this->logger->debug("_check_datetime_availability(): result: $is_still_available");

        return $is_still_available;
    }

    /**
     * Get an array containing the free time periods (start - end) of a selected date.
     *
     * This method is very important because there are many cases where the system needs to know when a provider is
     * available for an appointment. This method will return an array that belongs to the selected date and contains
     * values that have the start and the end time of an available time period.
     *
     * @param int $provider_id Provider record ID.
     * @param int $service_id Service record ID.
     * @param string $selected_date Date to be checked (MySQL formatted string).
     * @param array $excluded_appointment_ids Array containing the IDs of the appointments that will not be taken into
     * consideration when the available time periods are calculated.
     *
     * @return array Returns an array with the available time periods of the provider.
     */
    protected function _get_provider_available_time_periods(
        $provider_id,
        $service_id,
        $selected_date,
        $excluded_appointment_ids = []
    ) {
        $this->load->model('appointments_model');
        $this->load->model('providers_model');
        $this->load->model('services_model');

        // Get the service, provider's working plan and provider appointments.
        $working_plan = json_decode($this->providers_model->get_setting('working_plan', $provider_id), TRUE);


        // TODO: Figure out why `id_services` was not present in this lookup
        // Without it, only the last appointment slot was returned, instead
        // of the expected list of slots for the specific service.
        $provider_appointments = $this->appointments_model->get_batch([
            'id_users_provider' => $provider_id,
            'id_services' => $service_id, // TODO:
        ]);

        // Sometimes it might be necessary to not take into account some appointment records in order to display what
        // the providers' available time periods would be without them.
        foreach ($excluded_appointment_ids as $excluded_appointment_id)
        {
            foreach ($provider_appointments as $index => $reserved)
            {
                if ($reserved['id'] == $excluded_appointment_id)
                {
                    unset($provider_appointments[$index]);
                }
            }
        }

        // Find the empty spaces on the plan. The first split between the plan is due to a break (if any). After that
        // every reserved appointment is considered to be a taken space in the plan.
        $selected_date_working_plan = $working_plan[strtolower(date('l', strtotime($selected_date)))];

        $periods = [];

        if (isset($selected_date_working_plan['breaks']))
        {
            $periods[] = [
                'start' => $selected_date_working_plan['start'],
                'end' => $selected_date_working_plan['end']
            ];

            $day_start = new DateTime($selected_date_working_plan['start']);
            $day_end = new DateTime($selected_date_working_plan['end']);

            // Split the working plan to available time periods that do not contain the breaks in them.
            foreach ($selected_date_working_plan['breaks'] as $index => $break)
            {
                $break_start = new DateTime($break['start']);
                $break_end = new DateTime($break['end']);

                if ($break_start < $day_start)
                {
                    $break_start = $day_start;
                }

                if ($break_end > $day_end)
                {
                    $break_end = $day_end;
                }

                if ($break_start >= $break_end)
                {
                    continue;
                }

                foreach ($periods as $key => $period)
                {
                    $period_start = new DateTime($period['start']);
                    $period_end = new DateTime($period['end']);

                    $remove_current_period = FALSE;

                    if ($break_start > $period_start && $break_start < $period_end && $break_end > $period_start)
                    {
                        $periods[] = [
                            'start' => $period_start->format('H:i'),
                            'end' => $break_start->format('H:i')
                        ];

                        $remove_current_period = TRUE;
                    }

                    if ($break_start < $period_end && $break_end > $period_start && $break_end < $period_end)
                    {
                        $periods[] = [
                            'start' => $break_end->format('H:i'),
                            'end' => $period_end->format('H:i')
                        ];

                        $remove_current_period = TRUE;
                    }

                    if ($break_start == $period_start && $break_end == $period_end)
                    {
                        $remove_current_period = TRUE;
                    }

                    if ($remove_current_period)
                    {
                        unset($periods[$key]);
                    }
                }
            }
        }

        // Break the empty periods with the reserved appointments.
        foreach ($provider_appointments as $provider_appointment)
        {
            foreach ($periods as $index => &$period)
            {
                $appointment_start = new DateTime($provider_appointment['start_datetime']);
                $appointment_end = new DateTime($provider_appointment['end_datetime']);
                $period_start = new DateTime($selected_date . ' ' . $period['start']);
                $period_end = new DateTime($selected_date . ' ' . $period['end']);

                if ($appointment_start <= $period_start && $appointment_end <= $period_end && $appointment_end <= $period_start)
                {
                    // The appointment does not belong in this time period, so we  will not change anything.
                }
                else
                {
                    if ($appointment_start <= $period_start && $appointment_end <= $period_end && $appointment_end >= $period_start)
                    {
                        // The appointment starts before the period and finishes somewhere inside. We will need to break
                        // this period and leave the available part.
                        $period['start'] = $appointment_end->format('H:i');
                    }
                    else
                    {
                        if ($appointment_start >= $period_start && $appointment_end < $period_end)
                        {
                            // The appointment is inside the time period, so we will split the period into two new
                            // others.
                            unset($periods[$index]);

                            $periods[] = [
                                'start' => $period_start->format('H:i'),
                                'end' => $appointment_start->format('H:i')
                            ];

                            $periods[] = [
                                'start' => $appointment_end->format('H:i'),
                                'end' => $period_end->format('H:i')
                            ];
                        }
                        else if ($appointment_start == $period_start && $appointment_end == $period_end)
                        {
                            unset($periods[$index]); // The whole period is blocked so remove it from the available periods array.
                        }
                        else
                        {
                            if ($appointment_start >= $period_start && $appointment_end >= $period_start && $appointment_start <= $period_end)
                            {
                                // The appointment starts in the period and finishes out of it. We will need to remove
                                // the time that is taken from the appointment.
                                $period['end'] = $appointment_start->format('H:i');
                            }
                            else
                            {
                                if ($appointment_start >= $period_start && $appointment_end >= $period_end && $appointment_start >= $period_end)
                                {
                                    // The appointment does not belong in the period so do not change anything.
                                }
                                else
                                {
                                    if ($appointment_start <= $period_start && $appointment_end >= $period_end && $appointment_start <= $period_end)
                                    {
                                        // The appointment is bigger than the period, so this period needs to be removed.
                                        unset($periods[$index]);
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        return array_values($periods);
    }

    /**
     * Search for any provider that can handle the requested service.
     *
     * This method will return the database ID of the provider with the most available periods.
     *
     * @param int $service_id The requested service ID.
     * @param string $selected_date The date to be searched.
     *
     * @return int Returns the ID of the provider that can provide the service at the selected date.
     */
    protected function _search_any_provider($service_id, $selected_date)
    {
        $this->load->model('providers_model');
        $this->load->model('services_model');
        $available_providers = $this->providers_model->get_available_providers();
        $service = $this->services_model->get_row($service_id);
        $provider_id = NULL;
        $max_hours_count = 0;

        foreach ($available_providers as $provider)
        {
            foreach ($provider['services'] as $provider_service_id)
            {
                if ($provider_service_id == $service_id)
                {
                    // Check if the provider is available for the requested date.
                    $empty_periods = $this->_get_provider_available_time_periods($provider['id'], $service_id,
                        $selected_date);

                    $available_hours = $this->_calculate_available_hours($empty_periods, $selected_date,
                        $service['duration'], FALSE, $service['availabilities_type']);

                    $available_hours = $this->_get_multiple_attendants_hours($selected_date, $service,
                        $provider);

                    if (count($available_hours) > $max_hours_count)
                    {
                        $provider_id = $provider['id'];
                        $max_hours_count = count($available_hours);
                    }
                }
            }
        }

        return $provider_id;
    }

    /**
     * Search for any provider that can handle the requested service.
     *
     * This method will return the database ID of the providers affected to the requested service.
     *
     * @param numeric $service_id The requested service ID.
     *
     * @return array Returns the ID of the provider that can provide the requested service.
     */
    protected function _search_providers_by_service($service_id)
    {
        $this->load->model('providers_model');
        $available_providers = $this->providers_model->get_available_providers();
        $provider_list = array();

        foreach ($available_providers as $provider)
        {
            foreach ($provider['services'] as $provider_service_id)
            {
                if ($provider_service_id === $service_id)
                {
                    // Check if the provider is affected to the selected service.
                    $provider_list[] = $provider['id'];
                }
            }
        }

        return $provider_list;
    }

    /**
     * Calculate the available appointment hours.
     *
     * Calculate the available appointment hours for the given date. The empty spaces
     * are broken down to 60 min and if the service fit in each quarter then a new
     * available hour is added to the "$available_hours" array.
     *
     * @param array $empty_periods Contains the empty periods as generated by the "_get_provider_available_time_periods"
     * method.
     * @param string $selected_date The selected date to be search (format )
     * @param int $service_duration The service duration is required for the hour calculation.
     * @param bool $manage_mode (optional) Whether we are currently on manage mode (editing an existing appointment).
     * @param string $availabilities_type Optional ('flexible'), the service availabilities type.
     *
     * @return array Returns an array with the available hours for the appointment.
     */
    protected function _calculate_available_hours(
        array $empty_periods,
        $selected_date,
        $service_duration,
        $manage_mode = FALSE,
        $availabilities_type = 'flexible'
    ) {
        $this->load->model('settings_model');

        $available_hours = [];

        foreach ($empty_periods as $period)
        {
            $start_hour = new DateTime($selected_date . ' ' . $period['start']);
            $end_hour = new DateTime($selected_date . ' ' . $period['end']);
            $interval = $availabilities_type === AVAILABILITIES_TYPE_FIXED ? (int)$service_duration : 60;

            $current_hour = $start_hour;
            $diff = $current_hour->diff($end_hour);

            while (($diff->h * 60 + $diff->i) >= intval($service_duration))
            {
                $available_hours[] = $current_hour->format('H:i');
                $current_hour->add(new DateInterval('PT' . $interval . 'M'));
                $diff = $current_hour->diff($end_hour);
            }
        }

        return $available_hours;
    }

    /**
     * [AJAX] Public function for _get_available_appointments_longrange
     *
     * @return int number of appointments left
     */
    public function get_available_appointments_longrange() {
        $service_id  = $_POST['service_id'] ?? $_GET['service_id'];
        $provider_id  = $_POST['provider_id'] ?? $_GET['provider_id'];
        $selected_date  = $_POST['selected_date'] ?? date('Y-m-d', strtotime("+1 day"));
        $days = $_GET['days'] ?? 7;

        $this->load->model('providers_model');
        $this->load->model('services_model');
        $this->load->model('appointments_model');

        $service = $this->services_model->get_row($service_id);
        $provider = $this->providers_model->get_row($provider_id);

        $available_hours = $this->appointments_model->get_available_appointments_longrange($selected_date, $service, $provider, $days);;

        $this->output
        ->set_content_type('application/json')
        ->set_output(json_encode($available_hours));
    }

    /**
     * [AJAX] Public function for _get_multiple_attendants
     *
     * @param string $selected_date The selected appointment date.
     * @param array $service Selected service data.
     * @param array $provider Selected provider data.
     *
     * @return array Returns the available hours array and available slots in that hour array.
     */
    public function ajax_get_multiple_attendants_hours() {
        $this->load->model('providers_model');
        $this->load->model('services_model');
        $service = $this->services_model->get_row($_POST['service_id']);
        $provider = $this->providers_model->get_row($_POST['provider_id']);

        $available_hours = $this->_get_multiple_attendants_hours($_POST['selected_date'], $service, $provider);


        $this->output
        ->set_content_type('application/json')
        ->set_output(json_encode($available_hours));
    }

    /**
     * Get multiple attendants hours.
     *
     * This method will add the extra appointment hours whenever a service accepts multiple attendants.
     *
     * @param string $selected_date The selected appointment date.
     * @param array $service Selected service data.
     * @param array $provider Selected provider data.
     *
     * @return array Returns the available hours array and available slots in that hour array.
     */
    protected function _get_multiple_attendants_hours(
        $selected_date,
        $service,
        $provider
    ) {
        $this->load->model('appointments_model');
        $this->load->model('services_model');
        $this->load->model('providers_model');
        $selected_date = date("Y-m-d", strtotime($selected_date));

        $unavailabilities = $this->appointments_model->get_batch([
            'is_unavailable' => TRUE,
            'DATE(start_datetime)' => $selected_date,
            'id_users_provider' => $provider['id']
        ]);

        $working_plan = json_decode($provider['settings']['working_plan'], TRUE);
        $working_day = strtolower(date('l', strtotime($selected_date)));
        $working_hours = $working_plan[$working_day];

        if ($working_hours === null) {
            return [];
        }

        $periods = [
            [
                'start' => new DateTime($selected_date . ' ' . $working_hours['start']),
                'end' => new DateTime($selected_date . ' ' . $working_hours['end'])
            ]
        ];

        $periods = $this->appointments_model->remove_breaks($selected_date, $periods, $working_hours['breaks']);
        $periods = $this->appointments_model->remove_unavailabilities($periods, $unavailabilities);

        $hours = [];

        $interval_value = $service['availabilities_type'] == AVAILABILITIES_TYPE_FIXED ? $service['duration'] : '60';
        $interval = new DateInterval('PT' . (int)$interval_value . 'M');
        $duration = new DateInterval('PT' . (int)$service['duration'] . 'M');

        foreach ($periods as $period)
        {
            $slot_start = clone $period['start'];
            $slot_end = clone $slot_start;
            $slot_end->add($duration);

            while ($slot_end <= $period['end'])
            {
                // Check reserved attendants for this time slot and see if current attendants fit.
                $appointment_attendants_number = $this->appointments_model->get_attendants_number_for_period($slot_start,
                    $slot_end, $service['id']);

                $appointment_override_number = $this->appointments_model->get_attendants_override($slot_start,
                $slot_end, $service['id']);

                // If there is an override in the database, use that instead
                if ($appointment_override_number !== false) {
                    $service_attendants_number = $appointment_override_number;
                } else {
                    $service_attendants_number = $service['attendants_number'];
                }

                if ($appointment_attendants_number < $service_attendants_number)
                {
                    $hours[] = [
                        "available_hours" => $slot_start->format('H:i'),
                        "avail_per_slot"  => $service_attendants_number - $appointment_attendants_number
                    ];

                }

                $slot_start->add($interval);
                $slot_end->add($interval);
            }
        }

        return $hours;
    }

    /**
     * Checks if we should add/update an appointment
     *
     * @param array $appointment The appointment.
     * @return bool Returns boolean if we should add/update the appointment
     */
    protected function shouldAddUpdateTime($appointment): bool {
        // skip if frontend doesn't want to update time
        return filter_var($appointment['updateTime'] ?? true, FILTER_VALIDATE_BOOLEAN);
    }
}
