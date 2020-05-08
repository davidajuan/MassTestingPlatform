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

class Migration_Add_patient_fields extends CI_Migration {

    const COLUMN_DOB = 'dob';
    const COLUMN_LANGUAGE_PREF = 'language_pref';

    const COLUMN_PATIENT_ID = 'patient_id';
    const COLUMN_PATIENT_CONTACT_PREF = 'patient_contact_pref';
    const COLUMN_PATIENT_CONSENT = 'patient_consent';

    const COLUMN_DOCTOR_FIRST_NAME = 'doctor_first_name';
    const COLUMN_DOCTOR_LAST_NAME = 'doctor_last_name';
    const COLUMN_DOCTOR_PROVIDER = 'doctor_provider';
    const COLUMN_DOCTOR_PHONE_NUMBER = 'doctor_phone_number';
    const COLUMN_DOCTOR_FAX = 'doctor_fax';


    const ATTR_DOB = [
        self::COLUMN_DOB => [
            'type' => 'DATETIME',
            'null' => TRUE
        ]
    ];
    const ATTR_LANGUAGE_PREF = [
        self::COLUMN_LANGUAGE_PREF => [
            'type' => 'VARCHAR',
            'constraint' => '8',
            'default' => 'en',
            'comment' => 'ISO 639-1 Language Codes'
        ]
    ];


    const ATTR_PATIENT_ID = [
        self::COLUMN_PATIENT_ID => [
            'type' => 'VARCHAR',
            'constraint' => '128',
            'null' => TRUE
        ]
    ];
    const ATTR_PATIENT_CONTACT_PREF = [
        self::COLUMN_PATIENT_CONTACT_PREF => [
            'type' => 'VARCHAR',
            'constraint' => '2',
            'default' => '0',
        ]
    ];
    const ATTR_PATIENT_CONSENT = [
        self::COLUMN_PATIENT_CONSENT => [
            'type' => 'VARCHAR',
            'constraint' => '2',
            'default' => '0',
        ]
    ];


    const ATTR_DOCTOR_FIRST_NAME = [
        self::COLUMN_DOCTOR_FIRST_NAME => [
            'type' => 'VARCHAR',
            'constraint' => '256',
            'null' => TRUE
        ]
    ];
    const ATTR_DOCTOR_LAST_NAME = [
        self::COLUMN_DOCTOR_LAST_NAME => [
            'type' => 'VARCHAR',
            'constraint' => '512',
            'null' => TRUE
        ]
    ];
    const ATTR_DOCTOR_PROVIDER = [
        self::COLUMN_DOCTOR_PROVIDER => [
            'type' => 'VARCHAR',
            'constraint' => '256',
            'null' => TRUE
        ]
    ];
    const ATTR_DOCTOR_PHONE_NUMBER = [
        self::COLUMN_DOCTOR_PHONE_NUMBER => [
            'type' => 'VARCHAR',
            'constraint' => '128',
            'null' => TRUE
        ]
    ];
    const ATTR_DOCTOR_FAX = [
        self::COLUMN_DOCTOR_FAX => [
            'type' => 'VARCHAR',
            'constraint' => '128',
            'null' => TRUE
        ]
    ];


    const TABLE_NAME = 'ea_users';
    const FIELD_MANIFEST = [
        self::COLUMN_DOB => self::ATTR_DOB,
        self::COLUMN_LANGUAGE_PREF => self::ATTR_LANGUAGE_PREF,

        self::COLUMN_PATIENT_ID => self::ATTR_PATIENT_ID,
        self::COLUMN_PATIENT_CONTACT_PREF => self::ATTR_PATIENT_CONTACT_PREF,
        self::COLUMN_PATIENT_CONSENT => self::ATTR_PATIENT_CONSENT,

        self::COLUMN_DOCTOR_FIRST_NAME => self::ATTR_DOCTOR_FIRST_NAME,
        self::COLUMN_DOCTOR_LAST_NAME => self::ATTR_DOCTOR_LAST_NAME,
        self::COLUMN_DOCTOR_PROVIDER => self::ATTR_DOCTOR_PROVIDER,
        self::COLUMN_DOCTOR_PHONE_NUMBER => self::ATTR_DOCTOR_PHONE_NUMBER,
        self::COLUMN_DOCTOR_FAX => self::ATTR_DOCTOR_FAX,
    ];

    public function up()
    {
        // Loop and check if these fields exist, add if not
        foreach (self::FIELD_MANIFEST as $fieldName => $fieldAttrs) {
            if (!$this->db->field_exists($fieldName, self::TABLE_NAME)) {
                $this->dbforge->add_column(self::TABLE_NAME, $fieldAttrs);
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
