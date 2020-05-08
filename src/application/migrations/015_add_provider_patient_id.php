<?php defined('BASEPATH') OR exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * Easy!Appointments - Open Source Web Scheduler
 *
 * @package     EasyAppointments
 * @author      A.Tselegidis <alextselegidis@gmail.com>
 * @copyright   Copyright (c) 2013 - 2018, Alex Tselegidis
 * @license     http://opensource.org/licenses/GPL-3.0 - GPLv3
 * @link        http://easyappointments.org
 * @since       v1.3.2
 * ---------------------------------------------------------------------------- */

class Migration_Add_provider_patient_id extends CI_Migration {

    const COLUMN_PROVIDER_PATIENT_ID = 'provider_patient_id';

    const ATTR_PROVIDER_PATIENT_ID = [
        self::COLUMN_PROVIDER_PATIENT_ID => [
            'type' => 'VARCHAR',
            'constraint' => '128',
            'after' => 'doctor_provider_other',
            'null' => TRUE
        ]
    ];

    const TABLE_NAME = 'ea_users';
    const FIELD_MANIFEST = [
        self::COLUMN_PROVIDER_PATIENT_ID => self::ATTR_PROVIDER_PATIENT_ID,
    ];

    public function up()
    {
        // ADD
        // Loop and check if these fields exist, add if not
        foreach (self::FIELD_MANIFEST as $fieldName => $fieldAttrs) {
            if (!$this->db->field_exists($fieldName, self::TABLE_NAME)) {
                $this->dbforge->add_column(self::TABLE_NAME, $fieldAttrs);
            }
        }

        // UPDATE
        // Globally set date format to MM-DD-YYYY
        $this->db->query('UPDATE `ea_settings` SET `value` = "MDY" WHERE (`name` = "date_format")');

        // Set default service install to 60 mins, if it hasn't been assigned already
        $this->db->query('UPDATE `ea_services` SET `duration` = "60" WHERE (`id` = "1" AND `name` = "Test Service")');
    }

    public function down()
    {
        // Loop and check if these fields exist, drop if they do
        foreach (self::FIELD_MANIFEST as $fieldName => $fieldAttrs) {
            if ($this->db->field_exists($fieldName, self::TABLE_NAME)) {
                $this->dbforge->drop_column(self::TABLE_NAME, $fieldName);
            }
        }
    }
}
