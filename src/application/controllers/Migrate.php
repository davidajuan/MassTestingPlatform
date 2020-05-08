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

/**
 * Migrate Controller
 * Source: https://stackoverflow.com/a/17886252
 *
 * @package Controllers
 */
class Migrate extends CI_Controller {
    /**
     * Class Constructor
     */
    public function __construct()
    {
        parent::__construct();
        $this->load->library('migration');
    }

    public function index() {
        $ret = [
            'expectedVersion' => $this->migration->getExpectedVersion(),
            'actualVersion' => $this->migration->getActualVersion(),
        ];
        echo json_encode($ret) . PHP_EOL;
    }

    public function update()
    {
        $ret = [
            'status' => 'error',
            'message' => 'Missing permissions',
        ];

        // Only allow migration through the command line
        // $ php index.php migrate update
        if ($this->input->is_cli_request()) {

            // Covering different scenarios
            if ($this->migration->getActualVersion() == 0) {
                // Database is not even set up yet!
                $ret['status'] = 'success';
                $ret['message'] = 'Skipping update, initialize database first';
            }
            elseif ($this->migration->current()) {
                // Successful migration
                $ret['status'] = 'success';
                $ret['message'] = 'Version is now ' . $this->migration->getActualVersion();
            } else {
                // Error
                $ret['message'] = $this->migration->error_string();
            }
        }

        echo json_encode($ret) . PHP_EOL;
    }

    public function updateTo($versionNumber = null)
    {
        $ret = [
            'status' => 'error',
            'message' => 'Missing permissions',
        ];

        // Only allow migration through the command line
        if(!$this->input->is_cli_request()) {
            echo json_encode($ret) . PHP_EOL;
            return;
        }

        if (!ctype_digit($versionNumber)) {
            $ret['message'] = 'Version input must be an integer';
            echo json_encode($ret) . PHP_EOL;
            return;
        }

        $result = $this->migration->version($versionNumber);
        if ($result === true) {
            // Nothing to migrate to
            $ret['status'] = 'success';
            $ret['message'] = 'Nothing was migrated';
        }
        elseif ($result === false) {
            // Error
            $ret['message'] = $this->migration->error_string();
        }
        else {
            // Success
            $ret['status'] = 'success';
            $ret['message'] = 'Version is now ' . $this->migration->getActualVersion();
        }

        echo json_encode($ret) . PHP_EOL;
    }
}
