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

class Migration_Add_first_responder_field extends CI_Migration {
    const FIELD_ADD_LIST = [
        'ea_users' => [
            'first_responder' => 'VARCHAR(2) NOT NULL DEFAULT "0" AFTER `caller`',
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
