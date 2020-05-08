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

class Migration_Business_doctor extends CI_Migration {
    const BUSINESS_DOCTOR_TABLE = "ea_business_doctor";

    public function up()
    {
        // Create business to doctor reference table
        // data type to match ea_users
        $q = 'CREATE TABLE IF NOT EXISTS `' . self::BUSINESS_DOCTOR_TABLE . '` (
            `id_business` INT(11) NOT NULL,
            `doctor_first_name` VARCHAR(256) NULL,
            `doctor_last_name` VARCHAR(512) NULL,
            `doctor_npi` VARCHAR(128) NULL,
            `doctor_address` VARCHAR(256) NULL,
            `doctor_city` VARCHAR(256) NULL,
            `doctor_state` VARCHAR(128) NULL,
            `doctor_zip_code` VARCHAR(64) NULL,
            `doctor_phone_number` VARCHAR(128) NULL,
            FOREIGN KEY (`id_business`) REFERENCES `ea_business` (`id`) ON DELETE CASCADE
        );';

        $this->db->query($q);
    }

    public function down()
    {
        // Delete tables
        $this->db->query('DROP TABLE `' . self::BUSINESS_DOCTOR_TABLE . '`;');
    }
}
