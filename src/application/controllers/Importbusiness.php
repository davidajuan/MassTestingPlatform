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
 * Import Business Controller
 * Updates business records
 *
 * @package Controllers
 */
class ImportBusiness extends CI_Controller
{
    /**
     * Class Constructor
     */
    public function __construct()
    {
        parent::__construct();
        $this->load->library('business_import');

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
        $ret = [
            'status' => 'error',
            'message' => 'Are you sure you are using the right command? test OR insert',
        ];
        echo json_encode($ret) . PHP_EOL;
    }

    public function test($file_name)
    {
        //////////
        // Get File
        // Unpack File Contents
        // Validate
        // Dry Run Insert
        // Report Results

        // Success / Fail -> Report
        // Get Confirm of Insert
        // Revalidate
        // DB Insert Customer / DB Insert Business

        // Success / Fail -> Report
        //////////

        $ret = [
            'status' => 'error',
            'message' => '',
        ];

        // Sanitize input
        $file_path = realpath($file_name);
        if ($file_path == false) {
            // File does not exist
            $ret['message'] = "File '{$file_name}' does not exist";
            echo json_encode($ret) . PHP_EOL;
            return;
        }

        // Check that the file is in a public/safe directory
        $absDataDir = realpath(Config::DATA_DIR) . DIRECTORY_SEPARATOR;
        if (strpos($file_path, $absDataDir) !== 0) {
            $ret['message'] = "File '{$file_path}' is not in data directory";
            echo json_encode($ret) . PHP_EOL;
            return;
        }

        // Unpack File Contents
        $data = $this->business_import->fileToAssocArray($file_path);
        // Sanitize list to prevent XSS
        foreach ($data as $key => $value) {
            $data[$key] = $this->security->xss_clean($value);
        }

        // Validate
        // Dry Run Insert
        $result = $this->business_import->validate($data);

        // Report Results
        $this->reportResults($result, $file_path);
    }

    public function insert($file_name)
    {
        //////////
        // Get File
        // Unpack File Contents
        // Validate
        // Dry Run Insert
        // Report Results

        // Success / Fail -> Report
        // Get Confirm of Insert
        // Revalidate
        // DB Insert Customer / DB Insert Business

        // Success / Fail -> Report
        //////////

        $ret = [
            'status' => 'error',
            'message' => '',
        ];

        // Sanitize input
        $file_path = realpath($file_name);
        if ($file_path == false) {
            // File does not exist
            $ret['message'] = "File '{$file_name}' does not exist";
            echo json_encode($ret) . PHP_EOL;
            return;
        }

        // Check that the file is in a public/safe directory
        $absDataDir = realpath(Config::DATA_DIR) . DIRECTORY_SEPARATOR;
        if (strpos($file_path, $absDataDir) !== 0) {
            $ret['message'] = "File '{$file_path}' is not in data directory";
            echo json_encode($ret) . PHP_EOL;
            return;
        }

        // Unpack File Contents
        $data = $this->business_import->fileToAssocArray($file_path);
        // Sanitize list to prevent XSS
        foreach ($data as $key => $value) {
            $data[$key] = $this->security->xss_clean($value);
        }

        // Validate
        // REAL INSERT
        $result = $this->business_import->insert($data);

        // Report Results
        $this->reportResults($result, $file_path);

        // Notify approved business'
        $this->notifyBusiness($result);

    }
    /**
     * Notify businesses of their approval
     */
    protected function notifyBusiness(ImportResult $result): void
    {
        // Report Results
        if (!$result->hasErrors()) {
            $businesses = $result->getNotifyQueue();

            foreach($businesses as $business) {
                // :: Send email notification to business
                try {
                    $this->load->library('ses_email');
                    $email = new \EA\Engine\Notifications\Email($this, $this->config->config, $this->ses_email);
                    $email->sendBusinessActiveDetails($business, Config::SES_EMAIL_ADDRESS, Config::SES_EMAIL_NAME);
                } catch (Exception $exc) {
                    log_message('error', $exc->getMessage());
                    log_message('error', $exc->getTraceAsString());
                }

                // :: Send SMS notification to business
                try {
                    $this->load->library('sns_sms');
                    $sms = new \EA\Engine\Notifications\Sms($this, $this->config->config, $this->sns_sms);
                    $sms->sendBusinessActiveDetails($business);
                } catch (Exception $exc) {
                    log_message('error', $exc->getMessage());
                    log_message('error', $exc->getTraceAsString());
                }
            }
        }
    }


    /**
     * Report results in a friendly format
     */
    protected function reportResults(ImportResult $result, string $file_path): void
    {
        $ret = [
            'status' => 'error',
            'message' => '',
        ];

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
            $this->logger->notice(json_encode($ret));

            echo json_encode($ret) . PHP_EOL;
            return;
        }
        else {
            // All Good
            $ret['status'] = 'success';
            $ret['message'] = 'All records (' . count($result->getSuccessfulRows()) . ') were valid for file: ' . $file_path;

            // Log it
            $this->logger->notice(json_encode($ret));

            echo json_encode($ret) . PHP_EOL;
            return;
        }
    }
}
