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

require_once __DIR__ . '/../API_V1_Controller.php';

use \EA\Engine\Api\V1\Response;

/**
 * Appointments Controller
 *
 * @package Controllers
 * @subpackage API
 */
class RemainingAppointments extends API_V1_Controller {
    /**
     * Appointments Resource Parser
     *
     * @var \EA\Engine\Api\V1\Parsers\RemainingAppointments
     */
    protected $parser;

    /**
     * Class Constructor
     */
    public function __construct()
    {
        parent::__construct();
        $this->load->model('appointments_model');
        $this->parser = new \EA\Engine\Api\V1\Parsers\RemainingAppointments;
    }

    public function get()
    {
        $service_id  = $_GET['service_id'];
        $provider_id  = $_GET['provider_id'];
        $selected_date = date('Y-m-d', strtotime("+1 day"));
        $days  = $_GET['days'] ?? 7;

        $this->load->model('providers_model');
        $this->load->model('services_model');

        $service = $this->services_model->get_row($service_id);
        $provider = $this->providers_model->get_row($provider_id);

        try
        {
            $app_data = $this->appointments_model->get_available_appointments_longrange($selected_date, $service, $provider, $days);

            $response = new Response([$app_data]);

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
