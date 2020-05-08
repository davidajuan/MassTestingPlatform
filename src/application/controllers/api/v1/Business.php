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
use \EA\Engine\Api\V1\Parsers\Business as ParserBusiness;

/**
 * Business Controller
 *
 * @package Controllers
 * @subpackage API
 */
class Business extends API_V1_Controller {
    /**
     * Class Constructor
     */
    public function __construct()
    {
        parent::__construct();
        $this->load->model('business_model');
        $this->load->model('business_request_model');
    }

    /**
     * List all businesses
     */
    public function list()
    {
        try
        {
            // Get businesses
            $businesses = $this->business_model->get_batch();

            // Get Counts
            $slotCounts = $this->business_request_model->get_slot_counts();

            foreach ($businesses as $idx => $business) {
                // Adding slot counts
                $slots = $slotCounts[$business['id']] ?? [];
                $businesses[$idx] = array_merge($business, $slots);
            }

            $response = new Response($businesses);
            $response->encode(new ParserBusiness())
                ->search()
                ->sort()
                ->paginate()
                ->minimize()
                ->output();

        }
        catch (\Exception $exception)
        {
            $this->_handleException($exception);
        }
    }
}
