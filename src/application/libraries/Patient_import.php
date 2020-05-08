<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');

use Respect\Validation\Exceptions\NestedValidationException;
class Patient_import {

    /**
     * Constructor
     */
    public function __construct(?array $params = []) {
        $CI =& get_instance();
        $CI->load->model('customers_model');
        $CI->load->model('user_model');
        $CI->load->model('appointments_model');
        $CI->load->model('providers_model');
        $CI->load->model('services_model');
        $CI->load->model('business_request_model');

        $this->customers_model = $CI->customers_model;
        $this->user_model = $CI->user_model;
        $this->appointments_model = $CI->appointments_model;
        $this->providers_model = $CI->providers_model;
        $this->services_model = $CI->services_model;
        $this->business_request_model = $CI->business_request_model;
        $this->db = $CI->db;
    }


    /**
     * Take records and test a dry-run insert into database.
     *
     * @param array $records All the records. Do not include the header row
     * @param int $providerId
     * @param int $serviceId
     * @return ImportResult Object that contains all data about the insert process
     */
    public function validate(array $records, int $providerId, int $serviceId) {
        return $this->_insert($records, $providerId, $serviceId);
    }

    /**
     * Take records and insert into database.
     *
     * @param array $records All the records. Do not include the header row
     * @param int $providerId
     * @param int $serviceId
     * @return ImportResult Object that contains all data about the insert process
     */
    public function insert(array $records, int $providerId, int $serviceId) {
        return $this->_insert($records, $providerId, $serviceId, true);
    }

    /**
     * Take records and insert into database. By default a dry-will take place unless the flag is set to true
     *
     * @param array $records All the records. Do not include the header row
     * @param int $providerId
     * @param int $serviceId
     * @param bool $performRealInsert Set to true to actually insert data
     * @return ImportResult Object that contains all data about the insert process
     */
    protected function _insert(array $records, int $providerId, int $serviceId, bool $performRealInsert = false) {
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

        // Perform Provider and Validator check
        // Validate Provider
        try {
            // TODO: This is not enough, somehow it's tied to an email address...
            $tmp = $this->providers_model->get_row($providerId);
            $importResult->setProviderId($providerId);
        } catch (Exception $e) {
            $importResult->addFileError(sprintf('Provider Id `%s` does not exist', $providerId));
        }
        // Validate Service
        try {
            // TODO: This is not enough, somehow it's tied to an email address...
            $tmp = $this->services_model->get_row($serviceId);
            $importResult->setServiceId($serviceId);
        } catch (Exception $e) {
            $importResult->addFileError(sprintf('Service Id `%s` does not exist', $serviceId));
        }

        // Perform File level checks (Generic)
        $this->performFileChecksGeneric($importResult, $records);

        // Perform File level checks (Specific)
        $importParser->performFileChecksSpecific($importResult, $records);

        // Perform Row level checks (Generic)
        $this->performRowChecksGeneric($importResult, $records);

        // Perform Row level checks (Specific)
        $importParser->performRowChecksSpecific($importResult, $records, $this);

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

                $fieldKey = Patient_import::filterOutNonUtf8(trim($header[$idx]));
                $app[$fieldKey] = Patient_import::filterOutNonUtf8(trim($data));
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
        $dateTomorrow = Patient_import::getCurrentDate();
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

        if (count(array_intersect_key(array_flip(ImportRescheduleParser::HEADER_REQUIRED), $sampleRow)) === count(ImportRescheduleParser::HEADER_REQUIRED)) {
            return new ImportRescheduleParser();
        }
        return new ImportNewParser();
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
        $dupCheckList = [];

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

            $uniqueFields = [
                'first_name' => $row['first_name'],
                'last_name' => $row['last_name'],
                'dob' => $row['DOB'],
            ];

            // Check if record exists in our duplicate checklist
            if (in_array($uniqueFields, $dupCheckList, true)) {
                // get the index the row was already found on
                $idxFound = array_search($uniqueFields, $dupCheckList, true);
                $importResult->addRowError($lineNum, sprintf('Duplicate row exists on row %s', $idxFound + 1));
            } else {
                $dupCheckList[$lineNum] = $uniqueFields;
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

    public function addFileError(string $message) {
        $this->errorGenerics[] = $message;
    }

    public function addRowError(int $row, string $message) {
        // Apply row offset to match input file
        // +2 because:
        //      We strip off header
        //      Arrays are zero-indexed
        // Most spreadsheet readers are one-indexed
        $this->errorRows[] = new RowError($row + 2, $message);
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
    public function performRowChecksSpecific(object $importResult, array $records, object $params): void;
    public function buildDbRecords(object $importResult, array $records, object $params): array;
    public function performDbInserts(object $importResult, array $databaseRecords, object $params): void;
 }

class ImportNewParser implements Parser {
    const HEADER_REQUIRED = [
        'appt_date', // Needs to be combined
        'appt_time', // Needs to be combined
        'first_name',
        'last_name',
        'email',
        'mobile_number',
        'address',
        'apt',
        'city',
        'state',
        'zip_code',
        'first_responder', // Needs friendly parsing
        'gender', // Needs friendly parsing
        'DOB', // Needs friendly parsing
        'Email Consent', // Needs friendly parsing
        'Text Message Consent', // Needs friendly parsing
        'doctor_first_name',
        'doctor_last_name',
        'doctor_npi',
        'doctor_address',
        'doctor_city',
        'doctor_state',
        'doctor_zip_code',
        'doctor_phone_number', // Needs friendly parsing
        'rx_date', // Needs to be combined
        'business_code'
    ];

    const FIELDS_YESNO = [
        'first_responder' => 'first_responder',
        'patient_consent' => 'Email Consent',
        'patient_consent_sms' => 'Text Message Consent',
    ];
    const VALUES_YESNO = [
        '1' => 'Y',
        '0' => 'N',
    ];

    const FIELDS_GENDER = [
        'gender' => 'gender',
    ];
    const VALUES_GENDER = [
        'male' => 'M',
        'female' => 'F',
        'transgender' => 'T',
        'other' => 'O',
    ];


    // Source: https://respect-validation.readthedocs.io/en/1.1/feature-guide/#custom-messages
    // TODO: There's a bug where each validator that shares the 0 or 1 checkbox.
    // It will always be named after the first field that was assigned the checkbox validator
    // IE: "Row 2: Column `patient_consent` is invalid. Needs format `Y` or `N`" but it's actually talking about `first_responder`
    // Therefore for now, we need to explicitly set error messaging to override these.
    // KEYS = DB Column Names
    // VALUES = Should contain the CSV Friendly Header Names
    const ERROR_CUSTOM_MESSAGES = [
        'state' => 'Column `{{name}}` is invalid',

        'first_responder' => 'Column `first_responder` is invalid. Needs format `Y` or `N`',
        'patient_consent' => 'Column `Email Consent` is invalid. Needs format `Y` or `N`',
        'patient_consent_sms' => 'Column `Text Message Consent` is invalid. Needs format `Y` or `N`',

        'gender' => 'Column `gender` is invalid. Needs format `M` or `F` or `T` or `O`',

        'caller' => 'Column `caller` is invalid. Needs format `patient` or `provider`',

        'dob' => 'Column `DOB` is invalid. Needs format `M/D/Y`',
        'rx_date' => 'Column `rx_date` is invalid. Needs format `M/D/Y`',
        'start_datetime' => 'Column `appt_date` is invalid. Needs format `M/D/Y`',
        'business_code' => 'Column `business_code` is invalid.',

        // TODO: Clean this. This is a manifest of all errors that can occur on a single row
        // You can use this list to prune and remove duplicate errors to clean up redundancy
        // Row 2: Column `mobile_number` with value `` is invalid. Needs format `NNN-NNN-NNNN`
        // Row 2: Column `first_responder` with value `` is invalid. Needs format `Y` or `N`
        // Row 2: Column `gender` with value `` is invalid. Needs format `M` or `F` or `T` or `O`
        // Row 2: Column `Email Consent` with value `` is invalid. Needs format `Y` or `N`
        // Row 2: Column `Text Message Consent` with value `` is invalid. Needs format `Y` or `N`
        // Row 2: Column `doctor_phone_number` with value `` is invalid. Needs format `NNN-NNN-NNNN`
        // Row 2: Column `state` is invalid
        // Row 2: Column `Email Consent` is invalid. Needs format `Y` or `N`
        // Row 2: Column `Text Message Consent` is invalid. Needs format `Y` or `N`
        // Row 2: Column `gender` is invalid. Needs format `M` or `F` or `T` or `O`
        // Row 2: Column `DOB` is invalid. Needs format `M/D/Y`
        // Row 2: first_name must not be empty
        // Row 2: last_name must not be empty
        // Row 2: mobile_number must be a valid telephone number
        // Row 2: address must not be empty
        // Row 2: city must not be empty
        // Row 2: zip_code must be a valid postal code on "US"
        // Row 2: doctor_first_name must not be empty
        // Row 2: doctor_last_name must not be empty
        // Row 2: doctor_npi must not be empty
        // Row 2: doctor_address must not be empty
        // Row 2: doctor_city must not be empty
        // Row 2: doctor_state must be a subdivision code of United States
        // Row 2: doctor_zip_code must be a valid postal code on "US"
        // Row 2: doctor_phone_number must be a valid telephone number
    ];

    public function getType(): string {
        return 'new';
    }
    public function getRequiredHeaders(): array {
        return self::HEADER_REQUIRED;
    }
    public function performFileChecksSpecific(object $importResult, array $records): void {

    }
    public function performRowChecksSpecific(object $importResult, array $records, object $params): void {
        $dateCurr = Patient_import::getCurrentDate();
        $dateTomorrow = Patient_import::getTomorrowDate();

        // Check if requested service is a business/priority service
        $user_settings = $params->user_model->get_settings($importResult->getProviderId());
        $businessCodeRequired = in_array($importResult->getServiceId(),
            [$user_settings['settings']['business_service_id'], $user_settings['settings']['priority_service_id']]
            );

        // pick apart each customer row
        foreach ($records as $lineNum => $row) {
            // Validate specific CSV items
            try {
                // Expecting 4/3/2020
                $datetime = new DateTime($row['appt_date']);

                // Appointment cannot be in the past (today is allowed)
                if ($datetime < $dateCurr) {
                    $importResult->addRowError($lineNum, sprintf('Column `%s` with value `%s` is invalid. Date cannot be in the past', 'appt_date', $row['appt_date']));
                }
            } catch (Exception $e) {
                $importResult->addRowError($lineNum, sprintf('Column `%s` with value `%s` is invalid. Needs format `M/D/Y`', 'appt_date', $row['appt_date']));
            }

            try {
                // Expecting 9:00 AM
                $datetime = new DateTime($row['appt_time']);

                // Make sure time is on the hour
                if ($datetime->format('i:s') !== '00:00') {
                    $importResult->addRowError($lineNum, sprintf('Appointment time must start on the hour. Example: `%s:00:00 %s`', $datetime->format('g'), $datetime->format('A')));
                }
            } catch (Exception $e) {
                $importResult->addRowError($lineNum, sprintf('Column `%s` with value `%s` is invalid. Needs format `HH:MM AM`', 'appt_time', $row['appt_time']));
            }

            // Expecting 313-333-3333
            if (!preg_match('/^\d{3}-\d{3}-\d{4}$/i', $row['mobile_number'])) {
                $importResult->addRowError($lineNum, sprintf('Column `%s` with value `%s` is invalid. Needs format `NNN-NNN-NNNN`', 'mobile_number', $row['mobile_number']));
            }

            // Expecting N or Y
            if (!in_array($row['first_responder'], self::VALUES_YESNO)) {
                $importResult->addRowError($lineNum, sprintf('Column `%s` with value `%s` is invalid. Needs format `Y` or `N`', 'first_responder', $row['first_responder']));
            }

            // Expecting M or F or T or O
            if (!in_array($row['gender'], self::VALUES_GENDER)) {
                $importResult->addRowError($lineNum, sprintf('Column `%s` with value `%s` is invalid. Needs format `M` or `F` or `T` or `O`', 'gender', $row['gender']));
            }

            // If business_code is required, make sure we have them
            if ($businessCodeRequired && empty($row['business_code'])) {
                $importResult->addRowError($lineNum, sprintf('Column `%s` with value `%s` is invalid. Business code is required', 'business_code', $row['business_code']));
            }

            // If business_code is NOT required, make sure we DONT have them
            if (!$businessCodeRequired && !empty($row['business_code'])) {
                $importResult->addRowError($lineNum, sprintf('Column `%s` with value `%s` is invalid. Business code is not valid for this service', 'business_code', $row['business_code']));
            }

            // If we're given a business code and not city business code, expecting business code exists
            if ($row['business_code'] !== Config::BUSINESS_CODE_CITY_WORKER
                && !empty($row['business_code'])
                && empty($params->business_request_model->get_row_by_code($row['business_code']))) {
                $importResult->addRowError($lineNum, sprintf('Column `%s` with value `%s` is invalid. Business code not in system.', 'business_code', $row['business_code']));
            }

            try {
                // Expecting 3/3/1933
                $datetime = Patient_import::assumeFullYear(new DateTime($row['DOB']));

                if ($datetime >= $dateTomorrow) {
                    $importResult->addRowError($lineNum, sprintf('Column `%s` with value `%s` is invalid. Date cannot be in the future', 'DOB', $datetime->format('m/d/y')));
                }
            } catch (Exception $e) {
                $importResult->addRowError($lineNum, sprintf('Column `%s` with value `%s` is invalid. Needs format `M/D/Y`', 'DOB', $row['DOB']));
            }

            // Expecting N or Y
            if (!in_array($row['Email Consent'], self::VALUES_YESNO)) {
                $importResult->addRowError($lineNum, sprintf('Column `%s` with value `%s` is invalid. Needs format `Y` or `N`', 'Email Consent', $row['Email Consent']));
            }

            // Expecting N or Y
            if (!in_array($row['Text Message Consent'], self::VALUES_YESNO)) {
                $importResult->addRowError($lineNum, sprintf('Column `%s` with value `%s` is invalid. Needs format `Y` or `N`', 'Text Message Consent', $row['Text Message Consent']));
            }

            // Expecting 248-895-8562
            if (!empty($row['doctor_phone_number']) && !preg_match('/^\d{3}-\d{3}-\d{4}$/i', $row['doctor_phone_number'])) {
                $importResult->addRowError($lineNum, sprintf('Column `%s` with value `%s` is invalid. Needs format `NNN-NNN-NNNN`', 'doctor_phone_number', $row['doctor_phone_number']));
            }

            try {
                // Expecting 3/30/2020
                $datetime = Patient_import::assumeFullYear(new DateTime($row['rx_date']));

                // Cannot be a future date
                if ($datetime >= $dateTomorrow) {
                    $importResult->addRowError($lineNum, sprintf('Column `%s` with value `%s` is invalid. Date cannot be in the future', 'rx_date', $datetime->format('m/d/y')));
                }
            } catch (Exception $e) {
                $importResult->addRowError($lineNum, sprintf('Column `%s` with value `%s` is invalid. Needs format `M/D/Y`', 'rx_date', $row['rx_date']));
            }
        }
    }
    public function buildDbRecords(object $importResult, array $records, object $params): array {
        // query db table and get list of column names
        // If you model->add() without stripping out keys that don't exist in the DB
        // you will get DB insert errors.
        $dbFieldsCustomer = $params->customers_model->get_field_list();
        $dbFieldsAppointment = $params->appointments_model->get_field_list();

        // Setup Master Lists
        $masterCustomer = [];
        $masterAppointments = [];

        foreach ($records as $lineNum => $row) {

            // Start Building DB record
            $dbRow = $row;

            // Massage some actual DB fields
            try {
                $dbRow['start_datetime'] = null;
                $dateTmp = new DateTime($dbRow['appt_date'] . ' ' . $dbRow['appt_time']);
                $dbRow['start_datetime'] = $dateTmp->format('Y-m-d H:i:s');
            } catch (Exception $e) {
            } finally {
                unset($dbRow['appt_date']);
                unset($dbRow['appt_time']);
            }

            try {
                $dateTmp = new DateTime($dbRow['rx_date'] . ' 00:00');
                $dateTmp = Patient_import::assumeFullYear($dateTmp);
                $dbRow['rx_date'] = $dateTmp->format('Y-m-d H:i:s');
            } catch (Exception $e) {
                // We are reusing the same key name
                $dbRow['rx_date'] = null;
            } finally {}

            try {
                $dbRow['dob'] = null;
                $dateTmp = new DateTime($dbRow['DOB'] . ' 00:00');
                $dateTmp = Patient_import::assumeFullYear($dateTmp);
                $dbRow['dob'] = $dateTmp->format('Y-m-d H:i:s');
            } catch (Exception $e) {
            } finally {
                unset($dbRow['DOB']);
            }

            // Translate some keys and values
            $dbRow = Patient_import::translateKeysAndValues($dbRow, self::FIELDS_YESNO, self::VALUES_YESNO);
            $dbRow = Patient_import::translateKeysAndValues($dbRow, self::FIELDS_GENDER, self::VALUES_GENDER);


            // Set some required assumed fields
            $dbRow['patient_id'] = $params->customers_model->generate_unique_patient_id($dbRow['start_datetime']);
            $dbRow['caller'] = $params->customers_model->getCallByBusinessCode($dbRow['business_code']);

            // Applying whitelist
            $customer = $dbRow;
            // Add default DB fields that don't exist in CSV
            // TODO: This allows to by pass the validation rules
            // We should figure out a better way for this
            foreach ($dbFieldsCustomer as $fieldName => $fieldRow) {
                // Check if our array is missing a key and it's not an auto-incremented primary field
                if (!isset($customer[$fieldName]) && $fieldRow['Key'] !== 'PRI') {
                    $customer[$fieldName] = $fieldRow['Default'];
                }
            }

            try {
                // Check if user already exists
                if (is_numeric($params->customers_model->find_record_id($customer))) {
                    $importResult->addRowError($lineNum, sprintf('Patient `%s %s` already exists. This process is only for new patients', $customer['first_name'], $customer['last_name']));
                    continue;
                }
            } catch (Exception $e) {}


            // Run data through model validator
            $customerValidator = $params->customers_model->getValidationModel();
            // Perform validation and get messages
            try {
                $customerValidator->assert($customer);
            } catch(NestedValidationException $e) {
                Patient_import::handleErrorMessaging($importResult, $customer, $lineNum, $e, self::ERROR_CUSTOM_MESSAGES);
            }

            // Apply record to master
            $masterCustomer[] = $customer;


            // Appointment Validation
            $appointment = [
                'is_unavailable' => false,
                'id_users_provider' => $importResult->getProviderId(),
                'id_services' => $importResult->getServiceId(),
                'start_datetime' => $dbRow['start_datetime'],
                'business_code' => $dbRow['business_code'],
            ];

            try {
                // Apply 1 Hour block
                $dateTmp = new DateTime($dbRow['start_datetime']);
                // TODO: Risky to assume, we should pull value from DB
                $dateTmp->add(date_interval_create_from_date_string('1 hour'));
                $appointment['end_datetime'] = $dateTmp->format('Y-m-d H:i:s');
            } catch (Exception $e) {
                $importResult->addRowError($lineNum, 'Failed to compile end_datetime');
            }

            // Applying whitelist
            // Add default DB fields that don't exist in CSV
            foreach ($dbFieldsAppointment as $fieldName => $fieldRow ) {
                // Check if our array is missing a key and it's not an auto-incremented primary field
                if (!isset($appointment[$fieldName]) && $fieldRow['Key'] !== 'PRI') {
                    $appointment[$fieldName] = $fieldRow['Default'];
                }
            }
            // Remove fields that don't exist in DB
            $appointment = array_intersect_key($appointment, $dbFieldsAppointment);

            // TODO: Add validators from appointment model

            // Apply record to master
            $masterAppointments[] = $appointment;
        }

        // Check for master list imbalance
        // We should never get here, but just incase
        if (count($records) !== count($masterCustomer) || count($masterCustomer) !== count($masterAppointments)) {
            $importResult->addFileError('Customer and Appointment records are imbalanced');
        }

        return [
            'customer' => $masterCustomer,
            'appointment' => $masterAppointments,
        ];
    }
    public function performDbInserts(object $importResult, array $databaseRecords, object $params): void {
        // Loop through lists
        for ($i = 0; $i < count($databaseRecords['customer']); $i++) {
            try {
                $customer_id = $params->customers_model->add($databaseRecords['customer'][$i], false, $databaseRecords['appointment'][$i]);
                $databaseRecords['appointment'][$i]['id_users_customer'] = $customer_id;
                $initials = $params->customers_model->parseNameInitials($databaseRecords['customer'][$i]['first_name'], $databaseRecords['customer'][$i]['last_name']);
                $appointment_id = $params->appointments_model->add($databaseRecords['appointment'][$i], $initials);

                if (!$appointment_id) {
                    throw new Exception('AppointmentId was not returned after insert');
                }

                $importResult->addSuccessfulRow($i);
            } catch (Exception $e) {
                $importResult->addRowError($i, sprintf('DB Insert Failure: %s', $e));
            }
        }
    }
}

class ImportRescheduleParser implements Parser {
    const HEADER_REQUIRED = [
        'first_name',
        'last_name',
        'DOB',
        'previous_barcode',
        'appt_date', // Needs to be combined
        'appt_time', // Needs to be combined
        'appt_type',
    ];

    const FIELDS_APPT_TYPE = [
        'appt_type' => 'appt_type',
    ];
    const VALUES_APPT_TYPE = [
        'new' => 'new',
        'reschedule' => 'reschedule',
    ];

    public function getType(): string {
        return 'reschedule';
    }
    public function getRequiredHeaders(): array {
        return self::HEADER_REQUIRED;
    }
    public function performFileChecksSpecific(object $importResult, array $records): void {

    }
    public function performRowChecksSpecific(object $importResult, array $records, object $params): void {
        $dateCurr = Patient_import::getCurrentDate();
        $dateTomorrow = Patient_import::getTomorrowDate();

        // pick apart each customer row
        foreach ($records as $lineNum => $row) {
            try {
                // Expecting 3/3/1933
                $datetime = Patient_import::assumeFullYear(new DateTime($row['DOB']));

                if ($datetime >= $dateTomorrow) {
                    $importResult->addRowError($lineNum, sprintf('Column `%s` with value `%s` is invalid. Date cannot be in the future', 'DOB', $datetime->format('m/d/y')));
                }
            } catch (Exception $e) {
                $importResult->addRowError($lineNum, sprintf('Column `%s` with value `%s` is invalid. Needs format `M/D/Y`', 'DOB', $row['DOB']));
            }

            try {
                // Expecting 4/3/2020
                $datetime = new DateTime($row['appt_date']);

                // Appointment cannot be in the past (today is allowed)
                if ($datetime < $dateCurr) {
                    $importResult->addRowError($lineNum, sprintf('Column `%s` with value `%s` is invalid. Date cannot be in the past', 'appt_date', $row['appt_date']));
                }
            } catch (Exception $e) {
                $importResult->addRowError($lineNum, sprintf('Column `%s` with value `%s` is invalid. Needs format `M/D/Y`', 'appt_date', $row['appt_date']));
            }

            try {
                // Expecting 9:00 AM
                $datetime = new DateTime($row['appt_time']);

                // Make sure time is on the hour
                if ($datetime->format('i:s') !== '00:00') {
                    $importResult->addRowError($lineNum, sprintf('Appointment time must start on the hour. Example: `%s:00:00 %s`', $datetime->format('g'), $datetime->format('A')));
                }
            } catch (Exception $e) {
                $importResult->addRowError($lineNum, sprintf('Column `%s` with value `%s` is invalid. Needs format `HH:MM AM`', 'appt_time', $row['appt_time']));
            }

            if (!in_array($row['appt_type'], self::VALUES_APPT_TYPE)) {
                $importResult->addRowError($lineNum, sprintf('Column `%s` with value `%s` is invalid. Needs format `new` or `reschedule`', 'appt_type', $row['appt_type']));
            }
        }
    }
    public function buildDbRecords(object $importResult, array $records, object $params): array {
        $dateCurr = Patient_import::getCurrentDate();

        // Setup Master Lists
        $masterAppointments = [];

        foreach ($records as $lineNum => $row) {
            // Check and try to get patient data based on previous appt
            $previousAppointment = $params->appointments_model->get_batch('`hash` = "' . $row['previous_barcode'] . '"');
            // Get the first return
            $previousAppointment = $previousAppointment[0] ?? $previousAppointment;
            if (empty($previousAppointment)) {
                $importResult->addRowError($lineNum, sprintf('Column `%s` with value `%s` does not exist.', 'previous_barcode', $row['previous_barcode']));
                continue;
            }

            // Make sure the appointment data matches patient
            $customer = null;
            try {
                $dateTmp = Patient_import::assumeFullYear(new DateTime($row['DOB']));
                $customer = $params->customers_model->get_batch(
                    sprintf('`id` = "%s" AND DATE(`dob`) = DATE("%s") AND `first_name` = "%s" AND `last_name` = "%s"',
                    $previousAppointment['id_users_customer'],
                    $dateTmp->format('Y-m-d H:i:s'),
                    $row['first_name'],
                    $row['last_name'],
                ));
                // Get the first return
                $customer = $customer[0] ?? $customer;
            } catch (Exception $e) {}
            if (empty($customer)) {
                $importResult->addRowError($lineNum, sprintf('Patient fields `first_name`, `last_name`, `DOB` do not match the `previous_barcode` (%s).', $row['previous_barcode']));
                continue;
            }

            // Start Building DB record
            $appointment = $previousAppointment;


            // Determine if we are creating a new appointment or changing the datetime for an existing appointment
            // New Appointments:
            // * Need to remove create datetime, id, and hash. Everything else can be the same as the previous appointment.
            // Reschedule:
            // * Only changing the latest appointment (it may be different appointment referenced by the previous_barcode in the CSV)
            // * The latest appointment must not be a date in the past, we don't want to overwrite/purge data; throw error to get them to create a new appointment
            if ($row['appt_type'] === 'new') {
                unset($appointment['id']);
                unset($appointment['book_datetime']);
                unset($appointment['hash']);
            } elseif ($row['appt_type'] === 'reschedule') {
                // Let's make sure we have the latest appt.
                $appointment = $params->appointments_model->get_latest_appointment_for_customer($previousAppointment['id_users_customer']);

                // Check that it's not in the past
                try {
                    if (new DateTime($appointment['start_datetime']) < new DateTime()) {
                        $importResult->addRowError($lineNum, sprintf('Original appointment to be rescheduled has already occurred. Please mark as a `new` appointment instead.'));
                        continue;
                    }
                } catch (Exception $e) {
                    // We have already alerted the user that the date is invalid
                    continue;
                }
            } else {
                // A proper type has not been set
                continue;
            }

            try {
                // Apply new appt date
                $dateTmp = new DateTime($row['appt_date'] . ' ' . $row['appt_time']);
                $appointment['start_datetime'] = $dateTmp->format('Y-m-d H:i:s');
            } catch (Exception $e) {}

            try {
                // Apply 1 Hour block
                $dateTmp = new DateTime($appointment['start_datetime']);
                // TODO: Risky to assume, we should pull value from DB
                $dateTmp->add(date_interval_create_from_date_string('1 hour'));
                $appointment['end_datetime'] = $dateTmp->format('Y-m-d H:i:s');
            } catch (Exception $e) {
                $importResult->addRowError($lineNum, 'Failed to compile end_datetime');
            }


            $masterAppointments[] = [
                'customer' => $customer,
                'appointment' => $appointment,
            ];
        }

        // Check for master list imbalance
        if (count($records) !== count($masterAppointments)) {
            $importResult->addFileError('Not all records can be inserted, please fix the individual errors.');
        }

        return $masterAppointments;
    }
    public function performDbInserts(object $importResult, array $databaseRecords, object $params): void {
        // Loop through lists
        for ($i = 0; $i < count($databaseRecords); $i++) {
            try {
                // Process Customer
                $params->customers_model->add($databaseRecords[$i]['customer'], false, $databaseRecords[$i]['appointment']);

                // Process Appointment
                $initials = $params->customers_model->parseNameInitials($databaseRecords[$i]['customer']['first_name'], $databaseRecords[$i]['customer']['last_name']);
                $appointment_id = $params->appointments_model->add($databaseRecords[$i]['appointment'], $initials);

                if (!$appointment_id) {
                    throw new Exception('AppointmentId was not returned after insert');
                }

                $importResult->addSuccessfulRow($i);
            } catch (Exception $e) {
                $importResult->addRowError($i, sprintf('DB Insert Failure: %s', $e));
            }
        }
    }
}
