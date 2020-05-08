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

class Migration_Add_caller_fields extends CI_Migration {

    const COLUMN_PROVIDER_CONF = 'provider_confirmation';
    const COLUMN_CALLER = 'caller';

    const ATTR_PROVIDER_CONF = [
        self::COLUMN_PROVIDER_CONF => [
            'type' => 'VARCHAR',
            'constraint' => '2',
            'default' => '0',
            'after' => 'provider_patient_id',
        ]
    ];
    const ATTR_CALLER = [
        self::COLUMN_CALLER => [
            'type' => 'VARCHAR',
            'constraint' => '32',
            'after' => 'rx_date',
            'null' => TRUE
        ]
    ];

    const TABLE_NAME = 'ea_users';
    const FIELD_MANIFEST = [
        self::COLUMN_PROVIDER_CONF => self::ATTR_PROVIDER_CONF,
        self::COLUMN_CALLER => self::ATTR_CALLER,
    ];


    const DROP_FIELDS = [
        'doctor_fax',
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
