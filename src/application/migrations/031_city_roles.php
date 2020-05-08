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

class Migration_City_roles extends CI_Migration {
    const CITY_ADMIN_PROVIDERS_TABLE = 'ea_city_admin_providers';
    const CITY_BUSINESS_PROVIDERS_TABLE = 'ea_city_business_providers';

    const FIELD_EA_ROLES_ADD_LIST = [
        'ea_roles' => [
            PRIV_BUSINESS => ' INT(11) DEFAULT 0 AFTER `users`',
            PRIV_BUSINESS_REQUEST => ' INT(11) DEFAULT 0 AFTER `business`',
        ],
    ];

    public function up()
    {
        // Add business and business_request fields to ea_roles table
        $this->addFields(self::FIELD_EA_ROLES_ADD_LIST);

        // Add new records in ea_roles table for city_admin and city_business
        $insert = 'INSERT INTO
            `ea_roles` (`id`, `name`, `slug`, `is_admin`, `appointments`, `customers`, `services`, `users`, `' . PRIV_BUSINESS . '`, `' . PRIV_BUSINESS_REQUEST . '`, `system_settings`, `user_settings`)
        VALUES
            (5, \'City Admin\', \'' . DB_SLUG_CITY_ADMIN . '\', 0, 15, 15, 0, 0, 15, 15, 0, 0),
            (6, \'City Business\', \'' . DB_SLUG_CITY_BUSINESS . '\', 0, 0, 0, 0, 0, 0, 15, 0, 0);';
        $this->db->query($insert);

        // Give admin access to business and business_request
        $update = 'UPDATE `ea_roles` SET `' . PRIV_BUSINESS . '` = 15, `' . PRIV_BUSINESS_REQUEST . '` = 15 WHERE id = 1;';
        $this->db->query($update);

        // Give provider access to business
        $update = 'UPDATE `ea_roles` SET `' . PRIV_BUSINESS . '` = 15, `' . PRIV_BUSINESS_REQUEST . '` = 0 WHERE id = 2;';
        $this->db->query($update);

        // Create table ea_city_admin_providers (same as ea_secretaries_providers)
        $q = 'CREATE TABLE IF NOT EXISTS `' . self::CITY_ADMIN_PROVIDERS_TABLE . '` (
            `id_users_city_admin` INT(11) NOT NULL,
            `id_users_provider` INT(11) NOT NULL,
            FOREIGN KEY (`id_users_city_admin`) REFERENCES `ea_users` (`id`) ON DELETE CASCADE,
            FOREIGN KEY (`id_users_provider`) REFERENCES `ea_users` (`id`) ON DELETE CASCADE
        );';

        $this->db->query($q);

        // Create Table ea_city_business_providers (same as ea_secretaries_providers)
        $q = 'CREATE TABLE IF NOT EXISTS `' . self::CITY_BUSINESS_PROVIDERS_TABLE . '` (
            `id_users_city_business` INT(11) NOT NULL,
            `id_users_provider` INT(11) NOT NULL,
            FOREIGN KEY (`id_users_city_business`) REFERENCES `ea_users` (`id`) ON DELETE CASCADE,
            FOREIGN KEY (`id_users_provider`) REFERENCES `ea_users` (`id`) ON DELETE CASCADE
        );';

        $this->db->query($q);
    }

    public function down()
    {
        // Remove business and business_request fields to ea_roles table
        $this->removeFields(self::FIELD_EA_ROLES_ADD_LIST);

        // Remove new records in ea_roles table for city_admin and city_business
        $delete = 'DELETE FROM `ea_roles` WHERE id IN (5, 6)';
        $this->db->query($delete);

        // Delete tables
        $this->db->query('DROP TABLE `' . self::CITY_ADMIN_PROVIDERS_TABLE . '`;');
        $this->db->query('DROP TABLE `' . self::CITY_BUSINESS_PROVIDERS_TABLE . '`;');
    }
}
