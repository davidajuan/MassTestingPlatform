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

class Migration_Business_tables extends CI_Migration {
    const BUSINESS_TABLE = 'ea_business';
    const BUSINESS_REQ_TABLE = 'ea_business_request';

    public function up()
    {
        $q = 'CREATE TABLE IF NOT EXISTS `' . self::BUSINESS_TABLE . '` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `business_name` varchar(100) NOT NULL,
            `owner_first_name` varchar(50) NOT NULL,
            `owner_last_name` varchar(50) NOT NULL,
            `business_phone` varchar(15) NOT NULL,
            `mobile_phone` varchar(15),
            `consent_sms` varchar(2) DEFAULT 0 NOT NULL,
            `email` varchar(75),
            `consent_email` varchar(2) DEFAULT 0 NOT NULL,
            `address` varchar(75) NOT NULL,
            `city` varchar(75) NOT NULL,
            `state` varchar(5) NOT NULL,
            `zip_code` varchar(10) NOT NULL,
            `slots_remaining` INT(11) DEFAULT 0 NOT NULL,
            `hash` varchar(20) NOT NULL UNIQUE,
            `modified` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            ON UPDATE CURRENT_TIMESTAMP,
            `created` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        );';

        $this->db->query($q);

        $q = 'CREATE TABLE IF NOT EXISTS `' . self::BUSINESS_REQ_TABLE . '` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `id_business` INT(11) NOT NULL,
            `business_code` varchar(20) NOT NULL,
            `status` ENUM(
                \''.DB_SLUG_BUSINESS_REQ_ACTIVE.'\',
                \''.DB_SLUG_BUSINESS_REQ_PENDING.'\',
                \''.DB_SLUG_BUSINESS_REQ_DELETED.'\')
                DEFAULT \''.DB_SLUG_BUSINESS_REQ_PENDING.'\' NOT NULL,
            `slots_requested` INT(11) DEFAULT 0 NOT NULL,
            `slots_approved` INT(11) DEFAULT 0 NOT NULL,
            `modified` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            ON UPDATE CURRENT_TIMESTAMP,
            `created` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            FOREIGN KEY (`id_business`) REFERENCES `' . self::BUSINESS_TABLE . '` (`id`) ON DELETE CASCADE
        );';

        $this->db->query($q);
    }

    public function down()
    {
        $this->db->query('DROP TABLE `' . self::BUSINESS_REQ_TABLE . '`;');
        $this->db->query('DROP TABLE `' . self::BUSINESS_TABLE . '`;');
    }
}
