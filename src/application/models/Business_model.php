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

use Respect\Validation\Validator as v;
use Respect\Validation\Exceptions\NestedValidationException;

/**
 * Business Model
 *
 * @package Models
 */
class Business_Model extends CI_Model
{

    // Table fields
    const FIELD_BUSINESS_NAME = 'business_name';
    const FIELD_ADDRESS = 'address';
    const FIELD_ZIP_CODE = 'zip_code';

    const BUSINESS_ID_MAX = 999999;
    const GENERATE_BUSINESS_MAX_EXECUTION = 10; // seconds

    /**
     * Add a business.
     *
     * @param array $business Associative array with the business data.
     *
     * @return int Returns the business id.
     */
    public function add($business)
    {
        // Sanitize Data
        $business = $this->security->xss_clean($business);

        // Validate the business data before doing anything.
        $this->validate($business);

        // :: CHECK IF BUSINESS ALREADY EXIST
        if ($this->exists($business) && !isset($business['id'])) {
            // Find the business id from the database.
            $business['id'] = $this->find_record_id($business);
        }

        // Perform insert() or update() operation.
        if (!isset($business['id'])) {
            $business['id'] = $this->_insert($business);
        } else {
            $this->_update($business);
        }

        return $business['id'];
    }

    /**
     * Check if a particular business record already exists.
     *
     * @param array $business Associative array with the business's data.
     *
     * @return bool Returns whether the record exists or not.
     *
     * @throws Exception If business fields are missing.
     */
    public function exists($business)
    {
        if (
            !isset($business[self::FIELD_BUSINESS_NAME])
            || !isset($business[self::FIELD_ADDRESS])
            || !isset($business[self::FIELD_ZIP_CODE])
        ) {
            throw new Exception('Not all business field values are provided: '
                . print_r($business, TRUE));
        }

        $num_rows = $this->db->get_where('ea_business', [
            self::FIELD_BUSINESS_NAME => $business[self::FIELD_BUSINESS_NAME],
            self::FIELD_ADDRESS => $business[self::FIELD_ADDRESS],
            self::FIELD_ZIP_CODE => $business[self::FIELD_ZIP_CODE],
        ])->num_rows();

        return ($num_rows > 0) ? TRUE : FALSE;
    }

    /**
     * Find the database id of a business record.
     *
     * The business data should include the following fields in order to get the unique id from the database:
     *  FIELD_BUSINESS_NAME
     *  FIELD_ADDRESS
     *  FIELD_ZIP_CODE
     *
     * IMPORTANT: The record must already exists in the database, otherwise an exception is raised.
     *
     * @param array $business Array with the customer data.
     *
     * @return int Returns the ID.
     *
     * @throws Exception If business record does not exist.
     */
    public function find_record_id($business)
    {
        if (
            !isset($business[self::FIELD_BUSINESS_NAME])
            || !isset($business[self::FIELD_ADDRESS])
            || !isset($business[self::FIELD_ZIP_CODE])
        ) {
            throw new Exception(sprintf('%s, %s, %s was not provided.', self::FIELD_BUSINESS_NAME, self::FIELD_ADDRESS, self::FIELD_ADDRESS));
        }

        $result = $this->db
            ->select('id')
            ->from('ea_business')
            ->where(self::FIELD_BUSINESS_NAME, $business[self::FIELD_BUSINESS_NAME])
            ->where(self::FIELD_ADDRESS, $business[self::FIELD_ADDRESS])
            ->where(self::FIELD_ZIP_CODE, $business[self::FIELD_ZIP_CODE])
            ->get();

        if ($result->num_rows() == 0) {
            throw new Exception('Could not find business record id.');
        }

        return $result->row()->id;
    }

    /**
     * Insert a new business record to the database.
     *
     * @param array $business Associative array with the business's data.
     *
     * @return int Returns the id of the new record.
     *
     * @throws Exception If business record could not be inserted.
     */
    protected function _insert($business)
    {
        $business['modified'] = date('Y-m-d H:i:s');
        $business['created'] = date('Y-m-d H:i:s');
        $business['hash'] = $this->generate_unique_business_hash($business['business_name']);
        if (!$this->db->insert('ea_business', $business)) {
            throw new Exception('Could not insert business record.');
        }

        return (int) $this->db->insert_id();
    }

    /**
     * This method generates a random business hash based on given business name
     * NOTE: This does not check against existing / uniqueness
     * Use generate_unique_business_hash() instead
     *
     * @param string $business_name
     * @return string Returns the randomly generated business hash in format "123456BU"
     */
    protected function generate_business_hash($business_name)
    {
        // Gen a random number
        $num = random_int(0, self::BUSINESS_ID_MAX);
        // Pad left to make 6 digits
        $num = str_pad($num, 6, "0", STR_PAD_LEFT);

        // get business name ready capture first two AlphaNumber
        // Eg, K-Mart should be KM
        $business_name = preg_replace( '/[^A-Za-z0-9]/i', '', $business_name);
        return $num . strtoupper(substr($business_name, 0, 2));
    }

    /**
     * This method generates a unique and random business hash
     *
     * @param string $business_name
     * @return string Returns the randomly generated business hash in format "123456BU"
     */
    public function generate_unique_business_hash($business_name)
    {
        $hash = null;

        $startTime = time();
        for ($i = 0; $i <= self::BUSINESS_ID_MAX; $i++) {
            // Gen tmp hash
            $tmp = $this->generate_business_hash($business_name);

            // Check hash for uniqueness
            // $tmp is a controlled value, no security issue here
            $patientQuery = $this->db->query('SELECT * FROM `ea_business` WHERE `hash` = ? LIMIT 1', [$tmp]);
            if ($patientQuery->num_rows() === 0) {
                // Break out of loop, when we have a legit unique hash
                $hash = $tmp;
                break;
            }

            // Check if we surpassed the execution time
            if ($startTime + self::GENERATE_BUSINESS_MAX_EXECUTION < time()) {
                throw new Exception('Generate id execution exceeded');
            }
        }

        // hash is still null, we failed in getting an hash
        if ($hash === null) {
            throw new Exception('Ran out of unique hashs');
        }

        return $hash;
    }

    /**
     * Update an existing business record in the database.
     *
     * The business data argument should already include the record ID in order to process the update operation.
     *
     * @param array $business Associative array with the business's data.
     *
     * @throws Exception If business record could not be updated.
     */
    protected function _update($business)
    {
        $business['modified'] = date('Y-m-d H:i:s');
        $this->db->where('id', $business['id']);
        if (!$this->db->update('ea_business', $business)) {
            throw new Exception('Could not update business record.');
        }
    }

    /**
     * Validate business data before the insert or update operations are executed.
     *
     * @param array $business Contains the business data.
     *
     * @return bool Returns the validation result.
     *
     * @throws Exception If business validation fails.
     */
    public function validate($business)
    {
        $this->load->helper('data_validation');

        // Trim all whitespace
        $business = trim_whitespace($business);

        // If a business id is given, check whether the record exists
        // in the database.
        if (isset($business['id'])) {
            $num_rows = $this->db->get_where(
                'ea_business',
                ['id' => $business['id']]
            )->num_rows();
            if ($num_rows == 0) {
                throw new Exception('Provided business id does not exist in the database.');
            }
        }
        $businessValidator = $this->getValidationModel();

        // Perform validation and get messages
        try {
            $businessValidator->assert($business);
        } catch (NestedValidationException $exception) {
            // Validation failed, let's throw an error
            $errorMessage = 'Unknown Error';

            // Parse for the first error
            $errors = $exception->getFullMessage();
            $errorMessage = $errors;

            throw new Exception($errorMessage);
        }

        return TRUE;
    }

    public function getValidationModel()
    {
        $checkboxValidator = v::stringType()->oneOf(
            v::equals('0'),
            v::equals('1')
        );

        $businessValidator =
            v::key('address', v::stringType()->notEmpty())
            ->key('city', v::stringType()->notEmpty())
            ->key('state', v::subdivisionCode('US'))
            ->key('zip_code', v::postalCode('US'))
            ->key('email', v::optional(v::email()))
            ->key('owner_first_name', v::stringType()->notEmpty())
            ->key('owner_last_name', v::stringType()->notEmpty())
            ->key('business_phone', v::phone())
            ->key('mobile_phone', v::optional(v::phone()))
            ->key('consent_sms', $checkboxValidator)
            ->key('consent_email', $checkboxValidator);

        return $businessValidator;
    }


    /**
     * Delete an existing business record from the database.
     *
     * @param int $business_id The record id to be deleted.
     *
     * @return bool Returns the delete operation result.
     *
     * @throws Exception If $business_id argument is invalid.
     */
    public function delete($business_id)
    {
        if (!is_numeric($business_id)) {
            throw new Exception('Invalid value in business_id');
        }

        $num_rows = $this->db->get_where('ea_business', ['id' => $business_id])->num_rows();

        if ($num_rows == 0) {
            return FALSE; // Record does not exist.
        }

        $this->db->where('id', $business_id);
        return $this->db->delete('ea_business');
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
        $this->db->from('ea_business');
        return $this->db->count_all_results();
    }

    /**
     * Return active capacity counts for all businesses
     *  - business_name
     *  - AppointmentsRequested
     *  - AppointmentsApproved
     *  - AppointmentsCreated
     *
     * *Business Codes referring to same business are aggregated together
     *
     * @return array
     */
    public function get_capacity_counts(): array
    {
        $sql = "SELECT b.business_name,
                SUM(br.slots_requested) AS AppointmentsRequested,
                SUM(br.slots_approved) AS AppointmentsApproved,
                SUM((SELECT COUNT(id) FROM ea_appointments a WHERE a.business_code = br.business_code)) AS AppointmentsCreated
            FROM easyapp.ea_business b LEFT JOIN easyapp.ea_business_request br ON br.id_business = b.id
            WHERE `status` = ?
            GROUP BY b.business_name
            ORDER BY b.business_name";
        $query = $this->db->query($sql, [DB_SLUG_BUSINESS_REQ_ACTIVE]);

        return $query->result_array();
    }

    /**
     * Get a specific row from the business table.
     *
     * @param int $business_id The record's id to be returned.
     *
     * @return array Returns an associative array with the selected record's data. Each key has the same name as the
     * database field names.
     *
     * @throws Exception If $business_id argumnet is invalid.
     */
    public function get_row($business_id)
    {
        if (!is_numeric($business_id)) {
            throw new Exception('Invalid value in business_id');
        }

        return $this->db->get_where('ea_business', ['id' => $business_id])->row_array();
    }

    /**
     * Get a specific row from the business doctor table.
     *
     * @param int $business_id The record's id to be returned.
     *
     * @return array Returns an associative array doctor info.
     *
     * @throws Exception If $business_id argumnet is invalid.
     */
    public function getBusinessDoctorInfo(int $business_id) : array
    {
        return $this->db->get_where('ea_business_doctor', ['id_business' => $business_id])->row_array() ?? [];
    }

    /**
     * Get all, or specific records from business's table.
     * https://codeigniter.com/userguide3/database/query_builder.html#looking-for-specific-data
     *
     * @param string|array $where_clause
     * @return array Returns the rows from the database.
     */
    public function get_batch($where_clause = null, $search = []): array
    {
        if ($where_clause) {
            $this->db->where($where_clause);
        }

        if (!empty($search)) {
            $this->db->group_start()
                ->or_like($search)
                ->group_end();
        }

        $business = $this->db->get('ea_business')->result_array();
        return $business;
    }
}
