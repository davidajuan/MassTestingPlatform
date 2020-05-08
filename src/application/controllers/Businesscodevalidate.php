<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Business Code Validation API Controller
 *
 * @package Controllers
 * @subpackage API
 */
class BusinessCodeValidate extends CI_Controller {
    /**
     * Class Constructor
     */
    public function __construct() {
        parent::__construct();
        $this->load->model('business_request_model');
        $this->load->helper('privilege');

        // Only allow POST for CSRF checking
        if ($this->input->method() !== 'post') {
            show_error('The action you have requested is not allowed.', 403);
        }

        if (!_has_privileges(PRIV_CUSTOMERS, false) && !_has_privileges(PRIV_BUSINESS, false) && !_has_privileges(PRIV_BUSINESS_REQUEST, false)) {
            show_error('You are not authorized.', 401);
        }
    }

    /**
     * Validate an business code
     */
    public function index() {
        // Get params
        $business_code = $_POST['business_code'] ?? null;

        // Setup Response
        $ret = [
            'status' => 'error',
            'valid' => false,
            'message' => '* Unknown error',
        ];

        try {
            $checkCode = $this->business_request_model->checkBusinessCode($business_code);

            $ret['status'] = 'success';
            $ret['valid'] = $checkCode['valid'];
            $ret['priority_service'] = $checkCode['priority_service'];
            $ret['message'] = $checkCode['message'];
        } catch (Exception $e) {
            $ret['message'] = '* Business code validation has an error';
        }

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($ret));
        return;
    }
}
