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

class Migration_Add_pui_fields extends CI_Migration {
    const FIELD_ADD_LIST = [
        'ea_users' => [
            'patient_pui' => 'VARCHAR(64) NULL AFTER `patient_consent_sms`',
            'patient_pui_reason_1' => 'VARCHAR(2) NOT NULL DEFAULT "0" AFTER `patient_pui`',
            'patient_pui_reason_2' => 'VARCHAR(2) NOT NULL DEFAULT "0" AFTER `patient_pui_reason_1`',
            'patient_pui_reason_3' => 'VARCHAR(2) NOT NULL DEFAULT "0" AFTER `patient_pui_reason_2`',
            'patient_pui_reason_4' => 'VARCHAR(2) NOT NULL DEFAULT "0" AFTER `patient_pui_reason_3`',
            'patient_pui_reason_5' => 'VARCHAR(2) NOT NULL DEFAULT "0" AFTER `patient_pui_reason_4`',
            'patient_pui_reason_6' => 'VARCHAR(2) NOT NULL DEFAULT "0" AFTER `patient_pui_reason_5`',
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
