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

class Migration_Database_sessions extends CI_Migration {

    const TABLE_NAME = 'ci_sessions';

    public function up()
    {
        // https://stackoverflow.com/a/30087774
        $q = 'CREATE TABLE IF NOT EXISTS `' . self::TABLE_NAME . '` (
            `id` varchar(40) NOT NULL,
            `ip_address` varchar(45) NOT NULL,
            `timestamp` int(10) unsigned DEFAULT 0 NOT NULL,
            `data` blob NOT NULL,
            PRIMARY KEY (id),
            KEY `ci_sessions_timestamp` (`timestamp`)
        );';

        $this->db->query($q);
    }

    public function down()
    {
        $this->db->query('DROP TABLE `' . self::TABLE_NAME . '`;');
    }
}
