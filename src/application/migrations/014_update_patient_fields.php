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

class Migration_Update_patient_fields extends CI_Migration {

    const COLUMN_DOCTOR_PROVIDER_OTHER = 'doctor_provider_other';
    const COLUMN_LANGUAGE_PREF_OTHER = 'language_pref_other';
    const COLUMN_RX_DATE = 'rx_date';
    const COLUMN_PATIENT_SSN = 'ssn';


    const ATTR_DOCTOR_PROVIDER_OTHER = [
        self::COLUMN_DOCTOR_PROVIDER_OTHER => [
            'type' => 'VARCHAR',
            'constraint' => '256',
            'after' => 'doctor_provider',
            'null' => TRUE
        ]
    ];
    const ATTR_LANGUAGE_PREF_OTHER = [
        self::COLUMN_LANGUAGE_PREF_OTHER => [
            'type' => 'VARCHAR',
            'constraint' => '64',
            'after' => 'language_pref',
            'null' => TRUE
        ]
    ];
    const ATTR_RX_DATE = [
        self::COLUMN_RX_DATE => [
            'type' => 'DATETIME',
            'after' => 'doctor_fax',
            'null' => TRUE
        ]
    ];
    const ATTR_PATIENT_SSN = [
        self::COLUMN_PATIENT_SSN => [
            'type' => 'VARCHAR',
            'constraint' => '4',
            'after' => 'dob',
            'null' => TRUE
        ]
    ];

    const TABLE_NAME = 'ea_users';
    const FIELD_MANIFEST = [
        self::COLUMN_DOCTOR_PROVIDER_OTHER => self::ATTR_DOCTOR_PROVIDER_OTHER,
        self::COLUMN_LANGUAGE_PREF_OTHER => self::ATTR_LANGUAGE_PREF_OTHER,
        self::COLUMN_RX_DATE => self::ATTR_RX_DATE,
        self::COLUMN_PATIENT_SSN => self::ATTR_PATIENT_SSN,
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

        // ALTER
        $this->db->query('ALTER TABLE `ea_users` CHANGE COLUMN `language_pref` `language_pref` VARCHAR(64) NULL DEFAULT "english"');

        // UPDATE
        // only give edit access to customers for the role of 'secretaries'
        $this->db->query('UPDATE `ea_roles` SET `customers` = "5", `appointments` = "0", `user_settings` = "0" WHERE (`id` = "4")');
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
