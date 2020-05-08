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

class Migration_Add_doctor_fields extends CI_Migration {

    const COLUMN_DOCTOR_NPI = 'doctor_npi';
    const COLUMN_DOCTOR_ADDRESS = 'doctor_address';
    const COLUMN_DOCTOR_CITY = 'doctor_city';
    const COLUMN_DOCTOR_STATE = 'doctor_state';
    const COLUMN_DOCTOR_ZIP_CODE = 'doctor_zip_code';

    const ATTR_DOCTOR_NPI = [
        self::COLUMN_DOCTOR_NPI => [
            'type' => 'VARCHAR',
            'constraint' => '128',
            'after' => 'doctor_last_name',
            'null' => TRUE
        ]
    ];
    const ATTR_DOCTOR_ADDRESS = [
        self::COLUMN_DOCTOR_ADDRESS => [
            'type' => 'VARCHAR',
            'constraint' => '256',
            'after' => self::COLUMN_DOCTOR_NPI,
            'null' => TRUE
        ]
    ];
    const ATTR_DOCTOR_CITY = [
        self::COLUMN_DOCTOR_CITY => [
            'type' => 'VARCHAR',
            'constraint' => '256',
            'after' => self::COLUMN_DOCTOR_ADDRESS,
            'null' => TRUE
        ]
    ];
    const ATTR_DOCTOR_STATE = [
        self::COLUMN_DOCTOR_STATE => [
            'type' => 'VARCHAR',
            'constraint' => '128',
            'after' => self::COLUMN_DOCTOR_CITY,
            'null' => TRUE
        ]
    ];
    const ATTR_DOCTOR_ZIP_CODE = [
        self::COLUMN_DOCTOR_ZIP_CODE => [
            'type' => 'VARCHAR',
            'constraint' => '64',
            'after' => self::COLUMN_DOCTOR_STATE,
            'null' => TRUE
        ]
    ];


    const TABLE_NAME = 'ea_users';
    const FIELD_MANIFEST = [
        self::COLUMN_DOCTOR_NPI => self::ATTR_DOCTOR_NPI,
        self::COLUMN_DOCTOR_ADDRESS => self::ATTR_DOCTOR_ADDRESS,
        self::COLUMN_DOCTOR_CITY => self::ATTR_DOCTOR_CITY,
        self::COLUMN_DOCTOR_STATE => self::ATTR_DOCTOR_STATE,
        self::COLUMN_DOCTOR_ZIP_CODE => self::ATTR_DOCTOR_ZIP_CODE,
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
