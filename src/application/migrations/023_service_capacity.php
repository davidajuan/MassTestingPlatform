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

class Migration_Service_capacity extends CI_Migration {

    const TABLE_NAME = 'ea_service_capacity';

    public function up()
    {
        $q = 'CREATE TABLE IF NOT EXISTS `' . self::TABLE_NAME . '` (
            `id` varchar(40) NOT NULL,
            `start_datetime` DATETIME NOT NULL,
            `end_datetime` DATETIME NOT NULL,
            `id_services` INT(11),
            `attendants_number` INT(11),
            PRIMARY KEY (id),
            FOREIGN KEY (`id_services`) REFERENCES `ea_services` (`id`)
        );';

        $this->db->query($q);
    }

    public function down()
    {
        $this->db->query('DROP TABLE `' . self::TABLE_NAME . '`;');
    }
}
