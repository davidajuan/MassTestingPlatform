<?php defined('BASEPATH') or exit('No direct script access allowed');

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
use \EA\Engine\Api\V1\Response;
use \EA\Engine\Api\V1\Parsers\BusinessSearch as ParserBusinessSearch ;
use JasonGrimes\Paginator;

/**
 * Backend Controller
 *
 * @package Controllers
 */
class Backend extends CI_Controller
{
    /**
     * Class Constructor
     */
    public function __construct()
    {
        parent::__construct();
        $this->load->library('session');
        $this->load->helper('privilege');

        // Set user's selected language.
        if ($this->session->userdata('language')) {
            $this->config->set_item('language', $this->session->userdata('language'));
            $this->lang->load('translations', $this->session->userdata('language'));
        } else {
            $this->lang->load('translations', $this->config->item('language')); // default
        }
    }

    /**
     * Display the backend to book appointments.
     *
     * In this page the user has a wizard form for adding a user
     */
    public function index()
    {
        // get an appointment hash, for edit
        $appointment_hash = $this->input->get('appointment_hash');
        $patient_id = $this->input->get('patient_id');

        // used for notifications
        $wasDeleted = $this->input->get('wasDeleted');

        $this->session->set_userdata('dest_url', site_url('backend'));

        // check if they don't have access to appointments, if they don't take them to patients
        if (!_has_privileges(PRIV_APPOINTMENTS)) {
            return;
        }

        $this->load->model('appointments_model');
        $this->load->model('providers_model');
        $this->load->model('user_model');
        $this->load->model('services_model');
        $this->load->model('customers_model');
        $this->load->model('settings_model');

        try {
            $available_services = $this->services_model->get_available_services();
            $available_providers = $this->providers_model->get_available_providers();
            $company_name = $this->settings_model->get_setting('company_name');
            $date_format = $this->settings_model->get_setting('date_format');
            $time_format = $this->settings_model->get_setting('time_format');

            // Remove the data that are not needed inside the $available_providers array.
            foreach ($available_providers as $index => $provider) {
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
            if ($appointment_hash) {
                // Load the appointments data and enable the manage mode of the page.
                $manage_mode = TRUE;

                $results = $this->appointments_model->get_batch(['hash' => $appointment_hash]);

                if (count($results) === 0) {
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
                $appointment['city_worker'] = $appointment['business_code'] === Config::BUSINESS_CODE_CITY_WORKER ? 1 : 0;

                $customer_token = md5(uniqid(mt_rand(), true));

                $this->load->driver('cache', ['adapter' => 'file']);

                $this->session->set_tempdata('customer-token-' . $customer_token, $customer['id'], 600);
                // $this->cache->save('customer-token-' . $customer_token, $customer['id'], 600); // save for 10 minutes
            } else {
                // The customer is going to book a new appointment so there is no
                // need for the manage functionality to be initialized.
                $manage_mode = FALSE;
                $customer_token = FALSE;
                $appointment = [];
                // on inital load assume it is the first provider
                $provider = $this->providers_model->get_row($available_providers[0]['id']);

                // prefill patient data, if we have it
                $customer = $patient_id ? $this->customers_model->get_row($patient_id, 'patient_id') : [];
            }

            // API key to expose to the front end
            $google_api_key = Config::GOOGLE_MAPS_API_KEY;

            $user = $this->user_model->get_settings($provider['id']);
            $business_service_id = $user['settings']['business_service_id'] ?? null;
            $priority_service_id = $user['settings']['priority_service_id'] ?? null;

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
                'wasDeleted'  => $wasDeleted,
                'google_api_key' => $google_api_key,
                'business_service_id' => $business_service_id,
                'priority_service_id' => $priority_service_id,
                'base_url' => $this->config->item('base_url'),
                'user_display_name' => $this->user_model->get_user_display_name($this->session->userdata('user_id')),
                'active_menu' => PRIV_BOOK,
            ];
        } catch (Exception $exc) {
            $view['exceptions'][] = $exc;
        }

        $this->set_user_data($view);

        $this->load->view('backend/header', $view);
        $this->load->view('backend/book', $view);
        $this->load->view('backend/footer', $view);
    }

    /**
     * Display the backend to book appointments.
     *
     * In this page the user has a wizard form for adding a user
     */
    public function book_success($appointment_id)
    {
        $manage_mode = $this->input->get('manage_mode');
        $this->session->set_userdata('dest_url', site_url('backend/book_success'));

        // use this priv for now.. we don't want to change too manything things in db
        if (!_has_privileges(PRIV_CUSTOMERS)) {
            return;
        }

        // If the appointment id doesn't exist or zero redirect to index.
        if (!$appointment_id) {
            redirect('backend/book');
        }

        $this->load->model('user_model');
        $this->load->model('customers_model');
        $this->load->model('appointments_model');
        $this->load->model('providers_model');
        $this->load->model('services_model');
        $this->load->model('settings_model');

        //retrieve the data needed in the view
        $appointment = $this->appointments_model->get_row($appointment_id);


        $provider = $this->providers_model->get_row($appointment['id_users_provider']);
        $service = $this->services_model->get_row($appointment['id_services']);
        $customer = $this->customers_model->get_row($appointment['id_users_customer']);
        $company_name = $this->settings_model->get_setting('company_name');

        //get the exceptions
        $exceptions = $this->session->flashdata('book_success');
        // :: LOAD THE BOOK SUCCESS VIEW
        $view = [
            'appointment_data' => $appointment,
            'provider_data' => $provider,
            'service_data' => $service,
            'customer_data' => $customer,
            'company_name' => $company_name,
            'manage_mode' => $manage_mode,
            'base_url' => $this->config->item('base_url'),
            'user_display_name' => $this->user_model->get_user_display_name($this->session->userdata('user_id')),
            'active_menu' => PRIV_BOOK,
        ];
        if ($exceptions) {
            $view['exceptions'] = $exceptions;
        }

        $this->set_user_data($view);

        $this->load->view('backend/header', $view);
        $this->load->view('backend/book_success', $view);
        $this->load->view('backend/footer', $view);
    }

    // may change to a unique url with a customer id later
    public function upload_csv($customer_id = '')
    {
        // use this priv for now.. we don't want to change too manything things in db
        if (!_has_privileges(PRIV_CUSTOMERS)) {
            return;
        }

        $customer_id = $customer_id ? $customer_id : 1;
        $this->session->set_userdata('dest_url', site_url('backend/upload_csv'));
        // use this priv for now.. we don't want to change too manything things in db
        if (!_has_privileges(PRIV_CUSTOMERS)) {
            return;
        }
        $this->load->model('user_model');
        $this->load->model('customers_model');
        $this->load->model('appointments_model');
        $this->load->model('providers_model');
        $this->load->model('services_model');
        $this->load->model('settings_model');

        $available_services = $this->services_model->get_available_services();
        $available_providers = $this->providers_model->get_available_providers();

        $customer = $this->customers_model->get_row($customer_id);
        $company_name = $this->settings_model->get_setting('company_name');
        //get the exceptions
        $exceptions = $this->session->flashdata('upload_csv');
        // :: LOAD THE BOOK SUCCESS VIEW
        $view = [
            'customer_data' => $customer,
            'company_name' => $company_name,
            'available_services' => $available_services,
            'available_providers' => $available_providers,
            'base_url' => $this->config->item('base_url'),
            'user_display_name' => $this->user_model->get_user_display_name($this->session->userdata('user_id')),
            'active_menu' => $this->uri->uri_string(),
        ];
        if ($exceptions) {
            $view['exceptions'] = $exceptions;
        }
        $this->set_user_data($view);
        $this->load->view('backend/header', $view);
        $this->load->view('backend/upload_csv', $view);
        $this->load->view('backend/footer', $view);
    }

    /**
     * Display the backend business page.
     *
     * In this page the user has a form to add a business capacity.
     */
    public function business()
    {

        $this->session->set_userdata('dest_url', site_url('backend/business'));
        // use this priv for now.. we don't want to change too manything things in db
        if (!_has_privileges(PRIV_BUSINESS)) {
            return;
        }

        $hash = $this->input->get('hash');
        $manage_mode = false;

        $this->load->model('user_model');
        $this->load->model('settings_model');
        $this->load->model('business_model');
        $this->load->model('appointments_model');
        $this->load->model('business_request_model');

        if ($hash) {
            $manage_mode = true;

            $results = $this->business_model->get_batch(['hash' => $hash]);

            if (count($results) === 0) {
                // The requested business doesn't exist in the database.
                $view = [
                    'message_title' => $this->lang->line('business_not_found'),
                    'message_text' => $this->lang->line('business_does_not_exist_in_db'),
                    'message_icon' => base_url('assets/img/error.png')
                ];
                $this->load->view('appointments/message', $view);
                return;
            }
            $business = $results[0];

            // get all requests for business
            $business_requests = $this->business_request_model->get_batch(['id_business' => $business['id']]);

            foreach ($business_requests as $key => $business_request) {
                $business_requests[$key]['total_slots_used'] = $this->appointments_model->count(['business_code' => $business_request['business_code']]);
            }
        }

        // if it was a post, try adding the business
        if (!empty($_POST)) {
            $manage_mode = isset($_POST['id']) ? true : false;
            $this->handleBusinessPost($_POST, $manage_mode);
            return;
        }

        $company_name = $this->settings_model->get_setting('company_name');
        //get the exceptions
        $exceptions = $this->session->flashdata('business');

        // API key to expose to the front end
        $google_api_key = Config::GOOGLE_MAPS_API_KEY;

        // :: LOAD THE BOOK SUCCESS VIEW
        $view = [
            'company_name' => $company_name,
            'base_url' => $this->config->item('base_url'),
            'user_display_name' => $this->user_model->get_user_display_name($this->session->userdata('user_id')),
            'google_api_key' => $google_api_key,
            'active_menu' => PRIV_BUSINESS,
            'business_requests' => $business_requests ?? null,
            'business' => $business ?? null,
            'manage_mode' => $manage_mode

        ];
        if ($exceptions) {
            $view['exceptions'] = $exceptions;
        }
        $this->set_user_data($view);
        $this->load->view('backend/header', $view);
        $this->load->view('backend/business', $view);
        $this->load->view('backend/footer', $view);
    }

   /**
     * Display the backend reports page.
     *
     * In this page the user has options to generate various reports.
     */
    public function reports()
    {
        $this->session->set_userdata('dest_url', site_url('backend/reports'));

        // TODO: Add permissions of reports and then change this to be reports
        if (!_has_privileges(PRIV_BUSINESS)) {
            return;
        }

        $this->load->model('user_model');
        $this->load->model('settings_model');
        $this->load->model('appointments_model');
        $this->load->model('providers_model');
        $this->load->model('services_model');

        $available_services = $this->services_model->get_available_services();
        $available_providers = $this->providers_model->get_available_providers();

        $company_name = $this->settings_model->get_setting('company_name');
        //get the exceptions
        $exceptions = $this->session->flashdata('reports');

       // :: LOAD THE REPORTS VIEW
        $view = [
            'available_services' => $available_services,
            'available_providers' => $available_providers,
            'company_name' => $company_name,
            'base_url' => $this->config->item('base_url'),
            'user_display_name' => $this->user_model->get_user_display_name($this->session->userdata('user_id')),
            'active_menu' => PRIV_REPORTS,
            'reports' => $reports ?? null,

        ];
        if ($exceptions) {
            $view['exceptions'] = $exceptions;
        }

        $this->set_user_data($view);
        $this->load->view('backend/header', $view);
        $this->load->view('backend/reports', $view);
        $this->load->view('backend/footer', $view);
    }


    /**
     * Handle add/edit of business
     *
     * @param [type] $postData
     * @param boolean $manage_mode
     * @return void
     */
    protected function handleBusinessPost($postData, bool $manage_mode) : void
    {
        $business = $postData;
        $business_request_id = null;

        // clean data
        $business['consent_sms'] = isset($business['consent_sms']) &&  $business['consent_sms'] === 'on' ? '1' : '0';
        $business['consent_email'] =  isset($business['consent_email']) && $business['consent_email'] === 'on' ? '1' : '0';
        unset($business['slots_requested']);

        $this->db->trans_begin();
        $id_business = $this->business_model->add($business);

        // right now manage mode is done transactions
        if ($manage_mode) {
            $this->db->trans_commit();
        }

        // don't try adding a new business request if it's just edit mode
        if (!$manage_mode && $id_business) {
            $business_request = [
                'id_business' => $id_business,
                'slots_requested' => $postData['slots_requested'],
            ];

            $business_request_id = $this->business_request_model->add($business_request, $business['business_name']);
        }

        if ($business_request_id) {
            $this->db->trans_commit();

            // :: Send email notification to business
            try {
                $this->load->library('ses_email');
                $email = new \EA\Engine\Notifications\Email($this, $this->config->config, $this->ses_email);
                $email->sendBusinessCreatedDetails($business, $business_request, Config::SES_EMAIL_ADDRESS, Config::SES_EMAIL_NAME);
            } catch (Exception $exc) {
                log_message('error', $exc->getMessage());
                log_message('error', $exc->getTraceAsString());
            }

            // :: Send SMS notification to business
            try {
                $this->load->library('sns_sms');
                $sms = new \EA\Engine\Notifications\Sms($this, $this->config->config, $this->sns_sms);
                $sms->sendBusinessCreatedDetails($business);
            } catch (Exception $exc) {
                log_message('error', $exc->getMessage());
                log_message('error', $exc->getTraceAsString());
            }

        } else {
            $this->db->trans_rollback();
            $response = [
                'error' => "There was an error inserting into database."
            ];
        }

        $id = $manage_mode ? $business['id'] : $id_business;

        $response = [
            'id' => $id,
            'r_id' => $business_request_id ?? '',
            'manage_mode' => $manage_mode
        ];
        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($response));
    }

    /**
     * Success page for adding a business
     */
    public function business_success($id, $r_id = null)
    {
        $this->session->set_userdata('dest_url', site_url('backend/business_success'));
        $manage_mode = $this->input->get('manage_mode');

        // use this priv for now.. we don't want to change too manything things in db
        if (!_has_privileges(PRIV_BUSINESS)) {
            return;
        }

        // If the appointment id doesn't exist or zero redirect to index.
        if (!$id) {
            redirect('backend/business');
        }
        $this->load->model('user_model');
        $this->load->model('business_model');
        $this->load->model('business_request_model');
        $this->load->model('settings_model');

        //retrieve the data needed in the view
        $business = $this->business_model->get_row($id);

        $business_request = $r_id ? $this->business_request_model->get_row($r_id) : null;

        $company_name = $this->settings_model->get_setting('company_name');

        //get the exceptions
        $exceptions = $this->session->flashdata('business_success');
        $view = [
            'business' => $business,
            'business_request' => $business_request ?? null,
            'company_name' => $company_name,
            'base_url' => $this->config->item('base_url'),
            'user_display_name' => $this->user_model->get_user_display_name($this->session->userdata('user_id')),
            'active_menu' => PRIV_BUSINESS,
            'manage_mode' => $manage_mode,
        ];
        if ($exceptions) {
            $view['exceptions'] = $exceptions;
        }

        $this->set_user_data($view);

        $this->load->view('backend/header', $view);
        $this->load->view('backend/business_success', $view);
        $this->load->view('backend/footer', $view);
    }

    /**
     * Display the backend business request page.
     *
     * In this page the user can approve or deny a business request.
     */
    public function business_request()
    {

        $this->session->set_userdata('dest_url', site_url('backend/business_request'));
        // use this priv for now.. we don't want to change too manything things in db
        if (!_has_privileges(PRIV_BUSINESS_REQUEST)) {
            return;
        }

        // Get params
        $status = $this->input->get('status');
        $query = $this->input->get('query');
        $page = $this->input->get('page') ?? 1;
        $rows_per_page = $this->input->get('length') ?? 50;
        // need to set it back to GET for our EA pagination processes
        $_GET['length'] = $rows_per_page;
        $_GET['page'] = $page;

        $this->load->model('user_model');
        $this->load->model('settings_model');
        $this->load->model('business_model');
        $this->load->model('appointments_model');
        $this->load->model('business_request_model');
        $this->load->library('pagination');

        // Get businesses
        $businesses = $this->business_request_model->search_business($query, $status);

        // get query params to carry over
        // Todo: Need to find a better way to do this

        foreach ($businesses as $key => $business) {
            $businesses[$key]['total_slots_used'] = $this->appointments_model->count(['business_code' => $business['business_code']]);
        }

        $new_params = [];
        parse_str($_SERVER["QUERY_STRING"], $output);
        unset($output['page']);
        foreach ($output as $key => $val) {
            $new_params[] = $key . '=' .$val;
        }

        $new_params = implode('&', $new_params);

        // Pagination
        $urlPattern = '/backend/business_request?page=(:num)' . ($new_params ? '&' . $new_params : '');

        $totalItems = count($businesses);
        $itemsPerPage = (int) $rows_per_page;
        $currentPage = $this->input->get('page') ?? 1;

        $pagination = new Paginator($totalItems, $itemsPerPage, $currentPage, $urlPattern);

        // Used for sorting and paginating results
        $responseBusinesses = new Response($businesses ?? []);
        $parsedBusinesses = $responseBusinesses
            ->encode(new ParserBusinessSearch())
            ->sort()
            ->paginate()
            ->minimize()
            ->getResponse();

        $company_name = $this->settings_model->get_setting('company_name');
        //get the exceptions
        $exceptions = $this->session->flashdata('business_request');

        $view = [
            'company_name' => $company_name,
            'base_url' => $this->config->item('base_url'),
            'user_display_name' => $this->user_model->get_user_display_name($this->session->userdata('user_id')),
            'active_menu' => PRIV_BUSINESS_REQUEST,
            'businesses' => $parsedBusinesses ?? null,
            'pagination' => $pagination

        ];
        if ($exceptions) {
            $view['exceptions'] = $exceptions;
        }
        $this->set_user_data($view);
        $this->load->view('backend/header', $view);
        $this->load->view('backend/business_request', $view);
        $this->load->view('backend/footer', $view);
    }

    /**
     * Display the main backend page.
     *
     * This method displays the main backend page. All users login permission can view this page which displays a
     * calendar with the events of the selected provider or service. If a user has more privileges he will see more
     * menus at the top of the page.
     *
     * @param string $appointment_hash Appointment edit dialog will appear when the page loads (default '').
     */
    public function calendar($appointment_hash = '')
    {
        $this->session->set_userdata('dest_url', site_url('backend/calendar'));

        if (!_has_privileges(PRIV_APPOINTMENTS)) {
            return;
        }

        $this->load->model('appointments_model');
        $this->load->model('providers_model');
        $this->load->model('services_model');
        $this->load->model('customers_model');
        $this->load->model('settings_model');
        $this->load->model('roles_model');
        $this->load->model('user_model');
        $this->load->model('secretaries_model');

        $view['base_url'] = $this->config->item('base_url');
        $view['user_display_name'] = $this->user_model->get_user_display_name($this->session->userdata('user_id'));
        $view['active_menu'] = PRIV_APPOINTMENTS;
        $view['book_advance_timeout'] = $this->settings_model->get_setting('book_advance_timeout');
        $view['date_format'] = $this->settings_model->get_setting('date_format');
        $view['time_format'] = $this->settings_model->get_setting('time_format');
        $view['company_name'] = $this->settings_model->get_setting('company_name');
        $view['available_providers'] = $this->providers_model->get_available_providers();
        $view['available_services'] = $this->services_model->get_available_services();
        $view['customers'] = $this->customers_model->get_batch();
        $user = $this->user_model->get_settings($this->session->userdata('user_id'));
        $view['calendar_view'] = $user['settings']['calendar_view'];
        $this->set_user_data($view);

        if ($this->session->userdata('role_slug') === DB_SLUG_SECRETARY) {
            $secretary = $this->secretaries_model->get_row($this->session->userdata('user_id'));
            $view['secretary_providers'] = $secretary['providers'];
        } else {
            $view['secretary_providers'] = [];
        }

        $results = $this->appointments_model->get_batch(['hash' => $appointment_hash]);

        if ($appointment_hash !== '' && count($results) > 0) {
            $appointment = $results[0];
            $appointment['customer'] = $this->customers_model->get_row($appointment['id_users_customer']);
            $view['edit_appointment'] = $appointment; // This will display the appointment edit dialog on page load.
        } else {
            $view['edit_appointment'] = NULL;
        }

        $this->load->view('backend/header', $view);
        $this->load->view('backend/calendar', $view);
        $this->load->view('backend/footer', $view);
    }

    /**
     * Display the backend customers page.
     *
     * In this page the user can manage all the customer records of the system.
     */
    public function customers()
    {
        $this->session->set_userdata('dest_url', site_url('backend/customers'));

        if (!_has_privileges(PRIV_CUSTOMERS)) {
            return;
        }

        $this->load->model('providers_model');
        $this->load->model('customers_model');
        $this->load->model('secretaries_model');
        $this->load->model('services_model');
        $this->load->model('settings_model');
        $this->load->model('user_model');

        $view['base_url'] = $this->config->item('base_url');
        $view['user_display_name'] = $this->user_model->get_user_display_name($this->session->userdata('user_id'));
        $view['active_menu'] = PRIV_CUSTOMERS;
        $view['company_name'] = $this->settings_model->get_setting('company_name');
        $view['date_format'] = $this->settings_model->get_setting('date_format');
        $view['time_format'] = $this->settings_model->get_setting('time_format');
        $view['customers'] = $this->customers_model->get_batch();
        $view['available_providers'] = $this->providers_model->get_available_providers();
        $view['available_services'] = $this->services_model->get_available_services();

        if ($this->session->userdata('role_slug') === DB_SLUG_SECRETARY) {
            $secretary = $this->secretaries_model->get_row($this->session->userdata('user_id'));
            $view['secretary_providers'] = $secretary['providers'];
        } else {
            $view['secretary_providers'] = [];
        }

        $this->set_user_data($view);

        $this->load->view('backend/header', $view);
        $this->load->view('backend/customers', $view);
        $this->load->view('backend/footer', $view);
    }

    /**
     * Displays the backend services page.
     *
     * Here the admin user will be able to organize and create the services that the user will be able to book
     * appointments in frontend.
     *
     * NOTICE: The services that each provider is able to service is managed from the backend services page.
     */
    public function services()
    {
        $this->session->set_userdata('dest_url', site_url('backend/services'));

        if (!_has_privileges(PRIV_SERVICES)) {
            return;
        }

        $this->load->model('customers_model');
        $this->load->model('services_model');
        $this->load->model('settings_model');
        $this->load->model('user_model');

        $view['base_url'] = $this->config->item('base_url');
        $view['user_display_name'] = $this->user_model->get_user_display_name($this->session->userdata('user_id'));
        $view['active_menu'] = PRIV_SERVICES;
        $view['company_name'] = $this->settings_model->get_setting('company_name');
        $view['date_format'] = $this->settings_model->get_setting('date_format');
        $view['time_format'] = $this->settings_model->get_setting('time_format');
        $view['services'] = $this->services_model->get_batch();
        $view['categories'] = $this->services_model->get_all_categories();
        $this->set_user_data($view);

        $this->load->view('backend/header', $view);
        $this->load->view('backend/services', $view);
        $this->load->view('backend/footer', $view);
    }

    /**
     * Display the backend users page.
     *
     * In this page the admin user will be able to manage the system users. By this, we mean the provider, secretary and
     * admin users. This is also the page where the admin defines which service can each provider provide.
     */
    public function users()
    {
        $this->session->set_userdata('dest_url', site_url('backend/users'));

        if (!_has_privileges(PRIV_USERS)) {
            return;
        }

        $this->load->model('providers_model');
        $this->load->model('secretaries_model');
        $this->load->model('admins_model');
        $this->load->model('services_model');
        $this->load->model('settings_model');
        $this->load->model('user_model');

        $view['base_url'] = $this->config->item('base_url');
        $view['user_display_name'] = $this->user_model->get_user_display_name($this->session->userdata('user_id'));
        $view['active_menu'] = PRIV_USERS;
        $view['company_name'] = $this->settings_model->get_setting('company_name');
        $view['date_format'] = $this->settings_model->get_setting('date_format');
        $view['time_format'] = $this->settings_model->get_setting('time_format');
        $view['admins'] = $this->admins_model->get_batch();
        $view['providers'] = $this->providers_model->get_batch();
        $view['secretaries'] = $this->secretaries_model->get_batch();
        $view['services'] = $this->services_model->get_batch();
        $view['working_plan'] = $this->settings_model->get_setting('company_working_plan');
        $this->set_user_data($view);

        $this->load->view('backend/header', $view);
        $this->load->view('backend/users', $view);
        $this->load->view('backend/footer', $view);
    }

    /**
     * Display the user/system settings.
     *
     * This page will display the user settings (name, password etc). If current user is an administrator, then he will
     * be able to make change to the current Easy!Appointment installation (core settings like company name, book
     * timeout etc).
     */
    public function settings()
    {
        $this->session->set_userdata('dest_url', site_url('backend/settings'));
        if (
            !_has_privileges(PRIV_SYSTEM_SETTINGS, FALSE)
            && !_has_privileges(PRIV_USER_SETTINGS)
        ) {
            return;
        }

        $this->load->model('settings_model');
        $this->load->model('user_model');

        $this->load->library('session');
        $user_id = $this->session->userdata('user_id');

        $view['base_url'] = $this->config->item('base_url');
        $view['user_display_name'] = $this->user_model->get_user_display_name($user_id);
        $view['active_menu'] = PRIV_SYSTEM_SETTINGS;
        $view['company_name'] = $this->settings_model->get_setting('company_name');
        $view['date_format'] = $this->settings_model->get_setting('date_format');
        $view['time_format'] = $this->settings_model->get_setting('time_format');
        $view['role_slug'] = $this->session->userdata('role_slug');
        $view['system_settings'] = $this->settings_model->get_settings();
        $view['user_settings'] = $this->user_model->get_settings($user_id);
        $this->set_user_data($view);

        $this->load->view('backend/header', $view);
        $this->load->view('backend/settings', $view);
        $this->load->view('backend/footer', $view);
    }

    /**
     * Set the user data in order to be available at the view and js code.
     *
     * @param array $view Contains the view data.
     */
    protected function set_user_data(&$view)
    {
        $this->load->model('roles_model');

        // Get privileges
        $view['user_id'] = $this->session->userdata('user_id');
        $view['user_email'] = $this->session->userdata('user_email');
        $view['role_slug'] = $this->session->userdata('role_slug');
        $view['privileges'] = $this->roles_model->get_privileges($this->session->userdata('role_slug'));
    }
}
