<?php defined('BASEPATH') or exit('No direct script access allowed');

use Respect\Validation\Validator as v;

/**
 * Email Validation API Controller
 *
 * @package Controllers
 * @subpackage API
 */
class EmailValidate extends CI_Controller {
    /**
     * Class Constructor
     */
    public function __construct() {
        parent::__construct();
        $this->load->library('neverbounce');
        $this->load->helper('privilege');

        // Only allow POST for CSRF checking
        if ($this->input->method() !== 'post') {
            show_error('The action you have requested is not allowed.', 403);
        }

        if (!_has_privileges(PRIV_CUSTOMERS, false)) {
            show_error('You are not authorized.', 401);
        }
    }

    /**
     * Validate an email
     */
    public function index() {
        // Get params
        $email = $_POST['email'] ?? null;

        // Setup Response
        $ret = [
            'status' => 'error',
            'valid' => false,
            'message' => '* Unknown error',
        ];

        // Preliminary Checks
        if (!v::email()->validate($email)) {
            $ret['message'] = '* Email failed pre-validation';
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode($ret));
            return;
        }

        // Expensive Checks
        try {
            // Only run neverbounce validation if we have an api key to work with
            if (Config::NEVERBOUNCE_API_KEY) {
                $valid = $this->neverbounce->validEmail($email);

                // Able to contact service
                $ret['status'] = 'success';
                if ($valid) {
                    $ret['valid'] = true;
                    $ret['message'] = '* Email is valid';
                }
                else {
                    $ret['message'] = '* Email failed service validation';
                }
            } else {
                $ret['valid'] = true;
                $ret['message'] = '* Email is valid';
            }
        } catch (Exception $e) {
            // The api threw an exception, handle appropriately
            $ret['message'] = '* Email service has an error';
        }

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($ret));
        return;
    }
}
