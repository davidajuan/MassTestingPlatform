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
 * City Business Model
 *
 * Handles the db actions that have to do with city business.
 *
 * Data Structure:
 *
 *  'first_name'
 *  'last_name'
 *  'email'
 *  'mobile_number'
 *  'phone_number'
 *  'address'
 *  'city'
 *  'state'
 *  'zip_code'
 *  'notes'
 *  'id_roles'
 *  'providers' >> array with provider ids that the city business handles
 *  'settings' >> array with the city business settings
 *
 * @package Models
 */
class Citybusiness_Model extends CI_Model {
    /**
     * Add (insert or update) a city business user record into database.
     *
     * @param array $city_business Contains the city business user data.
     *
     * @return int Returns the record id.
     *
     * @throws Exception When the city business data are invalid (see validate() method).
     */
    public function add($city_business)
    {
        // Sanitize Data
        $city_business = $this->security->xss_clean($city_business);

        $this->validate($city_business);

        if ($this->exists($city_business) && ! isset($city_business['id']))
        {
            $city_business['id'] = $this->find_record_id($city_business);
        }

        if ( ! isset($city_business['id']))
        {
            $city_business['id'] = $this->_insert($city_business);
        }
        else
        {
            $city_business['id'] = $this->_update($city_business);
        }

        return (int)$city_business['id'];
    }

    /**
     * Check whether a particular city business record exists in the database.
     *
     * @param array $city_business Contains the city business data. The 'email' value is required to be present at the moment.
     *
     * @return bool Returns whether the record exists or not.
     *
     * @throws Exception When the 'email' value is not present on the $city_business argument.
     */
    public function exists($city_business)
    {
        if ( ! isset($city_business['email']))
        {
            throw new Exception('City business email is not provided: ' . print_r($city_business, TRUE));
        }

        // This method shouldn't depend on another method of this class.
        $num_rows = $this->db
            ->select('*')
            ->from('ea_users')
            ->join('ea_roles', 'ea_roles.id = ea_users.id_roles', 'inner')
            ->where('ea_users.email', $city_business['email'])
            ->where('ea_roles.slug', DB_SLUG_CITY_BUSINESS)
            ->get()->num_rows();

        return ($num_rows > 0) ? TRUE : FALSE;
    }

    /**
     * Insert a new city business record into the database.
     *
     * @param array $city_business Contains the city business data.
     *
     * @return int Returns the new record id.
     *
     * @throws Exception When the insert operation fails.
     */
    protected function _insert($city_business)
    {
        $this->load->helper('general');

        $providers = $city_business['providers'];
        unset($city_business['providers']);
        $settings = $city_business['settings'];
        unset($city_business['settings']);

        $city_business['id_roles'] = $this->get_city_business_role_id();

        if ( ! $this->db->insert('ea_users', $city_business))
        {
            throw new Exception('Could not insert city business into the database.');
        }

        $city_business['id'] = (int)$this->db->insert_id();
        $settings['salt'] = generate_salt();
        $settings['password'] = hash_password($settings['salt'], $settings['password']);

        $this->save_providers($providers, $city_business['id']);
        $this->save_settings($settings, $city_business['id']);

        return $city_business['id'];
    }

    /**
     * Update an existing city business record in the database.
     *
     * @param array $city_business Contains the city business record data.
     *
     * @return int Returns the record id.
     *
     * @throws Exception When the update operation fails.
     */
    protected function _update($city_business)
    {
        $this->load->helper('general');

        $providers = $city_business['providers'];
        unset($city_business['providers']);
        $settings = $city_business['settings'];
        unset($city_business['settings']);

        if (isset($settings['password']))
        {
            $salt = $this->db->get_where('ea_user_settings', ['id_users' => $city_business['id']])->row()->salt;
            $settings['password'] = hash_password($salt, $settings['password']);
        }

        $this->db->where('id', $city_business['id']);
        if ( ! $this->db->update('ea_users', $city_business))
        {
            throw new Exception('Could not update city business record.');
        }

        $this->save_providers($providers, $city_business['id']);
        $this->save_settings($settings, $city_business['id']);

        return (int)$city_business['id'];
    }

    /**
     * Find the database record id of a city business.
     *
     * @param array $city_business Contains the city business data. The 'email' value is required in order to find the record id.
     *
     * @return int Returns the record id
     *
     * @throws Exception When the 'email' value is not present on the $city_business array.
     */
    public function find_record_id($city_business)
    {
        if ( ! isset($city_business['email']))
        {
            throw new Exception('City business email was not provided: ' . print_r($city_business, TRUE));
        }

        $result = $this->db
            ->select('ea_users.id')
            ->from('ea_users')
            ->join('ea_roles', 'ea_roles.id = ea_users.id_roles', 'inner')
            ->where('ea_users.email', $city_business['email'])
            ->where('ea_roles.slug', DB_SLUG_CITY_BUSINESS)
            ->get();

        if ($result->num_rows() == 0)
        {
            throw new Exception('Could not find city business record id.');
        }

        return (int)$result->row()->id;
    }

    /**
     * Validate city business user data before add() operation is executed.
     *
     * @param array $city_business Contains the city business user data.
     *
     * @return bool Returns the validation result.
     *
     * @throws Exception If city business validation fails.
     */
    public function validate($city_business)
    {
        $this->load->helper('data_validation');

        // If a record id is provided then check whether the record exists in the database.
        if (isset($city_business['id']))
        {
            $num_rows = $this->db->get_where('ea_users', ['id' => $city_business['id']])
                ->num_rows();
            if ($num_rows == 0)
            {
                throw new Exception('Given city business id does not exist in database: ' . $city_business['id']);
            }
        }

        // Validate 'providers' value data type (must be array)
        if (isset($city_business['providers']) && ! is_array($city_business['providers']))
        {
            throw new Exception('City business providers value is not an array.');
        }

        // Validate required fields integrity.
        if ( ! isset($city_business['last_name'])
            || ! isset($city_business['email'])
            || ! isset($city_business['phone_number']))
        {
            throw new Exception('Not all required fields are provided: ' . print_r($city_business, TRUE));
        }

        // Validate city business email address.
        if ( ! filter_var($city_business['email'], FILTER_VALIDATE_EMAIL))
        {
            throw new Exception('Invalid email address provided: ' . $city_business['email']);
        }

        // Check if username exists.
        if (isset($city_business['settings']['username']))
        {
            $user_id = (isset($city_business['id'])) ? $city_business['id'] : '';
            if ( ! $this->validate_username($city_business['settings']['username'], $user_id))
            {
                throw new Exception ('Username already exists. Please select a different '
                    . 'username for this record.');
            }
        }

        // Validate city business password.
        if (isset($city_business['settings']['password']))
        {
            if (strlen($city_business['settings']['password']) < MIN_PASSWORD_LENGTH)
            {
                throw new Exception('The user password must be at least '
                    . MIN_PASSWORD_LENGTH . ' characters long.');
            }
        }

        // Validate calendar view mode.
        if (isset($city_business['settings']['calendar_view']) && ($city_business['settings']['calendar_view'] !== CALENDAR_VIEW_DEFAULT
                && $city_business['settings']['calendar_view'] !== CALENDAR_VIEW_TABLE))
        {
            throw new Exception('The calendar view setting must be either "' . CALENDAR_VIEW_DEFAULT
                . '" or "' . CALENDAR_VIEW_TABLE . '", given: ' . $city_business['settings']['calendar_view']);
        }

        // When inserting a record the email address must be unique.
        $city_business_id = (isset($city_business['id'])) ? $city_business['id'] : '';

        $num_rows = $this->db
            ->select('*')
            ->from('ea_users')
            ->join('ea_roles', 'ea_roles.id = ea_users.id_roles', 'inner')
            ->where('ea_roles.slug', DB_SLUG_CITY_BUSINESS)
            ->where('ea_users.email', $city_business['email'])
            ->where('ea_users.id <>', $city_business_id)
            ->get()
            ->num_rows();

        if ($num_rows > 0)
        {
            throw new Exception('Given email address belongs to another city business record. '
                . 'Please use a different email.');
        }

        return TRUE;
    }

    /**
     * Delete an existing city business record from the database.
     *
     * @param int $city_business_id The city business record id to be deleted.
     *
     * @return bool Returns the delete operation result.
     *
     * @throws Exception When the $city_business_id is not a valid int value.
     */
    public function delete($city_business_id)
    {
        if ( ! is_numeric($city_business_id))
        {
            throw new Exception('Invalid argument type $city_business_id: ' . $city_business_id);
        }

        $num_rows = $this->db->get_where('ea_users', ['id' => $city_business_id])->num_rows();
        if ($num_rows == 0)
        {
            return FALSE; // Record does not exist in database.
        }

        return $this->db->delete('ea_users', ['id' => $city_business_id]);
    }

    /**
     * Get a specific city business record from the database.
     *
     * @param int $city_business_id The id of the record to be returned.
     *
     * @return array Returns an array with the city business user data.
     *
     * @throws Exception When the $city_business_id is not a valid int value.
     * @throws Exception When given record id does not exist in the database.
     */
    public function get_row($city_business_id)
    {
        if ( ! is_numeric($city_business_id))
        {
            throw new Exception('Invalid value in city_business_id');
        }

        // Check if record exists
        if ($this->db->get_where('ea_users', ['id' => $city_business_id])->num_rows() == 0)
        {
            throw new Exception('The given city business id does not match a record in the database.');
        }

        $city_business = $this->db->get_where('ea_users', ['id' => $city_business_id])->row_array();

        $city_business_providers = $this->db->get_where('ea_city_business_providers',
            ['id_users_city_business' => $city_business['id']])->result_array();
        $city_business['providers'] = [];
        foreach ($city_business_providers as $city_business_provider)
        {
            $city_business['providers'][] = $city_business_provider['id_users_provider'];
        }

        $city_business['settings'] = $this->db->get_where('ea_user_settings',
            ['id_users' => $city_business['id']])->row_array();
        unset($city_business['settings']['id_users']);
        unset($city_business['settings']['password']);
        unset($city_business['settings']['salt']);

        return $city_business;
    }

    /**
     * Get a specific field value from the database.
     *
     * @param string $field_name The field name of the value to be returned.
     * @param int $city_business_id Record id of the value to be returned.
     *
     * @return string Returns the selected record value from the database.
     *
     * @throws Exception When the $field_name argument is not a valid string.
     * @throws Exception When the $city_business_id is not a valid int.
     * @throws Exception When the city business record does not exist in the database.
     * @throws Exception When the selected field value is not present on database.
     */
    public function get_value($field_name, $city_business_id)
    {
        if ( ! is_string($field_name))
        {
            throw new Exception('Invalid value in field_name');
        }

        if ( ! is_numeric($city_business_id))
        {
            throw new Exception('$city_business_id argument is not a valid numeric value: ' . $city_business_id);
        }

        // Check whether the city business record exists.
        $result = $this->db->get_where('ea_users', ['id' => $city_business_id]);
        if ($result->num_rows() == 0)
        {
            throw new Exception('The record with the given id does not exist in the '
                . 'database: ' . $city_business_id);
        }

        // Check if the required field name exist in database.
        $provider = $result->row_array();
        if ( ! isset($provider[$field_name]))
        {
            throw new Exception('The given $field_name argument does not exist in the '
                . 'database: ' . $field_name);
        }

        return $provider[$field_name];
    }

    /**
     * Get all, or specific city business records from database.
     *
     * @param string|array $where_clause (OPTIONAL) The WHERE clause of the query to be executed. Use this to get
     * specific city business records.
     *
     * @param array $search
     * @return array Returns an array with city business records.
     */
    public function get_batch($where_clause = '', $search = [])
    {
        $role_id = $this->get_city_business_role_id();

        if ($where_clause != '')
        {
            $this->db->where($where_clause);
        }

        if (!empty($search)) {
            $this->db->group_start()
                ->or_like($search)
                ->group_end();
        }

        $this->db->where('id_roles', $role_id);
        $batch = $this->db->get('ea_users')->result_array();

        // Include every city business providers.
        foreach ($batch as &$city_business)
        {
            $city_business_providers = $this->db->get_where('ea_city_business_providers',
                ['id_users_city_business' => $city_business['id']])->result_array();

            $city_business['providers'] = [];
            foreach ($city_business_providers as $city_business_provider)
            {
                $city_business['providers'][] = $city_business_provider['id_users_provider'];
            }

            $city_business['settings'] = $this->db->get_where('ea_user_settings',
                ['id_users' => $city_business['id']])->row_array();
            unset($city_business['settings']['id_users']);
            unset($city_business['settings']['password']);
            unset($city_business['settings']['salt']);
        }

        return $batch;
    }

    /**
     * Get the city business users role id.
     *
     * @return int Returns the role record id.
     */
    public function get_city_business_role_id()
    {
        return (int)$this->db->get_where('ea_roles', ['slug' => DB_SLUG_CITY_BUSINESS])->row()->id;
    }

    /**
     * Save a city business handling users.
     *
     * @param array $providers Contains the provider ids that are handled by the city business.
     * @param int $city_business_id The selected city business record.
     *
     * @throws Exception If $providers argument is invalid.
     */
    protected function save_providers($providers, $city_business_id)
    {
        if ( ! is_array($providers))
        {
            throw new Exception('Invalid value in providers');
        }

        // Delete old connections
        $this->db->delete('ea_city_business_providers', ['id_users_city_business' => $city_business_id]);

        if (count($providers) > 0)
        {
            foreach ($providers as $provider_id)
            {
                $this->db->insert('ea_city_business_providers', [
                    'id_users_city_business' => $city_business_id,
                    'id_users_provider' => $provider_id
                ]);
            }
        }
    }

    /**
     * Save the city business settings (used from insert or update operation).
     *
     * @param array $settings Contains the setting values.
     * @param int $city_business_id Record id of the city business.
     *
     * @throws Exception If $city_business_id argument is invalid.
     * @throws Exception If $settings argument is invalid.
     */
    protected function save_settings($settings, $city_business_id)
    {
        if ( ! is_numeric($city_business_id))
        {
            throw new Exception('Invalid value in city_business_id');
        }

        if (count($settings) == 0 || ! is_array($settings))
        {
            throw new Exception('Invalid value in settings');
        }

        // Check if the setting record exists in db.
        $num_rows = $this->db->get_where('ea_user_settings',
            ['id_users' => $city_business_id])->num_rows();
        if ($num_rows == 0)
        {
            $this->db->insert('ea_user_settings', ['id_users' => $city_business_id]);
        }

        foreach ($settings as $name => $value)
        {
            $this->set_setting($name, $value, $city_business_id);
        }
    }

    /**
     * Get a providers setting from the database.
     *
     * @param string $setting_name The setting name that is going to be returned.
     * @param int $city_business_id The selected provider id.
     *
     * @return string Returns the value of the selected user setting.
     */
    public function get_setting($setting_name, $city_business_id)
    {
        $provider_settings = $this->db->get_where('ea_user_settings',
            ['id_users' => $city_business_id])->row_array();
        return $provider_settings[$setting_name];
    }

    /**
     * Set a provider's setting value in the database.
     *
     * The provider and settings record must already exist.
     *
     * @param string $setting_name The setting's name.
     * @param string $value The setting's value.
     * @param int $city_business_id The selected provider id.
     */
    public function set_setting($setting_name, $value, $city_business_id)
    {
        $this->db->where(['id_users' => $city_business_id]);
        return $this->db->update('ea_user_settings', [$setting_name => $value]);
    }

    /**
     * Validate Records Username
     *
     * @param string $username The provider records username.
     * @param int $user_id The user record id.
     *
     * @return bool Returns the validation result.
     */
    public function validate_username($username, $user_id)
    {
        $num_rows = $this->db->get_where('ea_user_settings',
            ['username' => $username, 'id_users <> ' => $user_id])->num_rows();
        return ($num_rows > 0) ? FALSE : TRUE;
    }
}
