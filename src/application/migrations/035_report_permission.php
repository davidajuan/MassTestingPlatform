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

class Migration_Report_permission extends CI_Migration {
    const ROLES_TABLE = "ea_roles";

    const FIELD_EA_ROLES_ADD_LIST = [
        self::ROLES_TABLE => [
            PRIV_REPORTS => ' INT(11) DEFAULT 0 AFTER `business_request`',
        ],
    ];

    public function up()
    {
        // Add Columns
        $this->addFields(self::FIELD_EA_ROLES_ADD_LIST);

        $rolesWithPermission = [DB_SLUG_ADMIN, DB_SLUG_PROVIDER, DB_SLUG_SECRETARY, DB_SLUG_CITY_ADMIN];
        $permission = PRIV_VIEW + PRIV_ADD + PRIV_EDIT + PRIV_DELETE;
        $this->db
            ->where_in('slug', $rolesWithPermission)
            ->update(self::ROLES_TABLE, [PRIV_REPORTS => $permission]);
    }

    public function down()
    {
        // Remove Columns
        $this->removeFields(self::FIELD_EA_ROLES_ADD_LIST);
    }
}
