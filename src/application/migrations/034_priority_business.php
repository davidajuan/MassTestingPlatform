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

class Migration_Priority_business extends CI_Migration {
    const FIELD_EA_BUSINESS_REQUEST_ADD_LIST = [
        'ea_business_request' => [
            'priority_service' => 'TINYINT(4) DEFAULT 0 AFTER `slots_approved`',
        ],
    ];

    public function up()
    {
        // Add Columns
        $this->addFields(self::FIELD_EA_BUSINESS_REQUEST_ADD_LIST);
    }

    public function down()
    {
        // Remove Columns
        $this->removeFields(self::FIELD_EA_BUSINESS_REQUEST_ADD_LIST);
    }
}
