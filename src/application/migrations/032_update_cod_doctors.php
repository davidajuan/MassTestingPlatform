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

class Migration_Update_cod_doctors extends CI_Migration {

    public function up()
    {
        // Update all CoD patients to have the same doctor
        $sql = "UPDATE ea_users u JOIN ea_appointments a ON (a.id_users_customer = u.id)
            SET u.doctor_first_name = 'Najibah',
                u.doctor_last_name = 'Rehman',
                u.doctor_npi = '1033429394',
                u.doctor_address = '3245 E Jefferson Ave',
                u.doctor_city = 'Detroit',
                u.doctor_state = 'MI',
                u.doctor_zip_code = '48207',
                u.doctor_phone_number = '313-876-4000'
            WHERE a.business_code = ?";
        $this->db->query($sql, [Config::BUSINESS_CODE_CITY_WORKER]);
    }

    public function down()
    {
        // Do nothing as the previous data does not exist
    }
}
