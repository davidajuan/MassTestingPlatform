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
/**
 * Import Appointment Controller
 * Imports patient/appointments into our system
 * Assumes times slots are free to be added without checking availability
 *
 * @package Controllers
 */
class ImportAppointmentsCli extends CI_Controller
{
    /**
     * Class Constructor
     */
    public function __construct()
    {
        parent::__construct();
        $this->load->library('patient_import');

        // Don't allow browser
        if(!$this->input->is_cli_request()) {
            $ret = [
                'status' => 'error',
                'message' => 'Missing permissions',
            ];
            echo json_encode($ret) . PHP_EOL;
            die();
        }
    }

    public function index() {
        // Do Nothing
        $message = 'Are you sure you are using the right command? test OR insert';
        echo $message . PHP_EOL;
    }

    public function test($file_name, $provider_id, $service_id)
    {
        $ret = [
            'status' => 'error',
            'message' => '',
        ];

        //////////
        // Get File
        // Unpack File Contents
        // Validate
        // Dry Run Insert
        // Report Results

        // Success / Fail -> Report
        // Get Confirm of Insert
        // Revalidate
        // DB Insert Customer / DB Insert Appointment

        // Success / Fail -> Report
        //////////

        // Get File
        $file_path = Config::DATA_DIR . $file_name;
        if (!file_exists($file_path)) {
            $this->logger->error("The file '$file_path' does not exist in the data directory");
            return;
        }

        // Unpack File Contents
        $data = $this->patient_import->fileToAssocArray($file_path);
        // Sanitize list to prevent XSS
        foreach ($data as $key => $value) {
            $data[$key] = $this->security->xss_clean($value);
        }

        // Validate
        // Dry Run Insert
        $result = $this->patient_import->validate($data, $provider_id, $service_id);

        // Report Results
        if ($result->hasErrors()) {
            // Report on errors

            $ret['status'] = 'error';
            $ret['message'] = 'Check errorList for file: ' . $file_path;

            // Compile error list
            $errorList = $result->getFileErrors();
            foreach ($result->getRowErrors() as $error) {
                $errorList[] = sprintf('Row %s: %s', $error->row, $error->message);
            }
            $ret['errorList'] = $errorList;

            // Log it
            $message = json_encode($ret);
            $this->logger->notice($message);

            echo '-- START OF ERRORS --' . PHP_EOL;
            foreach ($errorList as $error) {
                echo $error . PHP_EOL;
            }
            echo '-- TEST FAILED --' . PHP_EOL;
            echo PHP_EOL;
            return;
        }
        else {
            // All Good
            $ret['status'] = 'success';
            $ret['message'] = 'All records (' . count($result->getSuccessfulRows()) . ') are valid for file: ' . $file_path;
            $message = json_encode($ret);
            $this->logger->notice($message);

            echo '-- TEST RESULTS --' . PHP_EOL;
            echo $ret['message'] . PHP_EOL;
            echo PHP_EOL;
            return;
        }
    }

    public function insert($file_name, $provider_id, $service_id)
    {
        $ret = [
            'status' => 'error',
            'message' => '',
        ];

        //////////
        // Get File
        // Unpack File Contents
        // Validate
        // Dry Run Insert
        // Report Results

        // Success / Fail -> Report
        // Get Confirm of Insert
        // Revalidate
        // DB Insert Customer / DB Insert Appointment

        // Success / Fail -> Report
        //////////

        // Get File
        $file_path = Config::DATA_DIR . $file_name;
        if (!file_exists($file_path)) {
            $this->logger->error("The file '$file_path' does not exist in the data directory");
            return;
        }

        // Unpack File Contents
        $data = $this->patient_import->fileToAssocArray($file_path);
        // Sanitize list to prevent XSS
        foreach ($data as $key => $value) {
            $data[$key] = $this->security->xss_clean($value);
        }

        // Validate
        // REAL INSERT
        $result = $this->patient_import->insert($data, $provider_id, $service_id);

        // Report Results
        if ($result->hasErrors()) {
            // Report on errors

            $ret['status'] = 'error';
            $ret['message'] = 'Check errorList for file: ' . $file_path;

            // Compile error list
            $errorList = $result->getFileErrors();
            foreach ($result->getRowErrors() as $error) {
                $errorList[] = sprintf('Row %s: %s', $error->row, $error->message);
            }
            $ret['errorList'] = $errorList;

            // Log it
            $message = json_encode($ret);
            $this->logger->notice($message);

            echo '-- START OF ERRORS --' . PHP_EOL;
            foreach ($errorList as $error) {
                echo $error . PHP_EOL;
            }
            echo '-- INSERT FAILED --' . PHP_EOL;
            echo PHP_EOL;
            return;
        }
        else {
            // All Good
            $ret['status'] = 'success';
            $ret['message'] = 'All records (' . count($result->getSuccessfulRows()) . ') were inserted for file: ' . $file_path;
            $message = json_encode($ret);
            $this->logger->notice($message);

            echo '-- INSERT RESULTS --' . PHP_EOL;
            echo $ret['message'] . PHP_EOL;
            echo PHP_EOL;
            return;
        }
    }
}
