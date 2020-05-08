<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');

use Respect\Validation\Validator as v;
use Respect\Validation\Exceptions\NestedValidationException;

class Business_import {

    /**
     * Constructor
     */
    public function __construct(?array $params = []) {
        $CI =& get_instance();
        $CI->load->model('business_model');
        $CI->load->model('business_request_model');

        $this->business_model = $CI->business_model;
        $this->business_request_model = $CI->business_request_model;
        $this->db = $CI->db;
    }

    /**
     * Take records and test a dry-run insert into database.
     *
     * @param array $records All the records. Do not include the header row
     * @return ImportResult Object that contains all data about the insert process
     */
    public function validate(array $records): ImportResult {
        return $this->_insert($records);
    }

    /**
     * Take records and insert into database.
     *
     * @param array $records All the records. Do not include the header row
     * @return ImportResult Object that contains all data about the insert process
     */
    public function insert(array $records): ImportResult {
        return $this->_insert($records, true);
    }

    /**
     * Take records and insert into database. By default a dry-will take place unless the flag is set to true
     *
     * @param array $records All the records. Do not include the header row
     * @param bool $performRealInsert Set to true to actually insert data
     * @return ImportResult Object that contains all data about the insert process
     */
    protected function _insert(array $records, bool $performRealInsert = false): ImportResult {
        //////
        // Setup Vars
        // Determine the type of import
        // Perform Provider and Validator check

        // Perform File level checks (Generic)
        // Perform File level checks (Specific)

        // Perform Row level checks (Generic)
        // Perform Row level checks (Specific)

        // Build DB Records (Must support multi table insert/updates)
        // Perform DB insert/updates

        // Return Results
        //////

        // Setup Vars
        $importResult = new ImportResult();

        // Determine the type of import
        $importParser = $this->detectImportParser($records);
        $importResult->setImportParser($importParser);

        // Perform File level checks (Generic)
        $this->performFileChecksGeneric($importResult, $records);

        // Perform File level checks (Specific)
        $importParser->performFileChecksSpecific($importResult, $records);

        // Perform Row level checks (Generic)
        $this->performRowChecksGeneric($importResult, $records);

        // Perform Row level checks (Specific)
        $importParser->performRowChecksSpecific($importResult, $records);

        // Build DB Records (Must support multi table insert/updates)
        $databaseRecords = $importParser->buildDbRecords($importResult, $records, $this);

        // Check if we can do DB inserts
        if (!$importResult->hasErrors()) {
            $this->db->trans_begin();

            // Perform DB insert/updates
            $importParser->performDbInserts($importResult, $databaseRecords, $this);

            // Check if we want to commit the transaction!
            if ($performRealInsert === true && !$importResult->hasErrors()) {
                $this->db->trans_commit();
                $importResult->setPerformedInsert(true);
            }
            else {
                $this->db->trans_rollback();
            }
        }

        // Return Results
        $importResult->prepResults();
        return $importResult;
    }

    /**
     * Read file and get each row as an associated array
     * Header row (row 1) is stripped out
     */
    public static function fileToAssocArray(string $file_path) {
        $file = fopen($file_path, "r");

        $new_appointments = [];
        // clean data and put into associative arrays
        $header = fgetcsv($file);

        while (!feof($file)) {
            $appointment = fgetcsv($file);
            $app = [];
            foreach ($appointment as $idx => $data) {
                if (!isset($header[$idx])) {
                    // Scenario where a record has more columns than the header
                    continue;
                }

                $fieldKey = Business_import::filterOutNonUtf8(trim($header[$idx]));
                $app[$fieldKey] = Business_import::filterOutNonUtf8(trim($data));
            }

            // Check if entire row is empty
            $empty = true;
            foreach ($app as $key => $val) {
                if (!empty($val)) {
                    $empty = false;
                break;
                }
            }
            if ($empty) {
                continue;
            }

            $new_appointments[] = $app;
        }

        fclose($file);

        return $new_appointments;
    }

    /**
     * Take the input and remove all non UTF-8 characters.
     * JSON explodes if you encode/decode non UTF-8 chars!
     */
    public static function filterOutNonUtf8($value) {
        // Substitute any intended question mark chars, otherwise they will be stripped out in the next step.
        $value = preg_replace('/[?]/', '=--Q--=', $value);
        // Remove Non UTF-8
        $value = preg_replace('/[?]/u', '', utf8_decode($value));
        // Put back original question marks
        $value = preg_replace('/=--Q--=/', '?', $value);
        return $value;
    }

    /**
     * Exchanges the keys and values as directed
     * Missing Value mappings will resort in nulls being injected
     *
     * Example
     *  dataArray: [ 'checkbox1' => 'Y', 'checkbox2' => 'N' ]
     *  keyArray: [ 'NewCheck1' => 'checkbox1', 'NewCheck2' => 'checkbox2' ]
     *  valueArray: [ 'NewYes' => 'Y', 'NewNo' => 'N' ]
     *
     * Output:
     *  [ 'NewCheck1' => 'NewYes', 'NewCheck2' => 'NewNo' ]
     * @param array $dataArray
     * @param array $keyArray
     * @param array $valueArray
     * @return array New data array
     */
    public static function translateKeysAndValues(array $dataArray, array $keyArray, array $valueArray) {
        foreach ($keyArray as $dbHeader => $csvHeader) {
            // Exchange Value
            // If mapping doesn't exist, null will be applied
            $flip = array_flip($valueArray);
            $newVal = $flip[$dataArray[$csvHeader]] ?? null;
            // Exchange Key
            // TODO: Forcing string here.... code smell
            $dataArray[$dbHeader] = strval($newVal);
            // Delete Old Key (if it's different than new key)
            if ($dbHeader !== $csvHeader) {
                unset($dataArray[$csvHeader]);
            }
        }
        return $dataArray;
    }

    /**
     * Solves the Y2K problem of assuming the century
     * If the date is in the future, we assume you're talking about 100 years ago
     */
    public static function assumeFullYear(DateTime $datetime) {
        $dateCurr = new DateTime();
        if ($datetime >= $dateCurr) {
            $datetime->add(date_interval_create_from_date_string('-100 years'));
        }
        return $datetime;
    }

    public static function getCurrentDate() {
        $dateCurr = new DateTime();
        $dateCurr->setTime(0, 0, 0); // Set to very beginning of today
        return $dateCurr;
    }

    public static function getTomorrowDate() {
        $dateTomorrow = Business_import::getCurrentDate();
        $dateTomorrow->add(date_interval_create_from_date_string('1 day'));
        return $dateTomorrow;
    }

    public static function handleErrorMessaging($importResult, $record, $lineNum, $exception, $customErrorMessages) {
        // Build custom error messaging
        $errorMessages = $record;
        // Set all messages to default
        $errorMessages = array_map(function($val) { return ''; }, $errorMessages);
        // Set custom messages here
        // Source: https://stackoverflow.com/a/6562291/1583548
        $errorMessages = array_intersect_key($customErrorMessages, $errorMessages) + $errorMessages;
        // Fetch messages with out custom template
        $errors = $exception->findMessages($errorMessages);
        // Normalize array and remove any NULL values
        $errors = array_filter($errors, function($value) {
            return !is_null($value) && $value !== '';
        });

        foreach ($errors as $errorKey => $error) {
            $importResult->addRowError($lineNum, sprintf('%s', $error));
        }
    }

    protected function detectImportParser(array $sampleRow) {
        // Check for multi rows or a single
        if (@is_array($sampleRow[0])) {
            $sampleRow = $sampleRow[0];
        }

        if (count(array_intersect_key(array_flip(ImportRequestParser::HEADER_REQUIRED), $sampleRow)) === count(ImportRequestParser::HEADER_REQUIRED)) {
            return new ImportRequestParser();
        }

        // TODO: We should throw an exception when we can't detect anything
        return new ImportRequestParser();
    }

    protected function performFileChecksGeneric(object $importResult, array $records) {
        if (count($records) <= 0) {
            // Are records empty?
            $importResult->addFileError('Record set is empty');
        }

        // does the encoding match UTF-8?
        if (!mb_check_encoding($records, 'UTF-8')) {
            $importResult->addFileError('File contains non UTF-8 encoded characters');
            // This should not happen as we are decoding and encoding during File to Array parsing
        }

        try {
            // do all rows have the same number of columns?
            $headerRowSize = @count($records[0]);
            foreach ($records as $lineNum => $row) {
                if (count($row) !== $headerRowSize) {
                    $importResult->addRowError($lineNum, sprintf('Column count (%s) does not match Header Column count (%s)', count($row), $headerRowSize));
                }
            }
        } catch (Exception $e) {}
    }

    protected function performRowChecksGeneric(object $importResult, array $records) {
        // check that each row has cols that match the whitelist
        foreach ($records as $lineNum => $row) {
            // Check for non existent keys
            $diff = array_diff_key(array_flip($importResult->getImportParser()->getRequiredHeaders()), $row);
            if (!empty($diff)) {
                $diff = array_keys($diff);
                $diffKeys = implode(', ', $diff);
                $diffKeys = rtrim($diffKeys, ',');
                $importResult->addRowError($lineNum, sprintf('Missing columns %s', $diffKeys));
            }
        }
    }
}

class ImportResult {
    protected $performedInsert = false;
    protected $errorGenerics = [];
    protected $errorRows = [];
    protected $successfulRow = [];
    protected $parser = null;
    protected $serviceId = null;
    protected $providerId = null;
    protected $notifyQueue = [];

    public function addFileError(string $message) {
        $this->errorGenerics[] = $message;
    }

    public function addRowError(int $row, string $message) {
        // Apply row offset to match input file
        $this->errorRows[] = new RowError($row + 1, $message);
    }

    public function addNotifyQueue(array $row) {
        $this->notifyQueue[] = $row;
    }

    public function getNotifyQueue() {
        return $this->notifyQueue;
    }

    public function getFileErrors() {
        return $this->errorGenerics;
    }

    public function getRowErrors() {
        return $this->errorRows;
    }

    public function hasErrors() {
        return !empty($this->errorGenerics) || !empty($this->errorRows);
    }

    public function setPerformedInsert(bool $performedInsert) {
        $this->performedInsert = $performedInsert;
    }

    /**
     * Db records were actually inserted and committed via a transaction
     */
    public function performedInsert() {
        return $this->performedInsert;
    }

    public function addSuccessfulRow(int $row) {
        $this->successfulRow[] = $row;
    }

    public function getSuccessfulRows() {
        return $this->successfulRow;
    }

    public function setImportParser(Parser $parser): void {
        $this->parser = $parser;
    }

    public function getImportParser(): Parser {
        return $this->parser;
    }

    public function setProviderId(int $id): void {
        $this->providerId = $id;
    }

    public function getProviderId(): int {
        return $this->providerId;
    }

    public function setServiceId(int $id): void {
        $this->serviceId = $id;
    }

    public function getServiceId(): int {
        return $this->serviceId;
    }

    /**
     * Clean up any post results
     */
    public function prepResults(): void {
        // Go through row errors and order them based on row number
        usort($this->errorRows, function($a, $b) {
            return strcmp($a->row, $b->row);
        });
    }
}

class RowError {
    public $row;
    public $message;

    public function __construct(int $row, string $message) {
        $this->row = $row;
        $this->message = $message;
    }
}

interface Parser {
    public function getType(): string;
    public function getRequiredHeaders(): array;
    public function performFileChecksSpecific(object $importResult, array $records): void;
    public function performRowChecksSpecific(object $importResult, array $records): void;
    public function buildDbRecords(object $importResult, array $records, object $params): array;
    public function performDbInserts(object $importResult, array $databaseRecords, object $params): void;
}

class ImportRequestParser implements Parser {
    const HEADER_REQUIRED = [
        'approved_confirm',     // We need this
        'slots_approved',       // We need this
        'slots_requested',
        'business_name',
        'business_code',        // We need this
        'owner_first_name',
        'owner_last_name',
        'business_phone',
        'mobile_phone',
        'email',
        'address',
        'city',
        'state',
        'zip_code',
        'created',
        'hash',                 // We need this
    ];

    const ERROR_CUSTOM_MESSAGES = [
        'approved_confirm' => 'Column `{{name}}` is invalid. Needs to be `Y` or `N` or blank.',
        'slots_approved' => 'Column `{{name}}` is invalid. Needs to be a positive number.',
        'business_code' => 'Column `{{name}}` is invalid.',
        'hash' => 'Column `{{name}}` is invalid.',
    ];


    public function getType(): string {
        return 'request';
    }
    public function getRequiredHeaders(): array {
        return self::HEADER_REQUIRED;
    }
    public function performFileChecksSpecific(object $importResult, array $records): void {

    }
    public function performRowChecksSpecific(object $importResult, array $records): void {
        // Setup validator
        $yesNoEmptyValidator = v::stringType()->oneOf(
            v::equals('Y'),
            v::equals('N'),
            v::equals(''),
        );
        $validator =
            v::key('approved_confirm', $yesNoEmptyValidator)
            ->key('slots_approved', v::intVal()->min(0))
            ->key('business_code', v::alnum()->notEmpty())
            ->key('hash', v::alnum()->notEmpty());

        // pick apart each customer row
        foreach ($records as $lineNum => $row) {
            // Perform validation and get messages
            try {
                $validator->assert($row);
            } catch(NestedValidationException $e) {
                Business_import::handleErrorMessaging($importResult, $row, $lineNum, $e, self::ERROR_CUSTOM_MESSAGES);
            }
        }
    }
    public function buildDbRecords(object $importResult, array $records, object $params): array {
        // Look up each request (currently in a pending status) using the business_code and hash
        // Update the request `status` with db slugs
        // Update the request `slots_approved` with val

        // Setup Vals
        $dateCurr = new DateTime();
        $masterList = [];

        foreach ($records as $lineNum => $row) {
            // Get the req
            $searchCrit = [
                'business_code' => $row['business_code'],
            ];
            $businessReq = $params->business_request_model->get_batch($searchCrit);
            // Get the first return
            $businessReq = $businessReq[0] ?? $businessReq;
            if (empty($businessReq)) {
                $importResult->addRowError($lineNum, sprintf('Column `%s` with value `%s` does not exist.', 'business_code', $row['business_code']));
                continue;
            }

            // Check the pending status of this req
            if ($businessReq['status'] !== DB_SLUG_BUSINESS_REQ_PENDING) {
                // Silently move on to next record
                continue;
            }

            // Get the Business tied to Req, make sure it exists
            $searchCrit = [
                'id' => $businessReq['id_business'],
                'hash' => $row['hash'],
            ];
            $business = $params->business_model->get_batch($searchCrit);
            if (empty($business)) {
                $importResult->addRowError($lineNum, sprintf('Column `%s` with value `%s` does not exist.', 'hash', $row['hash']));
                continue;
            }

            // Fill in business info
            $businessInfo = [
                'mobile_phone' => $business[0]['mobile_phone'],
                'consent_sms' => $business[0]['consent_sms'],
                'email' => $business[0]['email'],
                'consent_email' => $business[0]['consent_email'],
                'business_code' => $row['business_code'],
                'owner_first_name' => $business[0]['owner_first_name'],
            ];

            // Start Building DB record
            $newRecord = [
                'modified' => $dateCurr->format('Y-m-d H:i:s'),
            ];
            // Existing values are needed, even for update
            $newRecord = array_merge($businessReq, $newRecord);

            // Update the request `status` with db slugs
            if ($row['approved_confirm'] === 'Y') {
                $newRecord['status'] = DB_SLUG_BUSINESS_REQ_ACTIVE;
                $newRecord['slots_approved'] = $row['slots_approved'];
                $businessInfo['status'] = DB_SLUG_BUSINESS_REQ_ACTIVE;
                $businessInfo['slots_approved'] = $row['slots_approved'];
            }
            elseif ($row['approved_confirm'] === 'N') {
                $newRecord['status'] = DB_SLUG_BUSINESS_REQ_DELETED;
            }
            else {
                // User provided an empty approval
                // Silently move on to next record
                continue;
            }

            $masterList[] = [
                'ea_business_request' => $newRecord,
                'businessInfo' => $businessInfo
            ];
        }

        return $masterList;
    }
    public function performDbInserts(object $importResult, array $databaseRecords, object $params): void {
        // Loop through lists
        for ($i = 0; $i < count($databaseRecords); $i++) {
            try {
                $params->business_request_model->add(
                    $databaseRecords[$i]['ea_business_request'],
                    null // Hack; Needed to use add() method later, we are only updating so it's not needed
                );
                $importResult->addSuccessfulRow($i);

                // if it is approved, add them to the notifier queue
                if ($databaseRecords[$i]['businessInfo']['status'] === DB_SLUG_BUSINESS_REQ_ACTIVE) {
                    $importResult->addNotifyQueue($databaseRecords[$i]['businessInfo']);
                }
            } catch (Exception $e) {
                $importResult->addRowError($i, sprintf('DB Insert Failure: %s', $e));
            }
        }
    }
}
