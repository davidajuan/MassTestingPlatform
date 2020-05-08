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

use Respect\Validation\Validator as v;
use Respect\Validation\Exceptions\NestedValidationException;

/**
 * Appointments Model
 *
 * @package Models
 */
class Appointments_Model extends CI_Model {
    const APPOINTMENT_ID_MAX = 999999;
    const GENERATE_APPOINTMENT_MAX_EXECUTION = 10; // seconds
    const HASH_PATTERN = "/^[\d]{6}[A-Z\d]{2}$/";

    /**
     * Add an appointment record to the database.
     *
     * This method adds a new appointment to the database. If the appointment doesn't exists it is going to be inserted,
     * otherwise the record is going to be updated.
     *
     * @param array $appointment Associative array with the appointment data. Each key has the same name with the
     * database fields.
     *
     * @return int Returns the appointments id.
     */
    public function add($appointment, $initials)
    {
        // Sanitize Data
        $appointment = $this->security->xss_clean($appointment);
        $initials = $this->security->xss_clean($initials);

        // TODO: We should insert dates, gen ids in here, before validation
        // Taking into account insert requests and update requests
        // Pre Data Massage
        $appointment = $this->preEdit($appointment);

        // Validate the appointment data before doing anything.
        $this->validate($appointment);

        // Perform insert() or update() operation.
        if ( ! isset($appointment['id']))
        {
            $appointment['hash'] = $this->generate_unique_appointment_id($initials);
            $appointment['id'] = $this->_insert($appointment);
        }
        else
        {
            $this->_update($appointment);
        }

        return $appointment['id'];
    }

    /**
     * Check if a particular appointment record already exists.
     *
     * This method checks whether the given appointment already exists in the database. It doesn't search with the id,
     * but by using the following fields: "start_datetime", "end_datetime", "id_users_provider", "id_users_customer",
     * "id_services".
     *
     * @param array $appointment Associative array with the appointment's data. Each key has the same name with the
     * database fields.
     *
     * @return bool Returns whether the record exists or not.
     *
     * @throws Exception If appointment fields are missing.
     */
    public function exists($appointment)
    {
        if ( ! isset($appointment['start_datetime'])
            || ! isset($appointment['end_datetime'])
            || ! isset($appointment['id_users_provider'])
            || ! isset($appointment['id_users_customer'])
            || ! isset($appointment['id_services']))
        {
            throw new Exception('Not all appointment field values are provided: '
                . print_r($appointment, TRUE));
        }

        $num_rows = $this->db->get_where('ea_appointments', [
            'start_datetime' => $appointment['start_datetime'],
            'end_datetime' => $appointment['end_datetime'],
            'id_users_provider' => $appointment['id_users_provider'],
            'id_users_customer' => $appointment['id_users_customer'],
            'id_services' => $appointment['id_services'],
        ])
            ->num_rows();

        return ($num_rows > 0) ? TRUE : FALSE;
    }

    /**
     * Insert a new appointment record to the database.
     *
     * @param array $appointment Associative array with the appointment's data. Each key has the same name with the
     * database fields.
     *
     * @return int Returns the id of the new record.
     *
     * @throws Exception If appointment record could not be inserted.
     */
    protected function _insert($appointment)
    {
        $appointment['book_datetime'] = date('Y-m-d H:i:s');

        if ( ! $this->db->insert('ea_appointments', $appointment))
        {
            throw new Exception('Could not insert appointment record.');
        }

        return (int)$this->db->insert_id();
    }

    /**
     * Update an existing appointment record in the database.
     *
     * The appointment data argument should already include the record ID in order to process the update operation.
     *
     * @param array $appointment Associative array with the appointment's data. Each key has the same name with the
     * database fields.
     *
     * @throws Exception If appointment record could not be updated.
     */
    protected function _update($appointment)
    {
        $this->db->where('id', $appointment['id']);
        if ( ! $this->db->update('ea_appointments', $appointment))
        {
            throw new Exception('Could not update appointment record.');
        }
    }

    /**
     * Find the database id of an appointment record.
     *
     * The appointment data should include the following fields in order to get the unique id from the database:
     * "start_datetime", "end_datetime", "id_users_provider", "id_users_customer", "id_services".
     *
     * IMPORTANT: The record must already exists in the database, otherwise an exception is raised.
     *
     * @param array $appointment Array with the appointment data. The keys of the array should have the same names as
     * the db fields.
     *
     * @return int Returns the db id of the record that matches the appointment data.
     *
     * @throws Exception If appointment could not be found.
     */
    public function find_record_id($appointment)
    {
        $this->db->where([
            'start_datetime' => $appointment['start_datetime'],
            'end_datetime' => $appointment['end_datetime'],
            'id_users_provider' => $appointment['id_users_provider'],
            'id_users_customer' => $appointment['id_users_customer'],
            'id_services' => $appointment['id_services']
        ]);

        $result = $this->db->get('ea_appointments');

        if ($result->num_rows() == 0)
        {
            throw new Exception('Could not find appointment record id.');
        }

        return $result->row()->id;
    }

    /**
     * List all field names
     *
     *  Example:
     *   ["colname"]=>
     *   array(6) {
     *       ["Field"]=> string(15) "colname"
     *       ["Type"]=> string(11) "varchar(64)"
     *       ["Null"]=> string(3) "YES"
     *       ["Key"]=> string(0) ""
     *       ["Default"]=> NULL
     *       ["Extra"]=> string(0) ""
     *   }
     * @return array list of db column names
     */
    public function get_field_list() {
        $query = $this->db->query(
            'SHOW COLUMNS
            FROM ea_appointments'
        );

        $ret = [];
        foreach ($query->result_array() as $key => $row) {
            $ret[$row['Field']] = $row;
        }

        return $ret;
    }

    /**
     * Massage data
     *
     * @param array $appointment
     * @return array
     */
    public function preEdit(array $appointment): array
    {
        // Normalize to null
        if (empty($appointment['business_code'])) {
            $appointment['business_code'] = null;
        }

        return $appointment;
    }

    /**
     * Validate appointment data before the insert or update operations are executed.
     *
     * @param array $appointment Contains the appointment data.
     *
     * @return bool Returns the validation result.
     *
     * @throws Exception If appointment validation fails.
     */
    public function validate($appointment)
    {
        $this->load->helper('data_validation');

        // Trim all whitespace
        $appointment = trim_whitespace($appointment);

        // If a appointment id is given, check whether the record exists
        // in the database.
        if (isset($appointment['id']))
        {
            $num_rows = $this->db->get_where('ea_appointments',
                ['id' => $appointment['id']])->num_rows();
            if ($num_rows == 0)
            {
                throw new Exception('Provided appointment id does not exist in the database.');
            }
        }

        $appointmentValidator = $this->getValidationModel($appointment);

        // Perform validation and get messages
        try {
            $appointmentValidator->assert($appointment);
        } catch(NestedValidationException $exception) {
            // Validation failed, let's throw an error
            $errorMessage = 'Unknown Error';

            // Parse for the first error
            $errors = $exception->getFullMessage();
            $errorMessage = $errors;

            /*
            // TODO: Take learnings from here and set better error messaging
            $errors = $e->getFullMessage();
            // These rules must pass for { "first_name": "Tam", "last_name": "Borene", "email": "fake@emai.e", "mobile_number": "(808)456 4321", "address": "1050 Woodward Ave", "apt": "", "city": "Detroit", "state": "AA", "zip_code": "N/A", "first_responder": "", ... } - At least one of these rules must pass for gender - gender must be equals "female" - gender must be equals "male" - gender must be equals "transgender" - gender must be equals "other" - state must be a subdivision code of United States - zip_code must be a valid postal code on "US" - At least one of these rules must pass for patient_consent - patient_consent must be equals "0" - patient_consent must be equals "1"

            $errors = $e->getMessages();
            $errors = print_r($errors, true);
            // Array ( [0] => At least one of these rules must pass for gender [1] => gender must be equals "female" [2] => gender must be equals "male" [3] => gender must be equals "transgender" [4] => gender must be equals "other" [5] => state must be a subdivision code of United States [6] => zip_code must be a valid postal code on "US" [7] => At least one of these rules must pass for patient_consent [8] => patient_consent must be equals "0" [9] => patient_consent must be equals "1" )

            $errors = $e->getMainMessage();
            // These rules must pass for { "first_name": "Tam", "last_name": "Borene", "email": "fake@emai.e", "mobile_number": "(808)456 4321", "address": "1050 Woodward Ave", "apt": "", "city": "Detroit", "state": "AA", "zip_code": "N/A", "first_responder": "", ... }

            $errors = $e->findMessages([]);
            $errors = print_r($errors, true);
            // Empty if you don't pass anything

            $errors = $e->findMessages(['gender']);
            $errors = print_r($errors, true);
            // Array ( [gender] => At least one of these rules must pass for gender )

            $errors = $e->getParams();
            $errors = print_r($errors, true);
            // A LOT OF GARBAGE


            // Build custom error messaging
            $customMessages = $dbRow;
            // Set all messages to default
            $customMessages = array_map(function($val) { return ''; }, $customMessages);
            // Set custom messages here
            // Source: https://stackoverflow.com/a/6562291/1583548
            $customMessages = array_intersect_key(self::ERROR_CUSTOM_MESSAGES, $customMessages) + $customMessages;
            // Fetch messages with out custom template
            $errors = $e->findMessages($customMessages);
            // Normalize array and remove any NULL values
            $errors = array_filter($errors, function($value) {
                return !is_null($value) && $value !== '';
            });

            foreach ($errors as $error) {
                $errorList[] = sprintf('Row %s: %s', $lineNum + $lineOffset, $error);
            }
            */

            throw new Exception($errorMessage);
        }

        // Check if appointment dates are valid.
        if ( ! validate_mysql_datetime($appointment['start_datetime']))
        {
            throw new Exception('Appointment start datetime is invalid.');
        }

        if ( ! validate_mysql_datetime($appointment['end_datetime']))
        {
            throw new Exception('Appointment end datetime is invalid.');
        }

        // Check if the provider's id is valid.
        $num_rows = $this->db
            ->select('*')
            ->from('ea_users')
            ->join('ea_roles', 'ea_roles.id = ea_users.id_roles', 'inner')
            ->where('ea_users.id', $appointment['id_users_provider'])
            ->where('ea_roles.slug', DB_SLUG_PROVIDER)
            ->get()->num_rows();
        if ($num_rows == 0)
        {
            throw new Exception('Appointment provider id is invalid.');
        }

        if ($appointment['is_unavailable'] == FALSE)
        {
            // Check if the customer's id is valid.
            $num_rows = $this->db
                ->select('*')
                ->from('ea_users')
                ->join('ea_roles', 'ea_roles.id = ea_users.id_roles', 'inner')
                ->where('ea_users.id', $appointment['id_users_customer'])
                ->where('ea_roles.slug', DB_SLUG_CUSTOMER)
                ->get()->num_rows();
            if ($num_rows == 0)
            {
                throw new Exception('Appointment customer id is invalid.');
            }

            // Check if the service id is valid.
            $num_rows = $this->db->get_where('ea_services',
                ['id' => $appointment['id_services']])->num_rows();
            if ($num_rows == 0)
            {
                throw new Exception('Appointment service id is invalid.');
            }

            // Check that a business_code exists if the service is a business service or priority service
            // Check that a business_code DOES NOT exist if service is NOT a business service or priority service
            $num_rows = $this->db
                ->select('*')
                ->from('ea_user_settings')
                ->where('ea_user_settings.id_users', $appointment['id_users_provider'])
                ->group_start()
                    ->or_where('ea_user_settings.business_service_id', $appointment['id_services'])
                    ->or_where('ea_user_settings.priority_service_id', $appointment['id_services'])
                ->group_end()
                ->get()->num_rows();
            if ($num_rows > 0 && empty($appointment['business_code']))
            {
                throw new Exception(sprintf('Appointment business_code is empty for this business service (%s)', $appointment['id_services']));
            }
            if ($num_rows == 0 && !empty($appointment['business_code']))
            {
                throw new Exception(sprintf('Appointment business_code is invalid for this service (%s)', $appointment['id_services']));
            }

            // Check that appointment isn't booked within a break time
            // TODO:

            // Check that an existing patient doesn't already have an identical booked appt at the sametime for the same service
            // Only apply this rule to inserts, no 'id' means it's going to be an insert
            if (!isset($appointment['id'])) {
                $num_rows = $this->db
                ->select('*')
                ->from('ea_appointments')
                ->where('ea_appointments.start_datetime', $appointment['start_datetime'])
                ->where('ea_appointments.end_datetime', $appointment['end_datetime'])
                ->where('ea_appointments.id_users_provider', $appointment['id_users_provider'])
                ->where('ea_appointments.id_users_customer', $appointment['id_users_customer'])
                ->where('ea_appointments.id_services', $appointment['id_services'])
                ->get()->num_rows();
                if ($num_rows > 0)
                {
                    throw new Exception(sprintf('Appointment already exists for this patient at this particular timeslot `%s`', $appointment['start_datetime']));
                }
            }
        }

        return TRUE;
    }

    /**
     * Validation function
     *
     * @param array $appointment
     * @return Respect\Validation\Validator
     */
    public function getValidationModel(array $appointment): Respect\Validation\Validator
    {
        $appointmentValidator =
            v::key('start_datetime', v::date('Y-m-d H:i:s'))
            ->key('end_datetime', v::date('Y-m-d H:i:s')->min($appointment['start_datetime'], false))
            // ->key('book_datetime', v::date('Y-m-d H:i:s'))
            // ->key('hash', v::alnum()->noWhitespace())
            // ->key('is_unavailable', v::oneOf(
            //         v::yes(),
            //         v::no()
            //     )
            // )
            ->key('id_users_provider', v::intVal()) // TODO: May need digit() instead
            ->key('id_users_customer', v::intVal()) // TODO: May need digit() instead
            ->key('id_services', v::intVal())
            ->key('business_code', v::optional(v::alnum()->noWhitespace())) // optional (NULL), otherwise AlphaNum
            ;

        return $appointmentValidator;
    }

    /**
     * Delete an existing appointment record from the database.
     *
     * @param int $appointment_id The record id to be deleted.
     *
     * @return bool Returns the delete operation result.
     *
     * @throws Exception If $appointment_id argument is invalid.
     */
    public function delete($appointment_id)
    {
        if ( ! is_numeric($appointment_id))
        {
            throw new Exception('Invalid value in appointment_id');
        }

        $num_rows = $this->db->get_where('ea_appointments', ['id' => $appointment_id])->num_rows();

        if ($num_rows == 0)
        {
            return FALSE; // Record does not exist.
        }

        $this->db->where('id', $appointment_id);
        return $this->db->delete('ea_appointments');
    }

    /**
     * Get a count
     *
     * @param mixed where criteria
     * @return int count of appointments
     */
    public function count($where_clause = null): int {
        if ($where_clause) {
            $this->db->where($where_clause);
        }
        $this->db->from('ea_appointments');
        return $this->db->count_all_results();
    }

    /**
     * Get a specific row from the appointments table.
     *
     * @param int $appointment_id The record's id to be returned.
     *
     * @return array Returns an associative array with the selected record's data. Each key has the same name as the
     * database field names.
     *
     * @throws Exception If $appointment_id argumnet is invalid.
     */
    public function get_row($appointment_id)
    {
        if ( ! is_numeric($appointment_id))
        {
            throw new Exception('Invalid value in appointment_id');
        }

        return $this->db->get_where('ea_appointments', ['id' => $appointment_id])->row_array();
    }

    /**
     * Get a specific field value from the database.
     *
     * @param string $field_name The field name of the value to be returned.
     * @param int $appointment_id The selected record's id.
     *
     * @return string Returns the records value from the database.
     *
     * @throws Exception If $appointment_id argument is invalid.
     * @throws Exception If $field_name argument is invalid.
     * @throws Exception If requested appointment record was not found.
     * @throws Exception If requested field name does not exist.
     */
    public function get_value($field_name, $appointment_id)
    {
        if ( ! is_numeric($appointment_id))
        {
            throw new Exception('Invalid value in appointment_id');
        }

        if ( ! is_string($field_name))
        {
            throw new Exception('Invalid value in field_name');
        }

        if ($this->db->get_where('ea_appointments', ['id' => $appointment_id])->num_rows() == 0)
        {
            throw new Exception('The record with the provided id '
                . 'does not exist in the database: ' . $appointment_id);
        }

        $row_data = $this->db->get_where('ea_appointments', ['id' => $appointment_id])->row_array();

        if ( ! isset($row_data[$field_name]))
        {
            throw new Exception('The given field name does not exist in the database: ' . $field_name);
        }

        return $row_data[$field_name];
    }

    /**
     * Get all, or specific records from appointment's table.
     *
     * @example $this->Model->getBatch('id = ' . $recordId);
     *
     * @param string $where_clause (OPTIONAL) The WHERE clause of the query to be executed. DO NOT INCLUDE 'WHERE'
     * KEYWORD.
     *
     * @param bool $aggregates (OPTIONAL) Defines whether to add aggregations or not.
     *
     * @return array Returns the rows from the database.
     */
    public function get_batch($where_clause = '', $aggregates = FALSE)
    {
        if ($where_clause != '')
        {
            $this->db->where($where_clause);
        }

        $appointments = $this->db->get('ea_appointments')->result_array();

        if ($aggregates)
        {
            foreach ($appointments as &$appointment)
            {
                $appointment = $this->get_aggregates($appointment);
            }
        }

        return $appointments;
    }

    /**
     * Get the appointments by dates and unavailable flag
     *
     * @param $where_id
     * @param $record_id
     * @param $start_date
     * @param $end_date
     * @param int $unavailable
     * @return mixed
     */
    public function get_batch_availabilities($where_id, $record_id, $start_date, $end_date, $unavailable = 1)
    {
        return $this->db->where($where_id,$record_id)
            ->group_start()
                ->where('start_datetime >', $start_date)
                ->where('start_datetime <', $end_date)
                ->or_group_start()
                    ->where('end_datetime >', $start_date)
                    ->where('end_datetime <', $end_date)
                ->group_end()
                ->or_group_start()
                    ->where('start_datetime <=', $start_date)
                    ->where('end_datetime >=', $end_date)
                ->group_end()
            ->group_end()
            ->where('is_unavailable', $unavailable)
            ->get('ea_appointments')->result_array();
    }

    /**
     * Get appointments accompanied with customer data
     *
     * @param mixed $where_clause tables ea_appointments and ea_users are exposed
     * @return array
     */
    public function get_batch_customer($where_clause = null): array
    {
        $query = $this->db
            ->select('*')
            ->from('ea_appointments')
            ->join('ea_users', 'ea_users.id = ea_appointments.id_users_customer', 'inner')
            ->where('ea_appointments.is_unavailable', false);

        if (!empty($where_clause)) {
            $query->where($where_clause);
        }

        return $query->get()->result_array();
    }

    public function get_latest_appointment_for_customer($customer_id)
    {
        $this->db->where('id_users_customer', $customer_id);
        $this->db->order_by('start_datetime', 'DESC');
        $this->db->limit(1);
        return $this->db->get('ea_appointments')->row_array();
    }

    /**
     * Gets a distinct list of appointment hours for a given day
     */
    public function get_appointment_per_day($day)
    {
        $strQuery = "select * FROM ea_appointments a LEFT JOIN ea_users u ON u.id = a.id_users_customer WHERE start_datetime LIKE ? AND is_unavailable = false ORDER BY start_datetime ASC, last_name ASC";
        $query = $this->db->query($strQuery, ['%'.$day.'%']);

        return $query->result_array();
    }

    /**
     * Get appointment by hash
     */
    public function get_appointment_by_hash($hash)
    {
        if (empty($hash) || preg_match(self::HASH_PATTERN, $hash) === 0)
        {
            throw new Exception('Invalid value in hash');
        }

        $strQuery = "select a.*, u.* FROM ea_appointments a LEFT JOIN ea_users u ON u.id = a.id_users_customer WHERE hash = ?";
        $query = $this->db->query($strQuery, [$hash]);

        return $query->row_array();
    }

    /**
     * Gets a distinct list of appointment hours for a given day
     */
    public function get_appointment_hours_of_day($day)
    {
        $hours = [];
        $strQuery = "select DISTINCT start_datetime FROM ea_appointments where start_datetime LIKE ?";
        $query = $this->db->query($strQuery, ['%'.$day.'%']);

        $appointments = $query->result_array();

        foreach ($appointments as $appointment) {
            $hours[] = $appointment['start_datetime'];
        }

        return $hours;
    }

    /**
     * Gets a distinct list of appointment hours for a given day
     */
    public function get_appointments_per_hour($start_datetime)
    {
        $strQuery = "select * FROM ea_appointments a LEFT JOIN ea_users u ON u.id = a.id_users_customer where start_datetime = ? ORDER BY last_name ASC";
        $query = $this->db->query($strQuery, [$start_datetime]);

        return $query->result_array();
    }

    /**
     * This method generates a random appointment id based on given string first name and last name
     * NOTE: This does not check against existing / uniqueness
     * Use generate_unique_patient_id() instead
     *
     * @param string $nonce
     * @return string Returns the randomly generated appoint id in format "123456JD"
     * @throws Exception nonce is too small to create an id
     */
    protected function generate_appointment_id(string $nonce): string
    {
        // Gen a random number
        $num = random_int(0, self::APPOINTMENT_ID_MAX);
        // Pad left to make 6 digits
        $num = str_pad($num, 6, "0", STR_PAD_LEFT);

        // Eval nonce
        $nonce = preg_replace('/[^a-z]/i', '', $nonce);
        $nonce = substr($nonce, 0, 2);

        if (strlen($nonce) !== 2) {
            throw new Exception('Failed to create appointment hash');
        }

        return $num . strtoupper($nonce);
    }

    /**
     * This method generates a unique and random appointment id based on given string first name and last name
     *
     * @param string $initials
     * @return string Returns the randomly generated appoint id in format "123456JD"
     */
    public function generate_unique_appointment_id(string $initials): string
    {
        $id = null;

        $startTime = time();
        for ($i = 0; $i <= self::APPOINTMENT_ID_MAX; $i++) {
            // Gen tmp id
            $tmp = $this->generate_appointment_id($initials);

            // Check id for uniqueness
            // $tmp is a controlled value, no security issue here
            $patientQuery = $this->db->query('SELECT * FROM `ea_appointments` WHERE `hash` = ? LIMIT 1', [$tmp]);
            if ($patientQuery->num_rows() === 0) {
                // Break out of loop, when we have a legit unique id
                $id = $tmp;
                break;
            }

            // Check if we surpassed the execution time
            if ($startTime + self::GENERATE_APPOINTMENT_MAX_EXECUTION < time()) {
                throw new Exception('Generate id execution exceeded');
            }
        }

        // id is still null, we failed in getting an id
        if ($id === null) {
            throw new Exception('Ran out of unique ids');
        }

        return $id;
    }

    /**
     * Inserts or updates an unavailable period record in the database.
     *
     * @param array $unavailable Contains the unavailable data.
     *
     * @return int Returns the record id.
     *
     * @throws Exception If unavailability validation fails.
     * @throws Exception If provider record could not be found in database.
     */
    public function add_unavailable($unavailable)
    {
        // Validate period
        $start = strtotime($unavailable['start_datetime']);
        $end = strtotime($unavailable['end_datetime']);
        if ($start > $end)
        {
            throw new Exception('Unavailable period start must be prior to end.');
        }

        // Validate provider record
        $where_clause = [
            'id' => $unavailable['id_users_provider'],
            'id_roles' => $this->db->get_where('ea_roles', ['slug' => DB_SLUG_PROVIDER])->row()->id
        ];

        if ($this->db->get_where('ea_users', $where_clause)->num_rows() == 0)
        {
            throw new Exception('Provider id was not found in database.');
        }

        // Add record to database (insert or update).
        if ( ! isset($unavailable['id']))
        {
            $unavailable['book_datetime'] = date('Y-m-d H:i:s');
            $unavailable['is_unavailable'] = TRUE;

            $this->db->insert('ea_appointments', $unavailable);
            $unavailable['id'] = $this->db->insert_id();
        }
        else
        {
            $this->db->where(['id' => $unavailable['id']]);
            $this->db->update('ea_appointments', $unavailable);
        }

        return $unavailable['id'];
    }

    /**
     * Delete an unavailable period.
     *
     * @param int $unavailable_id Record id to be deleted.
     *
     * @return bool Returns the delete operation result.
     *
     * @throws Exception If $unavailable_id argument is invalid.
     */
    public function delete_unavailable($unavailable_id)
    {
        if ( ! is_numeric($unavailable_id))
        {
            throw new Exception('Invalid value in unavailable_id');
        }

        $num_rows = $this->db->get_where('ea_appointments', ['id' => $unavailable_id])->num_rows();

        if ($num_rows == 0)
        {
            return FALSE; // Record does not exist.
        }

        $this->db->where('id', $unavailable_id);

        return $this->db->delete('ea_appointments');
    }

    /**
     * Clear google sync IDs from appointment record.
     *
     * @param int $provider_id The appointment provider record id.
     *
     * @throws Exception If $provider_id argument is invalid.
     */
    public function clear_google_sync_ids($provider_id)
    {
        if ( ! is_numeric($provider_id))
        {
            throw new Exception('Invalid value in provider_id');
        }

        $this->db->update('ea_appointments', ['id_google_calendar' => NULL],
            ['id_users_provider' => $provider_id]);
    }

    /**
     * Get appointment count for the provided start datetime.
     *
     * @param int $service_id Selected service ID.
     * @param string $selected_date Selected date string.
     * @param string $hour Selected hour string.
     *
     * @return int Returns the appointment number at the selected start time.
     */
    public function appointment_count_for_hour($service_id, $selected_date, $hour)
    {
        return $this->db->get_where('ea_appointments', [
            'id_services' => $service_id,
            'start_datetime' => date('Y-m-d H:i:s', strtotime($selected_date . ' ' . $hour . ':00'))
        ])->num_rows();
    }

    /**
     * Returns the attendants number for selection period.
     *
     * @param DateTime $slot_start When the slot starts
     * @param DateTime $slot_end When the slot ends.
     * @param int $service_id Selected service ID.
     *
     * @return int Returns the number of attendants for selected time period.
     */
    public function get_attendants_number_for_period(DateTime $slot_start, DateTime $slot_end, $service_id)
    {
        return (int)$this->db
            ->select('count(*) AS attendants_number')
            ->from('ea_appointments')
            ->group_start()
            ->group_start()
            ->where('start_datetime <=', $slot_start->format('Y-m-d H:i:s'))
            ->where('end_datetime >', $slot_start->format('Y-m-d H:i:s'))
            ->group_end()
            ->or_group_start()
            ->where('start_datetime <', $slot_end->format('Y-m-d H:i:s'))
            ->where('end_datetime >=', $slot_end->format('Y-m-d H:i:s'))
            ->group_end()
            ->group_end()
            ->where('id_services', $service_id)
            ->get()
            ->row()
            ->attendants_number;
    }

    /**
     * Returns the attendants number override for a given service and time frame.
     *
     * @param DateTime $slot_start When the slot starts
     * @param DateTime $slot_end When the slot ends.
     * @param int $service_id Selected service ID.
     *
     * @return int Returns the override number of attendants for a given service and time frame.
     */
    public function get_attendants_override(DateTime $slot_start, DateTime $slot_end, $service_id)
    {
        $row = $this->db
        ->select('attendants_number')
        ->from('ea_service_capacity')
        ->group_start()
        ->group_start()
        ->where('start_datetime <=', $slot_start->format('Y-m-d H:i:s'))
        ->where('end_datetime >', $slot_start->format('Y-m-d H:i:s'))
        ->group_end()
        ->or_group_start()
        ->where('start_datetime <', $slot_end->format('Y-m-d H:i:s'))
        ->where('end_datetime >=', $slot_end->format('Y-m-d H:i:s'))
        ->group_end()
        ->group_end()
        ->where('id_services', $service_id)
        ->get()
        ->row();

        return $row ? (int) $row->attendants_number : false;
    }



    /**
     * Get the aggregates of an appointment.
     *
     * @param array $appointment Appointment data.
     *
     * @return array Returns the appointment with the aggregates.
     */
    private function get_aggregates(array $appointment)
    {
        $appointment['service'] = $this->db->get_where('ea_services',
            ['id' => $appointment['id_services']])->row_array();
        $appointment['provider'] = $this->db->get_where('ea_users',
            ['id' => $appointment['id_users_provider']])->row_array();
        $appointment['customer'] = $this->db->get_where('ea_users',
            ['id' => $appointment['id_users_customer']])->row_array();
        return $appointment;
    }

    /**
     * Get a count of scheduled appointments in a date range.
     *
     * @param DateTime $startDate Starting date (inclusive)
     * @param DateTime $endDate Ending date (inclusive)
     * @param array $services Array of service ints
     * @param array|null $where_clause Pass in additive ea_appointments/ea_users columns => values to use in query
     *
     * @return int count of scheduled appointments
     */
    public function count_scheduled_appointments(DateTime $startDate, DateTime $endDate, $services, ?array $where_clause = null) {
        $query = $this->db
            ->select('*')
            ->from('ea_appointments')
            ->join('ea_users', 'ea_users.id = ea_appointments.id_users_customer', 'inner')
            ->where('ea_appointments.start_datetime >=', $startDate->format('Y-m-d H:i:s'))
            ->where('ea_appointments.start_datetime <=', $endDate->format('Y-m-d H:i:s'))
            ->where('ea_appointments.is_unavailable', false)
            ->where_in('ea_appointments.id_services', $services);

        if ($where_clause) {
            $query->where($where_clause);
        }

        $num_rows = $query->get()->num_rows();
        return $num_rows;
    }

    /**
     * Get a count of appointments created on a day - book_datetime
     * Not to be confused with appointments booked for a specific day - start_datetime
     *
     * @param DateTime $date
     * @param array $services Array of service ints
     * @param array $criteria Pass in additive ea_appointments/ea_users columns => values to use in query
     * @return int count of created appointments
     */
    public function count_created_appointments(DateTime $date, $services, ?array $criteria = []) {
        $query = $this->db
            ->select('*')
            ->from('ea_appointments')
            ->join('ea_users', 'ea_users.id = ea_appointments.id_users_customer', 'inner')
            ->where('DATE(ea_appointments.book_datetime)', $date->format('Y-m-d'))
            ->where('ea_appointments.is_unavailable', false)
            ->where_in('ea_appointments.id_services', $services);

        if (is_array($criteria)) {
            foreach ($criteria as $col => $val) {
                $query->where($col, $val);
            }
        }

        $num_rows = $query->get()->num_rows();
        return $num_rows;
    }

    /**
     * Return appointments and it's patients within a date range.
     * Date range is for the create date of the record; the date the patient/provider called in
     *
     * @param int $providerId Selected provider id
     * @param int $serviceId Selected service id
     * @param DateTime $dateStart start date (inclusive) can be null
     * @param DateTime $dateEnd end date (inclusive) can be null
     * @return array associated array
     */
    public function get_created_ranged_appointments(int $providerId, int $serviceId, ?DateTime $dateStart = null, ?DateTime $dateEnd = null) {
        $query = $this->db
            ->select('*')
            ->from('ea_appointments')
            ->join('ea_users', 'ea_users.id = ea_appointments.id_users_customer', 'inner')
            ->where('ea_appointments.is_unavailable', false)
            ->where('ea_appointments.id_services', $serviceId)
            ->where('ea_appointments.id_users_provider', $providerId);

        // Determine range
        if ($dateStart !== null && $dateEnd === null) {
            // Get single date
            $query->where('DATE(ea_appointments.book_datetime)', $dateStart->format('Y-m-d'));
        }
        elseif ($dateStart !== null && $dateEnd !== null) {
            // Get everything in range (inclusive)
            $query->where('DATE(ea_appointments.book_datetime) >=', $dateStart->format('Y-m-d'));
            $query->where('DATE(ea_appointments.book_datetime) <=', $dateEnd->format('Y-m-d'));
        }
        else {
            // Get All records
        }

        return $query->get()->result_array();
    }

    /**
     * Get available appointments longrange.
     *
     * This method will get the total number of appointments available in the following x days
     *
     * @param string $selected_date Starting date
     * @param array $service Selected service data.
     * @param array $provider Selected provider data.
     * @param int $days How many days out to check
     * @param bool $inclusive If we should include the totals from the specified $selected_date
     *
     * @return array Returns the available hours array and available slots in that hour array.
     */
    public function get_available_appointments_longrange(
        $selected_date,
        $service,
        $provider,
        $days = 7,
        bool $inclusive = false
    ) {
        $this->load->model('appointments_model');

        $selected_date = date("Y-m-d", strtotime($selected_date));

        $date_range = [];
        // See if we need to include the selected_date
        if ($inclusive) {
            $date_range[] = $selected_date;
        }

        // get the list of date ranges 7 days from now
        for($day = 1; $day <= $days; $day++) {
            $date = strtotime("+$day day");
            //skip sunday
            if (date("D", $date) == "Sun") {
                $days++;
            } else {
                $date_range[] = date('Y-m-d', $date);
            }
        }

        $appointments_remaining = $num_days = 0;

        foreach ($date_range as $selected_date) {
            $num_days++;

            $unavailabilities = $this->appointments_model->get_batch([
                'is_unavailable' => TRUE,
                'DATE(start_datetime)' => $selected_date,
                'id_users_provider' => $provider['id']
            ]);

            $working_plan = json_decode($provider['settings']['working_plan'], TRUE);
            $working_day = strtolower(date('l', strtotime($selected_date)));
            $working_hours = $working_plan[$working_day];

            if ($working_hours === null) {
                continue;
            }

            $periods = [
                [
                    'start' => new DateTime($selected_date . ' ' . $working_hours['start']),
                    'end' => new DateTime($selected_date . ' ' . $working_hours['end'])
                ]
            ];

            $periods = $this->remove_breaks($selected_date, $periods, $working_hours['breaks']);
            $periods = $this->remove_unavailabilities($periods, $unavailabilities);

            $interval_value = $service['availabilities_type'] == AVAILABILITIES_TYPE_FIXED ? $service['duration'] : '60';
            $interval = new DateInterval('PT' . (int)$interval_value . 'M');
            $duration = new DateInterval('PT' . (int)$service['duration'] . 'M');

            foreach ($periods as $period)
            {
                $slot_start = clone $period['start'];
                $slot_end = clone $slot_start;
                $slot_end->add($duration);

                while ($slot_end <= $period['end'])
                {
                    // Check reserved attendants for this time slot and see if current attendants fit.
                    $appointment_attendants_number = $this->appointments_model->get_attendants_number_for_period($slot_start,
                        $slot_end, $service['id']);

                    $appointment_override_number = $this->appointments_model->get_attendants_override($slot_start,
                    $slot_end, $service['id']);

                    // If there is an override in the database, use that instead
                    if ($appointment_override_number !== false) {
                        $service_attendants_number = $appointment_override_number;
                    } else {
                        $service_attendants_number = $service['attendants_number'];
                    }

                    if ($appointment_attendants_number < $service_attendants_number)
                    {
                        $appointments_remaining += $service_attendants_number - $appointment_attendants_number;
                    }

                    $slot_start->add($interval);
                    $slot_end->add($interval);
                }
            }
        }

        return ["appointments_remaining" => $appointments_remaining, "days" => $num_days];
    }

    /**
     * Remove breaks from available time periods.
     *
     * @param string $selected_date Selected data (Y-m-d format).
     * @param array $periods Time periods of the current date.
     * @param array $breaks Breaks array for the current date.
     *
     * @return array Returns the available time periods without the breaks.
     */
    public function remove_breaks($selected_date, $periods, $breaks)
    {
        if ( ! $breaks)
        {
            return $periods;
        }

        foreach ($breaks as $break)
        {
            $break_start = new DateTime($selected_date . ' ' . $break['start']);
            $break_end = new DateTime($selected_date . ' ' . $break['end']);

            foreach ($periods as &$period)
            {
                $period_start = $period['start'];
                $period_end = $period['end'];

                if ($break_start <= $period_start && $break_end >= $period_start && $break_end <= $period_end)
                {
                    // left
                    $period['start'] = $break_end;
                    continue;
                }

                if ($break_start >= $period_start && $break_start <= $period_end && $break_end >= $period_start && $break_end <= $period_end)
                {
                    // middle
                    $period['end'] = $break_start;
                    $periods[] = [
                        'start' => $break_end,
                        'end' => $period_end
                    ];
                    continue;
                }

                if ($break_start >= $period_start && $break_start <= $period_end && $break_end >= $period_end)
                {
                    // right
                    $period['end'] = $break_start;
                    continue;
                }

                if ($break_start <= $period_start && $break_end >= $period_end)
                {
                    // break contains period
                    $period['start'] = $break_end;
                    continue;
                }
            }
        }

        return $periods;
    }


    /**
     * Remove the unavailabilities from the available time periods of the selected date.
     *
     * @param array $periods Available time periods.
     * @param array $unavailabilities Unavailabilities of the current date.
     *
     * @return array Returns the available time periods without the unavailabilities.
     */
    public function remove_unavailabilities($periods, $unavailabilities)
    {
        foreach ($unavailabilities as $unavailability)
        {
            $unavailability_start = new DateTime($unavailability['start_datetime']);
            $unavailability_end = new DateTime($unavailability['end_datetime']);

            foreach ($periods as &$period)
            {
                $period_start = $period['start'];
                $period_end = $period['end'];

                if ($unavailability_start <= $period_start && $unavailability_end >= $period_start && $unavailability_end <= $period_end)
                {
                    // left
                    $period['start'] = $unavailability_end;
                    continue;
                }

                if ($unavailability_start >= $period_start && $unavailability_start <= $period_end && $unavailability_end >= $period_start && $unavailability_end <= $period_end)
                {
                    // middle
                    $period['end'] = $unavailability_start;
                    $periods[] = [
                        'start' => $unavailability_end,
                        'end' => $period_end
                    ];
                    continue;
                }

                if ($unavailability_start >= $period_start && $unavailability_start <= $period_end && $unavailability_end >= $period_end)
                {
                    // right
                    $period['end'] = $unavailability_start;
                    continue;
                }

                if ($unavailability_start <= $period_start && $unavailability_end >= $period_end)
                {
                    // Unavaibility contains period
                    $period['start'] = $unavailability_end;
                    continue;
                }
            }
        }

        return $periods;
    }


}
