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

class Migration_Business_code_fields extends CI_Migration {
    const FIELD_ADD_LIST = [
        'ea_appointments' => [
            'business_code' => 'VARCHAR(20) NULL',
        ],
    ];

    const UNIQUE_ADD_LIST = [
        'ea_business_request' => [
            'business_code' => '(`business_code` ASC) VISIBLE',
        ]
    ];

    public function up()
    {
        // Add uniqueness loop
        $this->addIndexes(self::UNIQUE_ADD_LIST, 'UNIQUE');

        // Add fields
        $this->addFields(self::FIELD_ADD_LIST);

        // update modified/create fields to not come from mysql
        $this->db->query('ALTER TABLE `ea_business` CHANGE COLUMN `created` `created` DATETIME NOT NULL');
        $this->db->query('ALTER TABLE `ea_business` CHANGE COLUMN `modified` `modified` DATETIME NOT NULL');
        $this->db->query('ALTER TABLE `ea_business_request` CHANGE COLUMN `created` `created` DATETIME NOT NULL');
        $this->db->query('ALTER TABLE `ea_business_request` CHANGE COLUMN `modified` `modified` DATETIME NOT NULL');
    }

    public function down()
    {
        // remove uniqueness
        $this->removeIndexes(self::UNIQUE_ADD_LIST);

        // Remove fields
        $this->removeFields(self::FIELD_ADD_LIST);

        // update modified/create fields to not come from mysql
        $this->db->query('ALTER TABLE `ea_business` CHANGE COLUMN `created` `created` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP');
        $this->db->query('ALTER TABLE `ea_business` CHANGE COLUMN `modified` `modified` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP');
        $this->db->query('ALTER TABLE `ea_business_request` CHANGE COLUMN `created` `created` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP');
        $this->db->query('ALTER TABLE `ea_business_request` CHANGE COLUMN `modified` `modified` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP');
    }
}
