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
class Business_request_Model extends CI_Model
{

    // Table fields
    const FIELD_SLOTS_REQUESTED = 'slots_requested';

    const BUSINESS_ID_MAX = 999999;
    const GENERATE_BUSINESS_MAX_EXECUTION = 10; // seconds

    // check business code messages
    const CHECK_CODE_INVALID = '* Business code not valid (generic)';
    const CHECK_CODE_NOT_EXISTS = '* This code does not exist';
    const CHECK_CODE_VALID = '* Business code is valid';
    const CHECK_CODE_USED_SLOTS = '* This code has no remaining appointments available';

    /**
     * Add a business request.
     *
     * @param array $business_request Associative array with the business data.
     * @param string $business_name Used to generate
     *
     * @return int Returns the business request id.
     */
    public function add($business_request, $business_name)
    {
        // Sanitize Data
        $business_request = $this->security->xss_clean($business_request);
        $business_name = $this->security->xss_clean($business_name);

        // Validate the business data before doing anything.
        $this->validate($business_request);

        // Perform insert() or update() operation.
        if (!isset($business_request['id'])) {
            $business_request['id'] = $this->_insert($business_request, $business_name);
        } else {
            $this->_update($business_request);
        }

        return $business_request['id'];
    }

    /**
     * Insert a new business record to the database.
     *
     * @param array $business_request Associative array with the business's data.
     *
     * @return int Returns the id of the new record.
     *
     * @throws Exception If business record could not be inserted.
     */
    protected function _insert($business_request, $business_name)
    {
        $business_request['modified'] = date('Y-m-d H:i:s');
        $business_request['created'] = date('Y-m-d H:i:s');
        $business_request['business_code'] = $this->generate_unique_business_code($business_name);

        if (!$this->db->insert('ea_business_request', $business_request)) {
            throw new Exception('Could not insert business record.');
        }

        return (int) $this->db->insert_id();
    }

    /**
     * This method generates a random business hash based on given business name
     * NOTE: This does not check against existing / uniqueness
     * Use generate_unique_business_code() instead
     *
     * @param string $business_request_name
     * @return string Returns the randomly generated business hash in format "123456BUS"
     */
    protected function generate_business_code($business_request_name)
    {
        // Gen a random number
        $num = random_int(0, self::BUSINESS_ID_MAX);
        // Pad left to make 6 digits
        $num = str_pad($num, 6, "0", STR_PAD_LEFT);

        // get business name ready capture first two AlphaNumber
        // Eg, K-Mart should be KM
        $business_request_name = preg_replace( '/[^A-Za-z0-9]/i', '', $business_request_name);

        return $num . strtoupper(substr($business_request_name, 0, 2));
    }

    /**
     * This method generates a unique and random business hash
     *
     * TODO: we use this generate pattern a lot.. we should probably pull this into a reuseable function?
     *
     *
     * @param string $business_request_name
     * @return string Returns the randomly generated business hash in format "123456BUS"
     */
    public function generate_unique_business_code($business_request_name)
    {
        $hash = null;

        $startTime = time();
        for ($i = 0; $i <= self::BUSINESS_ID_MAX; $i++) {
            // Gen tmp hash
            $tmp = $this->generate_business_code($business_request_name);

            // Check hash for uniqueness
            // $tmp is a controlled value, no security issue here
            $patientQuery = $this->db->query('SELECT * FROM `ea_business_request` WHERE `business_code` = ? LIMIT 1', [$tmp]);
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
     * @param array $business_request Associative array with the business's data.
     *
     * @throws Exception If business record could not be updated.
     */
    protected function _update($business_request)
    {
        $business_request['modified'] = date('Y-m-d H:i:s');
        $this->db->where('id', $business_request['id']);
        if (!$this->db->update('ea_business_request', $business_request)) {
            throw new Exception('Could not update business record.');
        }
    }

    /**
     * Validate business data before the insert or update operations are executed.
     *
     * @param array $business_request Contains the business data.
     *
     * @return bool Returns the validation result.
     *
     * @throws Exception If business validation fails.
     */
    public function validate($business_request)
    {
        $this->load->helper('data_validation');

        // Trim all whitespace
        $business_request = trim_whitespace($business_request);

        // If a  is given, check whether the record exists
        // in the database.
        if (isset($business_request['id'])) {
            $num_rows = $this->db->get_where(
                'ea_business_request',
                ['id' => $business_request['id']]
            )->num_rows();
            if ($num_rows == 0) {
                throw new Exception('Provided id does not exist in the database.');
            }
        }

        $businessRequestValidator = $this->getValidationModel();

        // Perform validation and get messages
        try {
            $businessRequestValidator->assert($business_request);
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
        $businessRequestValidator =
            v::key('id_business', v::intVal()->notEmpty())      // int > 0
            ->key('slots_requested', v::intVal()->notEmpty());  // int > 0
        return $businessRequestValidator;
    }

    public function checkBusinessCode($business_code)
    {
        $ret = [
            'valid' => false,
            'message' => self::CHECK_CODE_INVALID,
            'priority_service' => false,
        ];

        //// To get total active slots_approved
        // Take business_code and get business hash
        $business = $this->getBusinessFromBusinessCode($business_code);

        // Use business hash to get all business_codes for that business
        $bus_requests = $this->getBusinessRequestsFromBusinessHash($business['hash'] ??  null, 'active');

        $business_codes = [];
        foreach ($bus_requests as $bus_request) {
            $business_codes[] = $bus_request['business_code'];
            // if any of the requests are priority, make it a priority request
            if ((int)$bus_request['priority_service'] === 1) {
                $ret['priority_service'] = true;
            }
        }

        $requests = [];

        if (!empty($business_codes)) {
            // query business_request with all business_codes with SUM(slots_approved)
            $total_active_slots = $this->getTotalActiveSlots($business_codes);

            //// to get total used slots
            // query ea_appoinments with all business_codes with COUNT(business_code)
            $total_used_slots = $this->getTotalSlotsUsed($business_codes);

            // Calculate the number of active slots
            $requests = $this->getRequestByCode($business_codes);
        }

        if (empty($requests)) {
            $ret['valid'] = false;
            $ret['message'] = self::CHECK_CODE_NOT_EXISTS;
        } else if ( $total_used_slots < $total_active_slots) {
            $ret['valid'] = true;
            $ret['message'] = self::CHECK_CODE_VALID;
        } else if ( $total_used_slots >= $total_active_slots) {
            $ret['valid'] = false;
            $ret['message'] = self::CHECK_CODE_USED_SLOTS;
        } else {
            $ret['valid'] = false;
        }

        return $ret;
    }

    /*
    * Given business codes, get the business request
    *
    * @param array $business_code Business code of request.
    *
    * @return array Returns all requests given a list of codes
    */
    protected function getRequestByCode($business_codes, $status = '')
    {
        $select = $this->db
            ->select('*')
            ->from('ea_business_request')
            ->where_in('ea_business_request.business_code', $business_codes);

        if ($status) {
            $select->where('status', $status);
        }

        return $select->get()
            ->result_array();
    }

    /*
    * Given an array of business codes, get the sum of their slots_approved
    *
    * @param array $business_codes.
    *
    * @return int Returns the number of approved slots
    */
    protected function getTotalActiveSlots($business_codes)
    {
        $return = $this->db
            ->select('SUM(slots_approved) as total_slots_approved')
            ->from('ea_business_request')
            ->where('status', 'active') // TODO: update with slug of Tony
            ->where_in('ea_business_request.business_code', $business_codes)
            ->get()
            ->row_array();

        return  $return['total_slots_approved'] ?? 0;
    }

    /*
    * Given business codes, get the slots used for the code
    *
    * @param array $business_code Business code of request.
    *
    * @return int Returns the number of used slots
    */
    protected function getTotalSlotsUsed($business_codes)
    {
        $return = $this->db
            ->select('COUNT(business_code) as total_slots_used')
            ->from('ea_appointments')
            ->where_in('ea_appointments.business_code', $business_codes)
            ->get()
            ->row_array();

        return $return['total_slots_used'] ?? 0;
    }

    /*
    * Given a business code, get the business info
    *
    * @param string $business_code Business code of request.
    *
    * @return array Returns the business associated to the business code.
    */
    public function getBusinessFromBusinessCode($business_code)
    {
        return $this->db
            ->select('bus.*, bus_req.*')
            ->from('ea_business as bus')
            ->where('bus_req.business_code', $business_code)
            ->join('ea_business_request as bus_req', 'bus.id = bus_req.id_business', 'left')
            ->limit(1)
            ->get()
            ->row_array();
    }

    /**
     * Get all slot counts for all businesses
     * Struct:
     *      business_id => [
     *          slots_requested,
     *          slots_approved,
     *          slots_remaining,
     *          slots_occupied,
     *          codes_approved,
     *          codes_pending,
     *          codes_denied,
     *      ]
     *
     * @param mixed $where_clause
     * @return array multi-dim array of slot counts for each business
     */
    public function get_slot_counts($where_clause = null): array
    {
        $ret = [];

        // requested slots
        // SELECT id_business, SUM(slots_requested) as requested FROM ea_business_request GROUP BY id_business;
        $this->db->select('id_business');
        $this->db->select_sum('slots_requested', 'requested');
        $this->db->group_by(['id_business']);
        if ($where_clause) {
            $this->db->where($where_clause);
        }
        $query = $this->db->get('ea_business_request');
        // Compile results
        foreach ($query->result() as $row) {
            $ret[$row->id_business] = [
                'slots_requested'   => intval($row->requested),
                'slots_approved'    => 0,
                'slots_remaining'   => 0,
                'slots_occupied'    => 0,
                'codes_approved'    => 0,
                'codes_pending'     => 0,
                'codes_denied'      => 0,
            ];
        }

        // actually approved slots
        // SELECT id_business, SUM(slots_approved) as approved FROM ea_business_request WHERE status = 'active' GROUP BY id_business;
        $this->db->select('id_business');
        $this->db->select_sum('slots_approved', 'approved');
        $this->db->where(['status' => DB_SLUG_BUSINESS_REQ_ACTIVE]);
        $this->db->group_by(['id_business']);
        if ($where_clause) {
            $this->db->where($where_clause);
        }
        $query = $this->db->get('ea_business_request');
        // Compile results
        foreach ($query->result() as $row) {
            $ret[$row->id_business]['slots_approved'] = intval($row->approved);
        }

        // codes approved, pending, denied
        // SELECT id_business, COUNT(`status`) as count, `status` FROM ea_business_request GROUP BY id_business, `status`;
        $this->db->select('id_business, COUNT(status) as count, status');
        $this->db->group_by(['id_business', 'status']);
        if ($where_clause) {
            $this->db->where($where_clause);
        }
        $query = $this->db->get('ea_business_request');
        // Compile results
        foreach ($query->result() as $row) {
            if ($row->status === DB_SLUG_BUSINESS_REQ_ACTIVE) {
                $ret[$row->id_business]['codes_approved'] = intval($row->count);
            }
            elseif ($row->status === DB_SLUG_BUSINESS_REQ_PENDING) {
                $ret[$row->id_business]['codes_pending'] = intval($row->count);
            }
            elseif ($row->status === DB_SLUG_BUSINESS_REQ_DELETED) {
                $ret[$row->id_business]['codes_denied'] = intval($row->count);
            }
        }

        // occupied slots
        // SELECT ea_business_request.id_business, COUNT(ea_business_request.business_code) as count
        //     FROM ea_appointments
        //     INNER JOIN ea_business_request
        //     ON ea_appointments.business_code = ea_business_request.business_code
        //     GROUP BY ea_business_request.business_code;
        $this->db->select('ea_business_request.id_business, COUNT(ea_business_request.business_code) as count');
        $this->db->join('ea_business_request', 'ea_appointments.business_code = ea_business_request.business_code');
        $this->db->group_by(['ea_business_request.business_code']);
        if ($where_clause) {
            $this->db->where($where_clause);
        }
        $query = $this->db->get('ea_appointments');

        foreach ($query->result() as $row) {
            // how many appointments are taken
            $ret[$row->id_business]['slots_occupied'] = intval($row->count);
            // how many appointments are left, never go below 0
            $ret[$row->id_business]['slots_remaining'] = max((intval($ret[$row->id_business]['slots_approved']) - intval($row->count)), 0);
        }

        return $ret;
    }

    /*
    * Given a business hash, get the business req
    *
    * @param string $hash Business hash.
    *
    * @return array Returns the business associated to the business hash.
    */
    protected function getBusinessRequestsFromBusinessHash($hash, $status = '')
    {
        $select = $this->db
            ->select('bus.*, bus_req.*')
            ->from('ea_business as bus')
            ->where('bus.hash', $hash);

        if ($status) {
            $select->where('status', $status);
        }

        return $select->join('ea_business_request as bus_req', 'bus.id = bus_req.id_business', 'left')
            ->get()
            ->result_array();
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
        $this->db->from('ea_business_request');
        return $this->db->count_all_results();
    }

    /**
     * Get a SUM of a specific field
     *
     * @param mixed where criteria
     * @return int count of business requests
     */
    public function sumByField(string $fieldName, $where_clause = null): int {
        $this->db->select_sum($fieldName, 'countResult');
        if ($where_clause) {
            $this->db->where($where_clause);
        }
        $query = $this->db->get('ea_business_request');

        return $query->row()->countResult ? $query->row()->countResult : 0;
    }

    /**
     * Delete an existing business record from the database.
     *
     * @param int $business_request_id The record id to be deleted.
     *
     * @return bool Returns the delete operation result.
     *
     * @throws Exception If $business_request_id argument is invalid.
     */
    public function delete($business_request_id)
    {
        if (!is_numeric($business_request_id)) {
            throw new Exception('Invalid value in business_request_id');
        }

        $num_rows = $this->db->get_where('ea_business_request', ['id' => $business_request_id])->num_rows();

        if ($num_rows == 0) {
            return FALSE; // Record does not exist.
        }

        $this->db->where('id', $business_request_id);
        return $this->db->delete('ea_business_request');
    }

    /**
     * Get a specific row from the business table.
     *
     * @param int $business_request_id The record's id to be returned.
     *
     * @return array Returns an associative array with the selected record's data. Each key has the same name as the
     * database field names.
     *
     * @throws Exception If $business_request_id argumnet is invalid.
     */
    public function get_row($business_request_id)
    {
        if (!is_numeric($business_request_id)) {
            throw new Exception('Invalid value in business_request_id');
        }

        return $this->db
            ->select('*')
            ->from('ea_business_request')
            ->where('ea_business_request.id', $business_request_id)
            ->join('ea_business', 'ea_business.id = ea_business_request.id_business', 'left')
            ->get()
            ->row_array();
    }

    /**
     * Get a specific row from the business table.
     *
     * @param int $business_request_id The record's id to be returned.
     *
     * @return array Returns an associative array with the selected record's data. Each key has the same name as the
     * database field names.
     *
     * @throws Exception If $business_request_id argumnet is invalid.
     */
    public function get_row_by_code($business_code)
    {
        if (empty($business_code)) {
            throw new Exception('business_code is required');
        }

        return $this->db->get_where('ea_business_request', ['business_code' => $business_code])->row_array();
    }

    /**
     * Get all records (and joined parent business table) in a date range
     *
     * @param DateTime $dateStart starting timestamp (inclusive)
     * @param DateTime $dateEnd ending timestamp (inclusive)
     * @param string $status filter out any results by status
     * @return array Returns an associative array with the selected record's data.
     */
    public function get_rows_createdate_ranged(DateTime $dateStart, DateTime $dateEnd, string $status = null)
    {
        // Be careful here join is overwriting IDs and Create DateTimes
        $query = $this->db
            ->select('bus.*, bus_req.*')
            ->from('ea_business_request AS bus_req')
            ->where('bus_req.created >=', $dateStart->format('Y-m-d H:i:s'))
            ->where('bus_req.created <=', $dateEnd->format('Y-m-d H:i:s'))
            ->join('ea_business AS bus', 'bus.id = bus_req.id_business', 'left');

        if ($status) {
            $query->where('bus_req.status', $status);
        }

        return $query->get()->result_array();
    }

    /**
     * Get all, or specific records from business's table.
     * https://codeigniter.com/userguide3/database/query_builder.html#looking-for-specific-data
     *
     * @param string|array $where_clause
     * @return array Returns the rows from the database.
     */
    public function get_batch($where_clause = null): array
    {
        if ($where_clause) {
            $this->db->where($where_clause);
        }

        $business_request = $this->db->get('ea_business_request')->result_array();
        return $business_request;
    }

    /**
     * Get all requests with joined business
     *
     * @param string|array $where_clause
     *
     * @return array Returns the rows from the database.
     */
    public function get_batch_business($where_clause = null)
    {
        $select = $this->db
            ->select('bus.*, bus_req.*, bus_req.created as request_created')
            ->from('ea_business_request bus_req')
            ->join('ea_business bus', 'bus.id = bus_req.id_business', 'left');

        if ($where_clause) {
            $select = $select->where($where_clause);
        }

        return $select->get()->result_array();
    }

    /**
     * Search for a business
     *
     * @param $query
     * @param bool $status
     *
     * @return mixed
     */
    public function search_business($query, $status = false)
    {
        $query = strtoupper($query);

        if ($status) {
            $this->db->where('status', $status);
        }

        return $this->db
            ->group_start()
                ->or_like('business_name', $query)
                ->or_like('owner_first_name', $query)
                ->or_like('owner_last_name', $query)
                ->or_like('business_phone', $query)
                ->or_like('mobile_phone', $query)
                ->or_like('email', $query)
                ->or_like('address', $query)
                ->or_like('hash', $query)
                ->or_like('business_code', $query)
            ->group_end()
            ->select('bus.*, bus_req.*, bus_req.created as request_created')
            ->from('ea_business_request bus_req')
            ->join('ea_business bus', 'bus.id = bus_req.id_business', 'left')
            ->get()
            ->result_array();
    }
}
