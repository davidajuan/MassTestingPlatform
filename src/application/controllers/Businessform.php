<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Businessform extends CI_Controller
{
    // List and Order of CSV headers
    const APPROVAL_HEADERS = [
        'approved_confirm',     // Y or N to approve record, starts blank
        'slots_approved',       // Needs to be a positive integer, starts blank
        'slots_requested',
        'business_name',
        'business_code',        // Business Req Identifier
        'owner_first_name',
        'owner_last_name',
        'business_phone',
        'mobile_phone',
        'email',
        'address',
        'city',
        'state',
        'zip_code',
        'created',              // Datetime of when requested
        'hash',                 // Business Identifier
    ];


    const MASTER_HEADERS = [
        'business_name',
        'owner_first_name',
        'owner_last_name',
        'business_phone',
        'mobile_phone',
        'consent_sms',
        'email',
        'consent_email',
        'address',
        'city',
        'state',
        'zip_code',
        'hash',
        'business_code',
        'status',
        'slots_requested',
        'slots_approved', // moving target
        'created',
    ];

    public function __construct()
    {
        parent::__construct();
        $this->load->model('business_model');
        $this->load->model('business_request_model');

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

    /**
     * Generate the CSV that will be given to a 3rd party to sign off on businesses
     * that are requesting slots for tests
     *
     * @param string $inputFilename Filename to use when generating CSV
     * @param string $inputStart ISO8601 date timestamp (inclusive)
     * @param string $inputEnd ISO8601 date timestamp (inclusive)
     */
    public function generateApproval(string $inputFilename, string $inputStart, string $inputEnd): void
    {
        // Setup Vars
        $filename = null;
        $dtStart = null;
        $dtEnd = null;
        $filesGenerated = 0;
        $dataLocation = Config::DATA_DIR . 'business';
        $ret = [
            'status' => 'error',
            'message' => 'Init',
        ];

        // Sanitize inputs
        try {
            $dtStart = new DateTime($inputStart);
            $dtEnd = new DateTime($inputEnd);
            $filename = $inputFilename;

            // Only allow valid chars
            if (!preg_match("/^[a-z0-9\-\_\.]+\.[a-z]+$/i", $filename)) {
                throw new Exception();
            }
        } catch (Exception $e) {
            $ret['message'] = 'Invalid inputs';
            echo json_encode($ret) . PHP_EOL;
            return;
        }

        // Set up file locations
        if (!is_dir($dataLocation)) {
            mkdir($dataLocation, 0755, true);
        }

        // Get records for specified date range
        $businessReqs = $this->business_request_model->get_rows_createdate_ranged($dtStart, $dtEnd, DB_SLUG_BUSINESS_REQ_PENDING);

        // Generate CSV file
        if ($this->generateCsvApproval($businessReqs, $dataLocation.DIRECTORY_SEPARATOR.$filename)) {
            $filesGenerated++;
        }

        // Output
        $message = sprintf('Files (%s) generated for DATE range %s AND %s', $filesGenerated, $dtStart->format('Y-m-d H:i:s'), $dtEnd->format('Y-m-d H:i:s'));
        $this->logger->notice($message);
        $ret = [
            'status' => 'success',
            'message' => $message,
            'files_generated' => $filesGenerated
        ];
        echo json_encode($ret) . PHP_EOL;
    }

    /**
     * @deprecated version
     *
     * @param string $inputFilename Filename to use when generating CSV
     * @param string $inputStart ISO8601 date timestamp (inclusive)
     * @param string $inputEnd ISO8601 date timestamp (inclusive)
     * @return void
     */
    public function generate(string $inputFilename, string $inputStart, string $inputEnd): void
    {
        $this->generateApproval($inputFilename, $inputStart, $inputEnd);
    }

    /**
     * Generate the CSV that will be given to a 3rd party to sign off on businesses
     * that are requesting slots for tests
     *
     * @param string $inputDate Formatted as YYYY-MM-DD. Optional, assuming current day if not present
     * @return void
     */
    public function generateMaster(string $inputDate = 'now'): void
    {
        // Setup Vars
        $filename = Config::FILENAME_BUSINESS_MASTER;
        $dtStart = null;
        $dtEnd = null;
        $filesGenerated = 0;
        $dataLocation = null;
        $ret = [
            'status' => 'error',
            'message' => 'Init',
        ];

        // Sanitize inputs
        try {
            $dtStart = new DateTime($inputDate);
            $dtStart->setTime(0, 0, 0);
            $dtEnd = clone $dtStart;
            $dtEnd->setTime(23, 59, 59);

            $dataLocation = Config::DATA_DIR . $dtStart->format('Y-m-d');
        } catch (Exception $e) {
            $ret['message'] = 'Invalid inputs';
            echo json_encode($ret) . PHP_EOL;
            return;
        }

        // Set up file locations
        if (!is_dir($dataLocation)) {
            mkdir($dataLocation, 0755, true);
        }

        // Get records for specified date range
        $businesses = $this->business_request_model->get_rows_createdate_ranged($dtStart, $dtEnd);

        // Generate CSV file
        if ($this->generateCsvMaster($businesses, $dataLocation.DIRECTORY_SEPARATOR.$filename)) {
            $filesGenerated++;
        }

        // Output
        $message = sprintf('Files (%s) generated for DATE %s', $filesGenerated, $dtStart->format('Y-m-d'));
        $this->logger->notice($message);
        $ret = [
            'status' => 'success',
            'message' => $message,
            'files_generated' => $filesGenerated
        ];
        echo json_encode($ret) . PHP_EOL;
    }

    protected function generateCsvApproval(array $data, string $filePath): bool
    {
        if (!empty($data)) {
            // Massage Data
            foreach ($data as $key => $value) {
                // Calculate data
                $data[$key]['approved_confirm'] = '';
                $data[$key]['slots_approved'] = '';
                $data[$key]['created'] = date('Y-m-d H:i:s', strtotime($data[$key]['created']));

                // Normalize Data
                $data[$key] = $this->normalizeArray($data[$key], self::APPROVAL_HEADERS);
            }

            // Get Header Fields
            $csvHeader = array_keys($data[0]);

            // Write file
            $fp = fopen($filePath, 'w');
            fputcsv($fp, $csvHeader);
            foreach ($data as $row) {
                fputcsv($fp, $row);
            }
            fclose($fp);

            return true;
        }
        return false;
    }

    protected function generateCsvMaster(array $data, string $filePath): bool
    {
        if (!empty($data)) {
            // Massage Data
            foreach ($data as $key => $value) {
                // Normalize Data
                $data[$key] = $this->normalizeArray($data[$key], self::MASTER_HEADERS);
            }

            // Get Header Fields
            $csvHeader = array_keys($data[0]);

            // Write file
            $fp = fopen($filePath, 'w');
            fputcsv($fp, $csvHeader);
            foreach ($data as $row) {
                fputcsv($fp, $row);
            }
            fclose($fp);

            return true;
        }
        return false;
    }

    /**
    * Takes an array and normalizes it.
    *
    * Puts keys in specific indexed order.
    * Removes any keys that don't exist in $orderedKeys.
    * Will throw an exception if a key doesn't exist in $orderedKeys.
    * Source: https://stackoverflow.com/a/9098675/1583548
    *
    * @param array $data records/rows of data
    * @param array $orderedKeys array of keys that are in a specific order
    * @throws Exception If a record doesn't have a listed key in $orderedKeys
    */
    protected function normalizeArray(array $data, array $orderedKeys) {
        // Check for non existent keys
        $diff = array_diff_key(array_flip($orderedKeys), $data);
        if (!empty($diff)) {
            // Remove PII
            $diff = array_keys($diff);
            $diffKeys = implode(', ', $diff);
            $diffKeys = rtrim($diffKeys,',');

            throw new Exception(sprintf('Data array is missing required key(s): %s', $diffKeys));
        }

        // order keys
        $data = array_merge(array_flip($orderedKeys), $data);
        // trim non existent keys
        $data = array_intersect_key($data, array_flip($orderedKeys));

        return $data;
    }
}
