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

class Migration_Service_ids extends CI_Migration {
    const FIELD_EA_USERS_ADD_LIST = [
        'ea_user_settings' => [
            'business_service_id' => 'INT(11) DEFAULT NULL',
            'priority_service_id' => 'INT(11) DEFAULT NULL',
        ],
    ];

    public function up()
    {
        // get provider
        $select = 'select us.id_users FROM ea_user_settings us LEFT JOIN ea_users u ON u.id = us.id_users LEFT JOIN ea_roles r ON r.id = u.id_roles WHERE r.slug = ?;';
        $provider = $this->db->query($select, [DB_SLUG_PROVIDER])->row_array();

        // Add Columns
        $this->addFields(self::FIELD_EA_USERS_ADD_LIST);

        // Add Business Service and update id
        $this->db->insert('ea_services',
            [
                'name' => 'Business Covid-19',
                'duration' => 60,
                'availabilities_type' => 'flexible',
                'attendants_number' => 30
            ]
        );
        $business_service_id = (int)$this->db->insert_id();

        // update provider with business service id
        $update = 'UPDATE ea_user_settings us LEFT JOIN ea_users u ON u.id = us.id_users LEFT JOIN ea_roles r ON r.id = u.id_roles SET us.business_service_id = ? WHERE r.slug = ?;';
        $this->db->query($update, [$business_service_id, DB_SLUG_PROVIDER]);

        // associate new service id to provider
        $this->db->insert('ea_services_providers',
        [
            'id_users' => $provider['id_users'],
            'id_services' => $business_service_id,
        ]);

        // Add Priority Service and update id
        $this->db->insert('ea_services',
            [
                'name' => 'Priority Critical Infrastructure Testing',
                'duration' => 60,
                'availabilities_type' => 'flexible',
                'attendants_number' => 30
            ]
        );
        $priority_service_id = (int)$this->db->insert_id();

        // update provider with business service id
        $update = 'UPDATE ea_user_settings us LEFT JOIN ea_users u ON u.id = us.id_users LEFT JOIN ea_roles r ON r.id = u.id_roles SET us.priority_service_id = ? WHERE r.slug = ?;';
        $this->db->query($update, [$priority_service_id, DB_SLUG_PROVIDER]);

        $this->db->insert('ea_services_providers',
        [
            'id_users' => $provider['id_users'],
            'id_services' => $priority_service_id,
        ]);
    }

    public function down()
    {
        // Remove Columns
        $this->removeFields(self::FIELD_EA_USERS_ADD_LIST);
    }
}
