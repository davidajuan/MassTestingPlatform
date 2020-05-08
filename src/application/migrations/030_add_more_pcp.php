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

class Migration_Add_more_pcp extends CI_Migration {
    const FIELD_ADD_LIST = [
        'ea_users' => [
            'pcp_phone_number' => 'VARCHAR(32) DEFAULT NULL AFTER `pcp_last_name`',
            'pcp_address' => 'VARCHAR(75) DEFAULT NULL AFTER `pcp_phone_number`',
            'pcp_city' => 'VARCHAR(32) DEFAULT NULL AFTER `pcp_address`',
            'pcp_state' => 'VARCHAR(32) DEFAULT NULL AFTER `pcp_city`',
            'pcp_zip_code' => 'VARCHAR(32) DEFAULT NULL AFTER `pcp_state`',
        ],
    ];

    public function up()
    {
        // Add fields
        $this->addFields(self::FIELD_ADD_LIST);
    }

    public function down()
    {
        // Remove fields
        $this->removeFields(self::FIELD_ADD_LIST);
    }
}
