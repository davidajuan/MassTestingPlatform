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
 * Import Appointment API Controller
 * Imports patient/appointments into our system
 * Assumes times slots are free to be added without checking availability
 *
 * @package Controllers
 */
class ImportAppointments extends CI_Controller
{
    /**
     * Class Constructor
     */
    public function __construct()
    {
        parent::__construct();
        $this->load->library('patient_import');
        $this->load->helper('privilege');

        if (!_has_privileges(PRIV_CUSTOMERS, false)) {
            show_error('You are not authorized.', 401);
        }
    }

    public function test()
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
        $file_path = $_FILES["file"]["tmp_name"];
        $file_name = $_FILES["file"]["name"];

        // File type validation
        if (!$this->isFileTypeValid($_FILES["file"]["type"], $file_name)) {
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
        $providerId = $_POST["providerId"];
        $serviceId = $_POST["serviceId"];
        $result = $this->patient_import->validate($data, $providerId, $serviceId);

        // Report Results
        if ($result->hasErrors()) {
            // Report on errors
            $ret['status'] = 'error';
            $ret['message'] = 'Check the list below for errors in file: ' . $file_name;

            // Compile error list
            $ret['errorList']['fileErrors'] = $result->getFileErrors();
            $ret['errorList']['rowErrors'] = [];
            foreach ($result->getRowErrors() as $error) {
                $ret['errorList']['rowErrors'][] = [
                    'row' => $error->row,
                    'message' => $error->message,
                ];
            }
        }
        else {
            // All Good
            $ret['status'] = 'success';
            $ret['message'] = 'All records (' . count($result->getSuccessfulRows()) . ') are valid for file: ' . $file_name;
            $ret['successList'] = $data; // TODO: show data that was compiled and transformed
        }

        $this->output
        ->set_content_type('application/json')
        ->set_output(json_encode($ret));
    }

    public function insert()
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
        $file_path = $_FILES["file"]["tmp_name"];
        $file_name = $_FILES["file"]["name"];

        // File type validation
        if (!$this->isFileTypeValid($_FILES["file"]["type"], $file_name)) {
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
        $providerId = $_POST["providerId"];
        $serviceId = $_POST["serviceId"];
        $result = $this->patient_import->insert($data, $providerId, $serviceId);

        // Report Results
        if ($result->hasErrors()) {
            // Report on errors
            $ret['status'] = 'error';
            $ret['message'] = 'Check the list below for errors in file: ' . $file_name;

            // Compile error list
            $ret['errorList']['fileErrors'] = $result->getFileErrors();
            $ret['errorList']['rowErrors'] = [];
            foreach ($result->getRowErrors() as $error) {
                $ret['errorList']['rowErrors'][] = [
                    'row' => $error->row,
                    'message' => $error->message,
                ];
            }
        }
        else {
            // All Good
            $ret['status'] = 'success';
            $ret['message'] = 'All records (' . count($result->getSuccessfulRows()) . ') were inserted for file: ' . $file_name;
        }

        $this->output
        ->set_content_type('application/json')
        ->set_output(json_encode($ret));
    }

    protected function isFileTypeValid($file_type, $file_name) {
        // File type validation
        if ( $file_type === "text/csv") {
            return true;
        } else {
            $ret['status'] = 'error';
            $ret['message'] = 'File "' . $file_name . '" must be csv format';

            $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($ret));

            return false;
        }
    }
}
