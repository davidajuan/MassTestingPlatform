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

/**
 * Services Model
 *
 * @package Models
 */
class Service_capacity_Model extends CI_Model {
    /**
     * Add (insert or update) a service record on the database
     *
     * @param array $service Contains the service data. If an 'id' value is provided then the record will be updated.
     *
     * @return int Returns the record id.
     */
    public function add($service_capacity)
    {
        // Sanitize Data
        $service_capacity = $this->security->xss_clean($service_capacity);

        // clean non db fields
        unset($service_capacity['date']);
        unset($service_capacity['end_time']);
        unset($service_capacity['start_time']);

        $this->validate($service_capacity);

        if ( ! isset($service_capacity['id']))
        {
            $service_capacity['id'] = $this->_insert($service_capacity);
        }
        else
        {
            $this->_update($service_capacity);
        }

        return (int)$service_capacity['id'];
    }

    /**
     * Insert service capacity record into database.
     *
     * @param array $service Contains the service record data.
     *
     * @return int Returns the new service record id.
     *
     * @throws Exception If service record could not be inserted.
     */
    protected function _insert($service_capacity)
    {
        if ( ! $this->db->insert('ea_service_capacity', $service_capacity))
        {
            throw new Exception('Could not insert service capacity record.');
        }
        return (int)$this->db->insert_id();
    }

    /**
     * Update service record.
     *
     * @param array $service Contains the service data. The record id needs to be included in the array.
     *
     * @throws Exception If service record could not be updated.
     */
    protected function _update($service_capacity)
    {
        $this->db->where('id', $service_capacity['id']);
        if ( ! $this->db->update('ea_service_capacity', $service_capacity))
        {
            throw new Exception('Could not update service record');
        }
    }

    /**
     * Checks whether an service record already exists in the database.
     *
     * @param array $service Contains the service data. Name, duration and price values are mandatory in order to
     * perform the checks.
     *
     * @return bool Returns whether the service record exists.
     *
     * @throws Exception If required fields are missing.
     */
    public function exists($service_capacity)
    {
        if ( ! isset($service_capacity['start_datetime'])
            || ! isset($service_capacity['end_datetime']))
        {
            throw new Exception('Not all service fields are provided in order to check whether '
                . 'a service record already exists: ' . print_r($service_capacity, TRUE));
        }

        $num_rows = $this->db->get_where('ea_service_capacity', [
            'start_datetime' => $service_capacity['start_datetime'],
            'end_datetime' => $service_capacity['end_datetime']
        ])->num_rows();

        return ($num_rows > 0) ? TRUE : FALSE;
    }

    /**
     * Validate a service record data.
     *
     * @param array $service Contains the service data.
     *
     * @return bool Returns the validation result.
     *
     * @throws Exception If service validation fails.
     */
    public function validate($service_capacity)
    {
        $this->load->helper('data_validation');

        // If record id is provided we need to check whether the record exists
        // in the database.
        if (isset($service_capacity['id']))
        {
            $num_rows = $this->db->get_where('ea_service_capacity', ['id' => $service_capacity['id']])
                ->num_rows();
            if ($num_rows == 0)
            {
                throw new Exception('Provided service capacity id does not exist in the database.');
            }
        }

        // Check if Capacity dates are valid.
        if ( !validate_mysql_datetime($service_capacity['start_datetime']))
        {
            throw new Exception('Capacity start datetime is invalid.');
        }

        if ( !validate_mysql_datetime($service_capacity['end_datetime']))
        {
            throw new Exception('Capacity end datetime is invalid.');
        }
        if ( !is_numeric($service_capacity['attendants_number']))
        {
            throw new Exception('Attendants number needs to be a number.');
        }

        return TRUE;
    }

    /**
     * Delete a service record from database.
     *
     * @param int $service_id Record id to be deleted.
     *
     * @return bool Returns the delete operation result.
     *
     * @throws Exception If $service_id argument is invalid.
     */
    public function delete($id)
    {
        if ( ! is_numeric($id))
        {
            throw new Exception('Invalid value in id');
        }

        $num_rows = $this->db->get_where('ea_service_capacity', ['id' => $id])->num_rows();
        if ($num_rows == 0)
        {
            return FALSE; // Record does not exist
        }

        return $this->db->delete('ea_service_capacity', ['id' => $id]);
    }

    /**
     * Get a specific row from the services db table.
     *
     * @param int $service_id The record's id to be returned.
     *
     * @return array Returns an associative array with the selected record's data. Each key has the same name as the
     * database field names.
     *
     * @throws Exception If $service_id argument is not valid.
     */
    public function get_row($id)
    {
        if ( ! is_numeric($id))
        {
            throw new Exception('Invalid value in id');
        }
        return $this->db->get_where('ea_service_capacity', ['id' => $id])->row_array();
    }


    /**
     * Get all, or specific records from service's table.
     *
     * @example $this->Model->getBatch('id = ' . $recordId);
     *
     * @param string $whereClause (OPTIONAL) The WHERE clause of
     * the query to be executed. DO NOT INCLUDE 'WHERE' KEYWORD.
     *
     * @return array Returns the rows from the database.
     */
    public function get_batch($where_clause = NULL)
    {
        if ($where_clause != NULL)
        {
            $this->db->where($where_clause);
        }

        return $this->db->get('ea_service_capacity')->result_array();
    }

    /**
     * This method returns all the services from the database.
     *
     * @return array Returns an object array with all the database services.
     */
    public function get_services_capacity($id_services)
    {
        $this->db->distinct();
        return $this->db
            ->select('*')
            ->from('ea_service_capacity')
            ->where(['id_services' => $id_services])
            ->get()->row_array();
    }
}
