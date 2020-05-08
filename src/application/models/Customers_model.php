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
 * Customers Model
 *
 * @package Models
 */
class Customers_Model extends CI_Model {

    // Table fields
    const FIELD_NAME_FIRST = 'first_name';
    const FIELD_NAME_LAST = 'last_name';
    const FIELD_DOB = 'dob';

    const PATIENT_ID_MAX = 999999;
    const GENERATE_PATIENT_MAX_EXECUTION = 10; // seconds

    /**
     * Add a customer record to the database.
     *
     * This method adds a customer to the database. If the customer doesn't exists it is going to be inserted, otherwise
     * the record is going to be updated.
     *
     * @param array $customer Associative array with the customer's data. Each key has the same name with the database fields
     * @param boolean $overrideEmpty
     * @param array|null $appointment optional data used to generate data for the customer object
     * @return int customer id
     */
    public function add(array $customer, bool $overrideEmpty = false, ?array $appointment = null): int
    {
        // Sanitize Data
        $customer = $this->security->xss_clean($customer);
        $appointment = !empty($appointment) ? $this->security->xss_clean($appointment) : $appointment;

        // Pre Data Massage
        $customer = $this->preEdit($customer, $appointment);

        // Validate the customer data before doing anything.
        $this->validate($customer);

        // :: CHECK IF CUSTOMER ALREADY EXIST
        if ($this->exists($customer) && ! isset($customer['id']))
        {
            // Find the customer id from the database.
            $customer['id'] = $this->find_record_id($customer);
        }

        $dbFieldsCustomer = $this->get_field_list();
        // Remove fields that don't exist in DB
        $customer = array_intersect_key($customer, $dbFieldsCustomer);

        // :: INSERT OR UPDATE CUSTOMER RECORD
        if ( ! isset($customer['id']))
        {
            $customer['id'] = $this->_insert($customer);
        }
        else
        {
            $this->_update($customer, $overrideEmpty);
        }

        return $customer['id'];
    }

    /**
     * Check if a particular customer record already exists.
     *
     * This method checks whether the given customer already exists in the database. It doesn't search with the id, but
     * with the following fields:
     *  FIELD_NAME_FIRST
     *  FIELD_NAME_LAST
     *  FIELD_DOB
     *
     * @param array $customer Associative array with the customer's data. Each key has the same name with the database
     * fields.
     *
     * @return bool Returns whether the record exists or not.
     *
     * @throws Exception If key fields are is missing.
     */
    public function exists($customer)
    {
        if (!isset($customer[self::FIELD_NAME_LAST])
            || !isset($customer[self::FIELD_DOB])
            || !isset($customer[self::FIELD_NAME_FIRST]))
        {
            throw new Exception(sprintf('%s, %s, %s was not provided.', self::FIELD_NAME_LAST, self::FIELD_DOB, self::FIELD_NAME_FIRST));
        }

        // This method shouldn't depend on another method of this class.
        $num_rows = $this->db
            ->select('*')
            ->from('ea_users')
            ->join('ea_roles', 'ea_roles.id = ea_users.id_roles', 'inner')
            ->where('ea_users.'.self::FIELD_NAME_LAST, $customer[self::FIELD_NAME_LAST])
            ->where('ea_users.'.self::FIELD_DOB, $customer[self::FIELD_DOB])
            ->where('ea_users.'.self::FIELD_NAME_FIRST, $customer[self::FIELD_NAME_FIRST])
            ->where('ea_roles.slug', DB_SLUG_CUSTOMER)
            ->get()->num_rows();

        return ($num_rows > 0) ? TRUE : FALSE;
    }

    /**
     * Insert a new customer record to the database.
     *
     * @param array $customer Associative array with the customer's data. Each key has the same name with the database
     * fields.
     *
     * @return int Returns the id of the new record.
     *
     * @throws Exception If customer record could not be inserted.
     */
    protected function _insert($customer)
    {
        // Before inserting the customer we need to get the customer's role id
        // from the database and assign it to the new record as a foreign key.
        $customer_role_id = $this->db
            ->select('id')
            ->from('ea_roles')
            ->where('slug', DB_SLUG_CUSTOMER)
            ->get()->row()->id;

        $customer['id_roles'] = $customer_role_id;

        if ( ! $this->db->insert('ea_users', $customer))
        {
            throw new Exception('Could not insert customer to the database.');
        }

        return (int)$this->db->insert_id();
    }

    /**
     * Update an existing customer record in the database.
     *
     * The customer data argument should already include the record ID in order to process the update operation.
     *
     * @param array $customer Associative array with the customer's data. Each key has the same name with the database
     * fields.
     *
     * @return int Returns the updated record ID.
     *
     * @throws Exception If customer record could not be updated.
     */
    protected function _update($customer, $overrideEmpty = false)
    {
        if (!$overrideEmpty) {
            // Do not update empty string values.
            foreach ($customer as $key => $value)
            {
                if ($value === '')
                {
                    unset($customer[$key]);
                }
            }
        }

        $this->db->where('id', $customer['id']);
        if ( ! $this->db->update('ea_users', $customer))
        {
            throw new Exception('Could not update customer to the database.');
        }

        return (int)$customer['id'];
    }

    /**
     * Find the database id of a customer record.
     *
     * The customer data should include the following fields in order to get the unique id from the database:
     *  FIELD_NAME_FIRST
     *  FIELD_NAME_LAST
     *  FIELD_DOB
     *
     * IMPORTANT: The record must already exists in the database, otherwise an exception is raised.
     *
     * @param array $customer Array with the customer data. The keys of the array should have the same names as the
     * database fields.
     *
     * @return int Returns the ID.
     *
     * @throws Exception If customer record does not exist.
     */
    public function find_record_id($customer)
    {
        if (!isset($customer[self::FIELD_NAME_LAST])
            || !isset($customer[self::FIELD_DOB])
            || !isset($customer[self::FIELD_NAME_FIRST]))
        {
            throw new Exception(sprintf('%s, %s, %s was not provided.', self::FIELD_NAME_LAST, self::FIELD_DOB, self::FIELD_NAME_FIRST));
        }

        // Get customer's role id
        $result = $this->db
            ->select('ea_users.id')
            ->from('ea_users')
            ->join('ea_roles', 'ea_roles.id = ea_users.id_roles', 'inner')
            ->where('ea_users.'.self::FIELD_NAME_LAST, $customer[self::FIELD_NAME_LAST])
            ->where('ea_users.'.self::FIELD_DOB, $customer[self::FIELD_DOB])
            ->where('ea_users.'.self::FIELD_NAME_FIRST, $customer[self::FIELD_NAME_FIRST])
            ->where('ea_roles.slug', DB_SLUG_CUSTOMER)
            ->get();

        if ($result->num_rows() == 0)
        {
            throw new Exception('Could not find customer record id.');
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
            FROM ea_users'
        );

        $ret = [];
        foreach ($query->result_array() as $key => $row) {
            $ret[$row['Field']] = $row;
        }

        return $ret;
    }

    /**
     * Helper function to massage data before validation and insert/update
     * Easily apply business logic to perform on matching conditions
     *
     * @param array $customer
     * @param array|null $appointment optional data used to generate data for the customer object
     * @return array New customer with massaged data
     */
    public function preEdit(array $customer, ?array $appointment): array
    {
        // Lookup business to see if there is a matching doctor in the database
        if (isset($appointment['business_code'])) {
            $this->load->model('business_request_model');
            $this->load->model('business_model');
            $business_request = $this->business_request_model->getBusinessFromBusinessCode($appointment['business_code']);
            $business_doctor = !empty($business_request) ?
                $this->business_model->getBusinessDoctorInfo($business_request['id_business']) : [];

            if (!empty($business_doctor)) {
                $customer['doctor_first_name'] = $business_doctor['doctor_first_name'];
                $customer['doctor_last_name'] = $business_doctor['doctor_last_name'];
                $customer['doctor_npi'] = $business_doctor['doctor_npi'];
                $customer['doctor_address'] = $business_doctor['doctor_address'];
                $customer['doctor_city'] = $business_doctor['doctor_city'];
                $customer['doctor_state'] = $business_doctor['doctor_state'];
                $customer['doctor_zip_code'] = $business_doctor['doctor_zip_code'];
                $customer['doctor_phone_number'] = $business_doctor['doctor_phone_number'];
            }
        }

        return $customer;
    }

    /**
     * Validate customer data before the insert or update operation is executed.
     *
     * @param array $customer Contains the customer data.
     *
     * @return bool Returns the validation result.
     *
     * @throws Exception If customer validation fails.
     */
    public function validate($customer)
    {
        $this->load->helper('data_validation');

        // Trim all whitespace
        $customer = trim_whitespace($customer);

        // If a customer id is provided, check whether the record
        // exist in the database.
        if (isset($customer['id']))
        {
            $num_rows = $this->db->get_where('ea_users',
                ['id' => $customer['id']])->num_rows();
            if ($num_rows == 0)
            {
                throw new Exception('Provided customer id does not '
                    . 'exist in the database.');
            }
        }

       $customerValidator = $this->getValidationModel();

        // Perform validation and get messages
        try {
            $customerValidator->assert($customer);
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

        return TRUE;
    }

    public function getValidationModel() {
        $checkboxValidator = v::stringType()->oneOf(
            v::equals('0'),
            v::equals('1')
        );

        $customerValidator =
            v::key('address', v::stringType()->notEmpty())
            ->key('city', v::stringType()->notEmpty())
            ->key('apt', v::optional(v::stringType()))
            ->key('dob', v::date()->minimumAge(16)->min('-120 years'))
            // TODO: Is this required for provider, but not patient?
            ->when(
                    v::key('caller', v::equals('provider')), // if
                    v::key('doctor_npi', v::stringType()->notEmpty()), //then
                    v::key('doctor_npi', v::optional(v::stringType()) // else
                )
            )
            ->when (
                v::oneOf(
                    v::key('city_worker', v::equals('1')),
                    v::key('caller', v::equals(CALLER_TYPE_CIE))
                ), // if

                // if there is a business code, doctor fields are optional
                v::key('doctor_first_name', v::optional(v::stringType()->notEmpty())) // then
                ->key('doctor_last_name', v::optional(v::stringType()->notEmpty()))
                ->key('doctor_phone_number', v::optional(v::phone()))
                ->key('doctor_address', v::optional(v::stringType()->notEmpty()))
                ->key('doctor_city', v::optional(v::stringType()->notEmpty()))
                ->key('doctor_state', v::optional(v::subdivisionCode('US')))
                ->key('doctor_zip_code', v::optional(v::postalCode('US')))
                ->key('rx_date', v::optional(v::date('Y-m-d H:i:s')->max('now')->min('-120 years'))),

                v::key('doctor_first_name', v::stringType()->notEmpty()) // else
                ->key('doctor_last_name', v::stringType()->notEmpty())
                ->key('doctor_phone_number', v::phone())
                ->key('doctor_address', v::stringType()->notEmpty())
                ->key('doctor_city', v::stringType()->notEmpty())
                ->key('doctor_state', v::subdivisionCode('US'))
                ->key('doctor_zip_code', v::postalCode('US'))
                ->key('rx_date', v::date('Y-m-d H:i:s')->max('now')->min('-120 years'))
            )

            ->key('pcp_first_name', v::optional(v::stringType()))
            ->key('pcp_last_name', v::optional(v::stringType()))
            ->key('pcp_phone_number', v::optional(v::phone()))
            ->key('pcp_address', v::optional(v::stringType()))
            ->key('pcp_city', v::optional(v::stringType()))
            ->key('pcp_state', v::optional(v::subdivisionCode('US')))
            ->key('pcp_zip_code', v::optional(v::postalCode('US')))

            // FIXME: Should this `patient_id` be here?
            //->key('patient_id', v::stringType())
            ->key('email', v::optional(v::email()))
            ->key('first_name', v::stringType()->notEmpty())
            ->key('last_name', v::stringType()->notEmpty())
            ->key('gender', v::stringType()->oneOf(
                    v::equals('female'),
                    v::equals('male'),
                    v::equals('transgender'),
                    v::equals('other')
                )
            )
            ->key('middle_initial', v::optional(v::stringType()))
            ->key('ssn', v::optional(v::digit()->length(4, 4)))
            ->key('mobile_number', v::phone())
            ->key('phone_number', v::optional(v::phone()))
            ->key('provider_patient_id', v::optional(v::stringType()))
            ->key('state', v::subdivisionCode('US'))
            ->key('zip_code', v::postalCode('US'))
            ->key('county', v::optional(v::stringType()))
            ->key('patient_consent', $checkboxValidator)
            ->key('patient_consent_sms', $checkboxValidator)
            ->key('first_responder', $checkboxValidator)

            ->key('caller', v::stringType()->oneOf(
                    v::equals(CALLER_TYPE_PATIENT),
                    v::equals(CALLER_TYPE_PROVIDER),
                    v::equals(CALLER_TYPE_CIE)
                )
            );
        return $customerValidator;
    }

    /**
     * Delete an existing customer record from the database.
     *
     * @param int $customer_id The record id to be deleted.
     *
     * @return bool Returns the delete operation result.
     *
     * @throws Exception If $customer_id argument is invalid.
     */
    public function delete($customer_id)
    {
        if ( ! is_numeric($customer_id))
        {
            throw new Exception('Invalid value in customer_id');
        }

        $num_rows = $this->db->get_where('ea_users', ['id' => $customer_id])->num_rows();
        if ($num_rows == 0)
        {
            return FALSE;
        }

        return $this->db->delete('ea_users', ['id' => $customer_id]);
    }

    /**
     * Get a specific row from the user table.
     *
     * @param int $id The record's id to be returned.
     * @param string $key Tables column name to key off of
     *
     * @return array Returns an associative array with the selected record's data. Each key has the same name as the
     * database field names.
     *
     * @throws Exception If $id or $key is invalid.
     */
    public function get_row($id, $key = 'id')
    {
        if ( !in_array($key, ['id', 'patient_id']))
        {
            throw new Exception('Invalid value in key');
        }

        if (!is_numeric($id))
        {
            throw new Exception('Invalid value in id');
        }
        return $this->db->get_where('ea_users', [$key => $id])->row_array();
    }

    /**
     * Get a count
     *
     * @param mixed where criteria
     * @return int count
     */
    public function count($where_clause = null): int {
        if ($where_clause) {
            $this->db->where($where_clause);
        }
        $this->db->from('ea_users');
        return $this->db->count_all_results();
    }

    /**
     * Get a specific field value from the database.
     *
     * @param string $field_name The field name of the value to be returned.
     * @param int $customer_id The selected record's id.
     *
     * @return string Returns the records value from the database.
     *
     * @throws Exception If $customer_id argument is invalid.
     * @throws Exception If $field_name argument is invalid.
     * @throws Exception If requested customer record does not exist in the database.
     * @throws Exception If requested field name does not exist in the database.
     */
    public function get_value($field_name, $customer_id)
    {
        if ( ! is_numeric($customer_id))
        {
            throw new Exception('Invalid value in customer_id');
        }

        if ( ! is_string($field_name))
        {
            throw new Exception('Invalid value in field_name');
        }

        if ($this->db->get_where('ea_users', ['id' => $customer_id])->num_rows() == 0)
        {
            throw new Exception('The record with the $customer_id argument '
                . 'does not exist in the database: ' . $customer_id);
        }

        $row_data = $this->db->get_where('ea_users', ['id' => $customer_id]
        )->row_array();
        if ( ! isset($row_data[$field_name]))
        {
            throw new Exception('The given $field_name argument does not'
                . 'exist in the database: ' . $field_name);
        }

        $customer = $this->db->get_where('ea_users', ['id' => $customer_id])->row_array();

        return $customer[$field_name];
    }

    /**
     * Get all, or specific records from appointment's table.
     *
     * @example $this->Model->getBatch('id = ' . $recordId);
     *
     * @param string $where_clause
     * @param array $search
     * @return array Returns the rows from the database.
     * @internal param string $whereClause (OPTIONAL) The WHERE clause of the query to be executed. DO NOT INCLUDE 'WHERE'
     * KEYWORD.
     *
     */
    public function get_batch($where_clause = '', $search = [])
    {
        $customers_role_id = $this->get_customers_role_id();

        if ($where_clause != '')
        {
            $this->db->where($where_clause);
        }

        if (!empty($search)) {
            $this->db->group_start()
                ->or_like($search)
                ->group_end();
        }

        $this->db->where('id_roles', $customers_role_id);

        return $this->db->get('ea_users')->result_array();
    }

    /**
     * Get the customers role id from the database.
     *
     * @return int Returns the role id for the customer records.
     */
    public function get_customers_role_id()
    {
        return $this->db->get_where('ea_roles', ['slug' => DB_SLUG_CUSTOMER])->row()->id;
    }

    /**
     * This method generates a random patient id based on given date string "YY-MM-DD"
     * NOTE: This does not check against existing / uniqueness
     * Use generate_unique_patient_id() instead
     *
     * @param string $date date string "YY-MM-DD" to be used in id
     * @return string Returns the randomly generated patient id in format "YYMMDDXXXXXX"
     * @throws Exception Invalid Date
     */
    protected function generate_patient_id(string $date): string
    {
        // Gen a random number
        $num = random_int(0, self::PATIENT_ID_MAX);
        // Pad left to make 6 digits
        $num = str_pad($num, 6, "0", STR_PAD_LEFT);

        // Convert date
        $dateTime = new DateTime($date);

        return $dateTime->format('ymd') . $num;
    }



    /**
     * This method generates a unique and random patient id based on given date string "YY-MM-DD"
     *
     * @param string $date date string "YY-MM-DD" to be used in id
     * @return string Returns the randomly generated patient id in format "YYMMDDXXXXXX"
     * @throws Exception Database errors, Ran out of unique numbers, Invalid date argument
     */
    public function generate_unique_patient_id(string $date): string
    {
        $id = null;

        // Playing with fire here, so let's handle appropriately
        // We might not have a connection to the database or be able to query the table
        // We might run out of unique numbers!
        // We might run too long!
        // Throw exceptions in these cases
        $startTime = time();
        for ($i = 0; $i <= self::PATIENT_ID_MAX; $i++) {
            // Gen tmp id
            $tmp = $this->generate_patient_id($date);

            // Check id for uniqueness
            // $tmp is a controlled value, no security issue here
            $patientQuery = $this->db->query('SELECT * FROM `ea_users` WHERE `patient_id` = ? LIMIT 1', [$tmp]);
            if ($patientQuery->num_rows() === 0) {
                // Break out of loop, when we have a legit unique id
                $id = $tmp;
                break;
            }

            // Check if we surpassed the execution time
            if ($startTime + self::GENERATE_PATIENT_MAX_EXECUTION < time()) {
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
     * Return caller type based on business_code
     *
     * @param string $business_code
     * @return string
     */
    public function getCallByBusinessCode(string $business_code) : string
    {
        return empty($business_code) ? CALLER_TYPE_PROVIDER : CALLER_TYPE_CIE;
    }

    /**
     * Helper function to gather initials.
     * Example: John Doe
     * Result: DJ
     *
     * @param string $firstname
     * @param string $lastname
     * @return string
     */
    public function parseNameInitials(string $firstname, string $lastname): string
    {
        $firstname = substr(preg_replace('/[^a-z]/i', '', $firstname), 0, 1);
        $lastname = substr(preg_replace('/[^a-z]/i', '', $lastname), 0, 1);
        return $lastname . $firstname;
    }
}
