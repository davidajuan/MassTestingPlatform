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

class Migration_Add_optional_customer_fields extends CI_Migration {
    const FIELD_ADD_LIST = [
        'ea_users' => [
            'county' => 'VARCHAR(32) NULL',
            'pcp_first_name' => 'VARCHAR(32) NULL',
            'pcp_last_name' => 'VARCHAR(32) NULL',
        ],
    ];

    const UNIQUE_ADD_LIST = [
        'ea_users' => [
            'patient_id' => '(`patient_id` ASC) VISIBLE',
        ],
        'ea_appointments' => [
            'hash' => '(`hash` ASC) VISIBLE',
        ],
    ];

    public function up()
    {
        // Change type first
        $this->db->query('ALTER TABLE `ea_appointments` CHANGE COLUMN `hash` `hash` VARCHAR(255)');

        // Add uniqueness loop
        $this->addIndexes(self::UNIQUE_ADD_LIST, 'UNIQUE');

        // Add fields
        $this->addFields(self::FIELD_ADD_LIST);
    }

    public function down()
    {
        // Remove uniqueness loop
        // Special case, we want to remove any bugged and duplicate indexes that were created in the past by accident
        $newList = self::UNIQUE_ADD_LIST;
        $newList['ea_users']['patient_id_2'] = '';
        $newList['ea_appointments']['hash_2'] = '';
        $this->removeIndexes($newList);

        // Change type
        $this->db->query('ALTER TABLE `ea_appointments` CHANGE COLUMN `hash` `hash` TEXT');

        // Remove fields
        $this->removeFields(self::FIELD_ADD_LIST);
    }
}
