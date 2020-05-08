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

class Migration_Remove_patient_conf extends CI_Migration {

    const TABLE_NAME = 'ea_users';
    const DROP_FIELDS = [
        'provider_confirmation',
    ];

    public function up()
    {
        // DROP
        foreach (self::DROP_FIELDS as $fieldName) {
            if ($this->db->field_exists($fieldName, self::TABLE_NAME)) {
                $this->dbforge->drop_column(self::TABLE_NAME, $fieldName);
            }
        }
    }

    public function down()
    {

    }
}
