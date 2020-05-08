<?php
defined('BASEPATH') or exit('No direct script access allowed');

use phpseclib\Net\SFTP;

/*
 * Controller to send files to our printing company Wolverine
 *
 * endpoint will be hit nightly,
 */

class SendCSV extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->helper('sftp_helper');

        $this->sftp = connect(Config::PRINT_SFTP_HOST, Config::PRINT_SFTP_USERNAME, Config::PRINT_SFTP_PASSWORD);

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

    /*
    * sends file to print company
    */
    public function sendprint($customDate = "")
    {
        // use date that comes in from url, otherwise use todays date
        $day = $customDate ? date('Y-m-d', strtotime($customDate)) : date('Y-m-d');

        $printFileName = "patients_form_print.csv";
        $localPrintFile = Config::DATA_DIR . "$day/$printFileName";

        $listFileName = "patient_appointments.csv";
        $localListFile = Config::DATA_DIR . "$day/$listFileName";

        // go to woliverines dropbox first
        $this->sftp->chdir(Config::PRINT_SFTP_STARTING_LOCATION);
        $this->sftp->mkdir($day);
        $this->sftp->chdir($day);
        $cntFilesSent = 0;

        if (file_exists($localPrintFile)) {
            try {
                $this->sftp->put($printFileName, $localPrintFile, SFTP::SOURCE_LOCAL_FILE);
                $cntFilesSent++;
            } catch(Exception $e) {
                $this->logger->error(sprintf('There was an issue putting the file %s, Exception: %s'), $localPrintFile, $e);
            }
        } else {
            $this->logger->error(sprintf('There was no file %s, no sftp sent', $localPrintFile));
        }

        if (file_exists($localListFile)) {
            try {
                $this->sftp->put($listFileName, $localListFile, SFTP::SOURCE_LOCAL_FILE);
                $cntFilesSent++;
            } catch(Exception $e) {
                $this->logger->error(sprintf('There was an issue putting the file %s, Exception: %s'), $localListFile, $e);
            }
        } else {
            $this->logger->error(sprintf('There was no file %s, no sftp sent', $localListFile));
        }

        $message = sprintf('Script finished for %s', $day);
        $this->logger->notice($message);
        $ret = [
            'status' => 'success',
            'message' => $message,
            'files_sent' => $cntFilesSent
        ];
        echo json_encode($ret) . PHP_EOL;
    }
}
