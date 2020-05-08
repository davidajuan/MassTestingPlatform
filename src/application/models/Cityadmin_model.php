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
 * City Admin Model
 *
 * Handles the db actions that have to do with city admin.
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
 *  'providers' >> array with provider ids that the city admin handles
 *  'settings' >> array with the city admin settings
 *
 * @package Models
 */
class Cityadmin_Model extends CI_Model {
    /**
     * Add (insert or update) a city admin user record into database.
     *
     * @param array $city_admin Contains the city admin user data.
     *
     * @return int Returns the record id.
     *
     * @throws Exception When the city admin data are invalid (see validate() method).
     */
    public function add($city_admin)
    {
        // Sanitize Data
        $city_admin = $this->security->xss_clean($city_admin);

        $this->validate($city_admin);

        if ($this->exists($city_admin) && ! isset($city_admin['id']))
        {
            $city_admin['id'] = $this->find_record_id($city_admin);
        }

        if ( ! isset($city_admin['id']))
        {
            $city_admin['id'] = $this->_insert($city_admin);
        }
        else
        {
            $city_admin['id'] = $this->_update($city_admin);
        }

        return (int)$city_admin['id'];
    }

    /**
     * Check whether a particular city admin record exists in the database.
     *
     * @param array $city_admin Contains the city admin data. The 'email' value is required to be present at the moment.
     *
     * @return bool Returns whether the record exists or not.
     *
     * @throws Exception When the 'email' value is not present on the $city_admin argument.
     */
    public function exists($city_admin)
    {
        if ( ! isset($city_admin['email']))
        {
            throw new Exception('City admin email is not provided: ' . print_r($city_admin, TRUE));
        }

        // This method shouldn't depend on another method of this class.
        $num_rows = $this->db
            ->select('*')
            ->from('ea_users')
            ->join('ea_roles', 'ea_roles.id = ea_users.id_roles', 'inner')
            ->where('ea_users.email', $city_admin['email'])
            ->where('ea_roles.slug', DB_SLUG_CITY_ADMIN)
            ->get()->num_rows();

        return ($num_rows > 0) ? TRUE : FALSE;
    }

    /**
     * Insert a new city admin record into the database.
     *
     * @param array $city_admin Contains the city admin data.
     *
     * @return int Returns the new record id.
     *
     * @throws Exception When the insert operation fails.
     */
    protected function _insert($city_admin)
    {
        $this->load->helper('general');

        $providers = $city_admin['providers'];
        unset($city_admin['providers']);
        $settings = $city_admin['settings'];
        unset($city_admin['settings']);

        $city_admin['id_roles'] = $this->get_city_admin_role_id();

        if ( ! $this->db->insert('ea_users', $city_admin))
        {
            throw new Exception('Could not insert city admin into the database.');
        }

        $city_admin['id'] = (int)$this->db->insert_id();
        $settings['salt'] = generate_salt();
        $settings['password'] = hash_password($settings['salt'], $settings['password']);

        $this->save_providers($providers, $city_admin['id']);
        $this->save_settings($settings, $city_admin['id']);

        return $city_admin['id'];
    }

    /**
     * Update an existing city admin record in the database.
     *
     * @param array $city_admin Contains the city admin record data.
     *
     * @return int Returns the record id.
     *
     * @throws Exception When the update operation fails.
     */
    protected function _update($city_admin)
    {
        $this->load->helper('general');

        $providers = $city_admin['providers'];
        unset($city_admin['providers']);
        $settings = $city_admin['settings'];
        unset($city_admin['settings']);

        if (isset($settings['password']))
        {
            $salt = $this->db->get_where('ea_user_settings', ['id_users' => $city_admin['id']])->row()->salt;
            $settings['password'] = hash_password($salt, $settings['password']);
        }

        $this->db->where('id', $city_admin['id']);
        if ( ! $this->db->update('ea_users', $city_admin))
        {
            throw new Exception('Could not update city admin record.');
        }

        $this->save_providers($providers, $city_admin['id']);
        $this->save_settings($settings, $city_admin['id']);

        return (int)$city_admin['id'];
    }

    /**
     * Find the database record id of a city admin.
     *
     * @param array $city_admin Contains the city admin data. The 'email' value is required in order to find the record id.
     *
     * @return int Returns the record id
     *
     * @throws Exception When the 'email' value is not present on the $city_admin array.
     */
    public function find_record_id($city_admin)
    {
        if ( ! isset($city_admin['email']))
        {
            throw new Exception('City admin email was not provided: ' . print_r($city_admin, TRUE));
        }

        $result = $this->db
            ->select('ea_users.id')
            ->from('ea_users')
            ->join('ea_roles', 'ea_roles.id = ea_users.id_roles', 'inner')
            ->where('ea_users.email', $city_admin['email'])
            ->where('ea_roles.slug', DB_SLUG_CITY_ADMIN)
            ->get();

        if ($result->num_rows() == 0)
        {
            throw new Exception('Could not find city admin record id.');
        }

        return (int)$result->row()->id;
    }

    /**
     * Validate city admin user data before add() operation is executed.
     *
     * @param array $city_admin Contains the city admin user data.
     *
     * @return bool Returns the validation result.
     *
     * @throws Exception If city admin validation fails.
     */
    public function validate($city_admin)
    {
        $this->load->helper('data_validation');

        // If a record id is provided then check whether the record exists in the database.
        if (isset($city_admin['id']))
        {
            $num_rows = $this->db->get_where('ea_users', ['id' => $city_admin['id']])
                ->num_rows();
            if ($num_rows == 0)
            {
                throw new Exception('Given city admin id does not exist in database: ' . $city_admin['id']);
            }
        }

        // Validate 'providers' value data type (must be array)
        if (isset($city_admin['providers']) && ! is_array($city_admin['providers']))
        {
            throw new Exception('City admin providers value is not an array.');
        }

        // Validate required fields integrity.
        if ( ! isset($city_admin['last_name'])
            || ! isset($city_admin['email'])
            || ! isset($city_admin['phone_number']))
        {
            throw new Exception('Not all required fields are provided: ' . print_r($city_admin, TRUE));
        }

        // Validate city admin email address.
        if ( ! filter_var($city_admin['email'], FILTER_VALIDATE_EMAIL))
        {
            throw new Exception('Invalid email address provided: ' . $city_admin['email']);
        }

        // Check if username exists.
        if (isset($city_admin['settings']['username']))
        {
            $user_id = (isset($city_admin['id'])) ? $city_admin['id'] : '';
            if ( ! $this->validate_username($city_admin['settings']['username'], $user_id))
            {
                throw new Exception ('Username already exists. Please select a different '
                    . 'username for this record.');
            }
        }

        // Validate city admin password.
        if (isset($city_admin['settings']['password']))
        {
            if (strlen($city_admin['settings']['password']) < MIN_PASSWORD_LENGTH)
            {
                throw new Exception('The user password must be at least '
                    . MIN_PASSWORD_LENGTH . ' characters long.');
            }
        }

        // Validate calendar view mode.
        if (isset($city_admin['settings']['calendar_view']) && ($city_admin['settings']['calendar_view'] !== CALENDAR_VIEW_DEFAULT
                && $city_admin['settings']['calendar_view'] !== CALENDAR_VIEW_TABLE))
        {
            throw new Exception('The calendar view setting must be either "' . CALENDAR_VIEW_DEFAULT
                . '" or "' . CALENDAR_VIEW_TABLE . '", given: ' . $city_admin['settings']['calendar_view']);
        }

        // When inserting a record the email address must be unique.
        $city_admin_id = (isset($city_admin['id'])) ? $city_admin['id'] : '';

        $num_rows = $this->db
            ->select('*')
            ->from('ea_users')
            ->join('ea_roles', 'ea_roles.id = ea_users.id_roles', 'inner')
            ->where('ea_roles.slug', DB_SLUG_CITY_ADMIN)
            ->where('ea_users.email', $city_admin['email'])
            ->where('ea_users.id <>', $city_admin_id)
            ->get()
            ->num_rows();

        if ($num_rows > 0)
        {
            throw new Exception('Given email address belongs to another city admin record. '
                . 'Please use a different email.');
        }

        return TRUE;
    }

    /**
     * Delete an existing city admin record from the database.
     *
     * @param int $city_admin_id The city admin record id to be deleted.
     *
     * @return bool Returns the delete operation result.
     *
     * @throws Exception When the $city_admin_id is not a valid int value.
     */
    public function delete($city_admin_id)
    {
        if ( ! is_numeric($city_admin_id))
        {
            throw new Exception('Invalid value in city_admin_id');
        }

        $num_rows = $this->db->get_where('ea_users', ['id' => $city_admin_id])->num_rows();
        if ($num_rows == 0)
        {
            return FALSE; // Record does not exist in database.
        }

        return $this->db->delete('ea_users', ['id' => $city_admin_id]);
    }

    /**
     * Get a specific city admin record from the database.
     *
     * @param int $city_admin_id The id of the record to be returned.
     *
     * @return array Returns an array with the city admin user data.
     *
     * @throws Exception When the $city_admin_id is not a valid int value.
     * @throws Exception When given record id does not exist in the database.
     */
    public function get_row($city_admin_id)
    {
        if ( ! is_numeric($city_admin_id))
        {
            throw new Exception('Invalid value in city_admin_id');
        }

        // Check if record exists
        if ($this->db->get_where('ea_users', ['id' => $city_admin_id])->num_rows() == 0)
        {
            throw new Exception('The given city admin id does not match a record in the database.');
        }

        $city_admin = $this->db->get_where('ea_users', ['id' => $city_admin_id])->row_array();

        $city_admin_providers = $this->db->get_where('ea_city_admin_providers',
            ['id_users_city_admin' => $city_admin['id']])->result_array();
        $city_admin['providers'] = [];
        foreach ($city_admin_providers as $city_admin_provider)
        {
            $city_admin['providers'][] = $city_admin_provider['id_users_provider'];
        }

        $city_admin['settings'] = $this->db->get_where('ea_user_settings',
            ['id_users' => $city_admin['id']])->row_array();
        unset($city_admin['settings']['id_users']);
        unset($city_admin['settings']['password']);
        unset($city_admin['settings']['salt']);

        return $city_admin;
    }

    /**
     * Get a specific field value from the database.
     *
     * @param string $field_name The field name of the value to be returned.
     * @param int $city_admin_id Record id of the value to be returned.
     *
     * @return string Returns the selected record value from the database.
     *
     * @throws Exception When the $field_name argument is not a valid string.
     * @throws Exception When the $city_admin_id is not a valid int.
     * @throws Exception When the city admin record does not exist in the database.
     * @throws Exception When the selected field value is not present on database.
     */
    public function get_value($field_name, $city_admin_id)
    {
        if ( ! is_string($field_name))
        {
            throw new Exception('Invalid value in field_name');
        }

        if ( ! is_numeric($city_admin_id))
        {
            throw new Exception('Invalid value in city_admin_id');
        }

        // Check whether the city admin record exists.
        $result = $this->db->get_where('ea_users', ['id' => $city_admin_id]);
        if ($result->num_rows() == 0)
        {
            throw new Exception('The record with the given id does not exist in the '
                . 'database: ' . $city_admin_id);
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
     * Get all, or specific city admin records from database.
     *
     * @param string|array $where_clause (OPTIONAL) The WHERE clause of the query to be executed. Use this to get
     * specific city admin records.
     *
     * @param array $search
     * @return array Returns an array with city admin records.
     */
    public function get_batch($where_clause = '', $search = [])
    {
        $role_id = $this->get_city_admin_role_id();

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

        // Include every city admin providers.
        foreach ($batch as &$city_admin)
        {
            $city_admin_providers = $this->db->get_where('ea_city_admin_providers',
                ['id_users_city_admin' => $city_admin['id']])->result_array();

            $city_admin['providers'] = [];
            foreach ($city_admin_providers as $city_admin_provider)
            {
                $city_admin['providers'][] = $city_admin_provider['id_users_provider'];
            }

            $city_admin['settings'] = $this->db->get_where('ea_user_settings',
                ['id_users' => $city_admin['id']])->row_array();
            unset($city_admin['settings']['id_users']);
            unset($city_admin['settings']['password']);
            unset($city_admin['settings']['salt']);
        }

        return $batch;
    }

    /**
     * Get the city admin users role id.
     *
     * @return int Returns the role record id.
     */
    public function get_city_admin_role_id()
    {
        return (int)$this->db->get_where('ea_roles', ['slug' => DB_SLUG_CITY_ADMIN])->row()->id;
    }

    /**
     * Save a city admin handling users.
     *
     * @param array $providers Contains the provider ids that are handled by the city admin.
     * @param int $city_admin_id The selected city admin record.
     *
     * @throws Exception If $providers argument is invalid.
     */
    protected function save_providers($providers, $city_admin_id)
    {
        if ( ! is_array($providers))
        {
            throw new Exception('Invalid value in providers');
        }

        // Delete old connections
        $this->db->delete('ea_city_admin_providers', ['id_users_city_admin' => $city_admin_id]);

        if (count($providers) > 0)
        {
            foreach ($providers as $provider_id)
            {
                $this->db->insert('ea_city_admin_providers', [
                    'id_users_city_admin' => $city_admin_id,
                    'id_users_provider' => $provider_id
                ]);
            }
        }
    }

    /**
     * Save the city admin settings (used from insert or update operation).
     *
     * @param array $settings Contains the setting values.
     * @param int $city_admin_id Record id of the city admin.
     *
     * @throws Exception If $city_admin_id argument is invalid.
     * @throws Exception If $settings argument is invalid.
     */
    protected function save_settings($settings, $city_admin_id)
    {
        if ( ! is_numeric($city_admin_id))
        {
            throw new Exception('Invalid value in city_admin_id');
        }

        if (count($settings) == 0 || ! is_array($settings))
        {
            throw new Exception('Invalid value in settings');
        }

        // Check if the setting record exists in db.
        $num_rows = $this->db->get_where('ea_user_settings',
            ['id_users' => $city_admin_id])->num_rows();
        if ($num_rows == 0)
        {
            $this->db->insert('ea_user_settings', ['id_users' => $city_admin_id]);
        }

        foreach ($settings as $name => $value)
        {
            $this->set_setting($name, $value, $city_admin_id);
        }
    }

    /**
     * Get a providers setting from the database.
     *
     * @param string $setting_name The setting name that is going to be returned.
     * @param int $city_admin_id The selected provider id.
     *
     * @return string Returns the value of the selected user setting.
     */
    public function get_setting($setting_name, $city_admin_id)
    {
        $provider_settings = $this->db->get_where('ea_user_settings',
            ['id_users' => $city_admin_id])->row_array();
        return $provider_settings[$setting_name];
    }

    /**
     * Set a provider's setting value in the database.
     *
     * The provider and settings record must already exist.
     *
     * @param string $setting_name The setting's name.
     * @param string $value The setting's value.
     * @param int $city_admin_id The selected provider id.
     */
    public function set_setting($setting_name, $value, $city_admin_id)
    {
        $this->db->where(['id_users' => $city_admin_id]);
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
