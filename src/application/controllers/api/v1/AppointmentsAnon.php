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
use \EA\Engine\Api\V1\Parsers\AppointmentAnon;

/**
 * AppointmentsAnon Controller
 *
 * @package Controllers
 * @subpackage API
 */
class AppointmentsAnon extends API_V1_Controller {


    /**
     * AppointmentsAnon Resource Parser
     *
     * @var \EA\Engine\Api\V1\Parsers\AppointmentAnon
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
        $this->parser = new AppointmentAnon();
    }


    /**
     * Get metrics used by dashboard
     *
     * Query Params:
     * provider_id
     * service_id
     * date_start (optional) format: YYYY-MM-DD
     * date_end (optional) format: YYYY-MM-DD
     *
     * http://localhost/api/v1/appointmentsanon?provider_id=2&service_id=1&date_start=2020-04-02
     * http://localhost/api/v1/appointmentsanon?provider_id=2&service_id=1
     */
    public function get()
    {
        // Required inputs
        $inputServiceId = intval($_GET['service_id'] ?? -1);
        $inputProviderId = intval($_GET['provider_id'] ?? -1);
        // Optional inputs
        $inputDateStart = $_GET['date_start'] ?? null;
        $inputDateEnd = $_GET['date_end'] ?? null;

        // Set vars
        $dateStart = null;
        $dateEnd = null;
        $data = [];

        try {
            if ($inputDateStart !== null) {
                $dateStart = new DateTime($inputDateStart);
            }
        } catch (Exception $e) {}
        try {
            if ($inputDateEnd !== null && $dateStart !== null) {
                $dateEnd = new DateTime($inputDateEnd);
            }
        } catch (Exception $e) {}

        // Get models
        $service = $this->services_model->get_row($inputServiceId);
        $provider = $this->providers_model->get_row($inputProviderId);

        // Fetch data
        $data = $this->appointments_model->get_created_ranged_appointments($provider['id'], $service['id'], $dateStart, $dateEnd);

        try
        {
            $response = new Response($data);

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
