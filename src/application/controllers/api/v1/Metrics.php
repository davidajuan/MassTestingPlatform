<?php defined('BASEPATH') OR exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * Easy!Appointments - Open Source Web Scheduler
 *
 * @package     EasyAppointments
 * @author      A.Tselegidis <alextselegidis@gmail.com>
 * @copyright   Copyright (c) 2013 - 2018, Alex Tselegidis
 * @license     http://opensource.org/licenses/GPL-3.0 - GPLv3
 * @link        http://easyappointments.org
 * @since       v1.2.0
 * ---------------------------------------------------------------------------- */

require_once __DIR__ . '/API_V1_Controller.php';

use \EA\Engine\Api\V1\Response;
use \EA\Engine\Api\V1\Parsers\Metrics as MetricsParser;

/**
 * Appointments Controller
 *
 * @package Controllers
 * @subpackage API
 */
class Metrics extends API_V1_Controller {


    /**
     * Appointments Resource Parser
     *
     * @var \EA\Engine\Api\V1\Parsers\Metrics
     */
    protected $parser;

    /**
     * Class Constructor
     */
    public function __construct()
    {
        parent::__construct();
        $this->load->model('appointments_model');
        $this->load->model('providers_model');
        $this->load->model('services_model');

        $this->load->model('business_model');
        $this->load->model('business_request_model');
        $this->load->model('customers_model');
        $this->parser = new MetricsParser;
    }


    /**
     * Get metrics used by dashboard
     *
     * Query Params:
     * service_id
     * provider_id
     * date (optional) format: YYYY-MM-DD
     *
     * http://localhost/api/v1/metrics?service_id=1&provider_id=2&date=2020-04-02
     * http://localhost/api/v1/metrics?service_id=1&provider_id=2
     *
     */
    public function get()
    {
        // Required inputs
        $inputServiceId = intval($_GET['service_id'] ?? -1);
        $inputProviderId = intval($_GET['provider_id'] ?? -1);
        // Optional inputs
        $inputDate = $_GET['date'] ?? 'now';

        // Set vars
        $dateCurr = new DateTime($inputDate);
        $data = [];
        // Setup date ranges
        $apptTomorrowStart = clone $dateCurr;
        $apptTomorrowStart->add(date_interval_create_from_date_string('1 day')); // Can't book today's appointments
        $apptTomorrowStart->setTime(0, 0, 0); // Set to very beginning of the day
        $apptTomorrowEnd = clone $apptTomorrowStart;
        $apptTomorrowEnd->setTime(23, 59, 59);

        $apptWeekEnd = clone $apptTomorrowStart;
        $apptWeekEnd->add(date_interval_create_from_date_string('7 days')); // Look forward 7 days;
        $apptWeekEnd->setTime(23, 59, 59); // Set to very end of the day

        // Get models
        $service = $this->services_model->get_row($inputServiceId);
        $provider = $this->providers_model->get_row($inputProviderId);

        $user = $this->user_model->get_settings($inputProviderId);
        $business_service_id = $user['settings']['business_service_id'] ?? null;
        $priority_service_id = $user['settings']['priority_service_id'] ?? null;

        // Get Slot Counts
        $slotCounts = $this->business_request_model->get_slot_counts();

        $allServiceIds = [$service['id'], $business_service_id, $priority_service_id];

        // Aggregate data
        // Count of appointments scheduled today (this would be the appointments created today, not the appointments that are OCCURRING today)
        $data[MetricsParser::KEY_SCHEDULED_TODAY] = $this->appointments_model->count_created_appointments($dateCurr, $allServiceIds);;
        // Number of remaining appointments for 7 day window
        $data[MetricsParser::KEY_AVAILABLE_TOTAL] = $this->appointments_model->get_available_appointments_longrange($dateCurr->format('Y-m-d'), $service, $provider, 7, false)['appointments_remaining'];
        // Count of appointments scheduled today by providers
        $data[MetricsParser::KEY_SCHEDULED_TODAY_PROVIDER] = $this->appointments_model->count_created_appointments($dateCurr, [$service['id']], ['ea_users.caller' => CALLER_TYPE_PROVIDER]);
        // Count of appointments scheduled today by patients
        $data[MetricsParser::KEY_SCHEDULED_TODAY_PATIENT] = $this->appointments_model->count_created_appointments($dateCurr, [$service['id']], ['ea_users.caller' => CALLER_TYPE_PATIENT]);
        // Count of appointments scheduled today by CIE
        $data[MetricsParser::KEY_SCHEDULED_TODAY_CIE] = $this->appointments_model->count_created_appointments($dateCurr, [$business_service_id, $priority_service_id], ['ea_users.caller' => CALLER_TYPE_CIE]);
        // Count of appointments are scheduled/booked for tomorrow
        $data[MetricsParser::KEY_BOOKED_TOMORROW] = $this->appointments_model->count_scheduled_appointments($apptTomorrowStart, $apptTomorrowEnd, $allServiceIds);
        // Count of appointments are scheduled/booked for the next 7 days
        $data[MetricsParser::KEY_BOOKED_TOTAL] = $this->appointments_model->count_scheduled_appointments($apptTomorrowStart, $apptWeekEnd, $allServiceIds);

        // Current dates Business Information
        // # Of unique businesses registered
        $data[MetricsParser::KEY_TODAY_BUSINESS_REGISTERED] = $this->business_model->count(['DATE(created)' => $dateCurr->format('Y-m-d')]);
        // total # of appointments requested
        $data[MetricsParser::KEY_TODAY_APPOINTMENT_REQUESTED] = $this->business_request_model->sumByField('slots_requested', ['DATE(created)' => $dateCurr->format('Y-m-d')]);
        // total # of appointments approved
        $data[MetricsParser::KEY_TODAY_APPOINTMENT_APPROVED] = $this->business_request_model->sumByField('slots_approved', ['status' => DB_SLUG_BUSINESS_REQ_ACTIVE, 'DATE(created)' => $dateCurr->format('Y-m-d')]);

        // Total Business Information
        // # Of unique businesses registered
        $data[MetricsParser::KEY_BUSINESS_REGISTERED] = $this->business_model->count();
        // total # of appointments requested
        $data[MetricsParser::KEY_APPOINTMENT_REQUESTED] = $this->business_request_model->sumByField('slots_requested');
        // total # of appointments approved
        $data[MetricsParser::KEY_APPOINTMENT_APPROVED] = $this->business_request_model->sumByField('slots_approved', ['status' => DB_SLUG_BUSINESS_REQ_ACTIVE]);
        // total # of CIEs scheduled
        $data[MetricsParser::KEY_PATIENT_CIE_SCHEDULED] = $this->customers_model->count(['caller' => CALLER_TYPE_CIE]);


        $data[MetricsParser::KEY_BUSINESS_SLOT_REMAINING]   = 0;
        $data[MetricsParser::KEY_BUSINESS_SLOT_OCCUPIED]    = 0;
        $data[MetricsParser::KEY_BUSINESS_CODE_APPROVED]    = 0;
        $data[MetricsParser::KEY_BUSINESS_CODE_PENDING]     = 0;
        $data[MetricsParser::KEY_BUSINESS_CODE_DENIED]      = 0;
        foreach ($slotCounts as $business_id => $counts) {
            $data[MetricsParser::KEY_BUSINESS_SLOT_REMAINING]   += $counts['slots_remaining'];
            $data[MetricsParser::KEY_BUSINESS_SLOT_OCCUPIED]    += $counts['slots_occupied'];
            $data[MetricsParser::KEY_BUSINESS_CODE_APPROVED]    += $counts['codes_approved'];
            $data[MetricsParser::KEY_BUSINESS_CODE_PENDING]     += $counts['codes_pending'];
            $data[MetricsParser::KEY_BUSINESS_CODE_DENIED]      += $counts['codes_denied'];
        }

        try
        {
            $response = new Response([$data]);

            $response->encode($this->parser)
                ->search()
                ->sort()
                ->paginate()
                ->minimize()
                ->output();
        }
        catch (\Exception $exception)
        {
            exit($this->_handleException($exception));
        }
    }
}
