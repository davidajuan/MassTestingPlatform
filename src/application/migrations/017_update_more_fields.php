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

class Migration_Update_more_fields extends CI_Migration {

    const COLUMN_MIDDLE_NAME = 'middle_initial';
    const COLUMN_GENDER = 'gender';
    const COLUMN_ADDRESS_APT = 'apt';
    const COLUMN_PATIENT_CONSENT_SMS = 'patient_consent_sms';

    const ATTR_MIDDLE_NAME = [
        self::COLUMN_MIDDLE_NAME => [
            'type' => 'VARCHAR',
            'constraint' => '128',
            'after' => 'last_name',
            'null' => TRUE
        ]
    ];
    const ATTR_GENDER = [
        self::COLUMN_GENDER => [
            'type' => 'VARCHAR',
            'constraint' => '32',
            'after' => 'id_roles',
            'null' => TRUE
        ]
    ];
    const ATTR_ADDRESS_APT = [
        self::COLUMN_ADDRESS_APT => [
            'type' => 'VARCHAR',
            'constraint' => '256',
            'after' => 'address',
            'null' => TRUE
        ]
    ];
    const ATTR_PATIENT_CONSENT_SMS = [
        self::COLUMN_PATIENT_CONSENT_SMS => [
            'type' => 'VARCHAR',
            'constraint' => '2',
            'after' => 'patient_consent',
            'default' => '0',
        ]
    ];


    const TABLE_NAME = 'ea_users';
    const FIELD_MANIFEST = [
        self::COLUMN_MIDDLE_NAME => self::ATTR_MIDDLE_NAME,
        self::COLUMN_GENDER => self::ATTR_GENDER,
        self::COLUMN_ADDRESS_APT => self::ATTR_ADDRESS_APT,
        self::COLUMN_PATIENT_CONSENT_SMS => self::ATTR_PATIENT_CONSENT_SMS,
    ];


    const DROP_FIELDS = [
        'language_pref',
        'language_pref_other',
        'doctor_provider',
        'doctor_provider_other',
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

        // DROP
        foreach (self::DROP_FIELDS as $fieldName) {
            if ($this->db->field_exists($fieldName, self::TABLE_NAME)) {
                $this->dbforge->drop_column(self::TABLE_NAME, $fieldName);
            }
        }
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
