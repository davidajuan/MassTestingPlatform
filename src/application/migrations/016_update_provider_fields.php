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

class Migration_Update_provider_fields extends CI_Migration {


    public function up()
    {
        // UPDATE
        // Globally set date format to MM-DD-YYYY
        $this->db->query('UPDATE `ea_settings` SET `value` = "MDY" WHERE (`name` = "date_format")');

        // Update default service number of attendies
        $this->db->query('UPDATE `ea_services` SET `attendants_number` = "50" WHERE (`id` = "1" AND `name` = "Test Service")');
    }

    public function down()
    {
    }
}
