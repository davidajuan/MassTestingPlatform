<?php defined('BASEPATH') OR exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * Easy!Appointments - Open Source Web Scheduler
 *
 * @package     EasyAppointments
 * @author      A.Tselegidis <alextselegidis@gmail.com>
 * @copyright   Copyright (c) 2013 - 2018, Alex Tselegidis
 * @license     http://opensource.org/licenses/GPL-3.0 - GPLv3
 * @link        http://easyappointments.org
 * @since       v1.3.0
 * ---------------------------------------------------------------------------- */

class Migration_Auto_increment extends CI_Migration {
    public function up()
    {
        $fields = [
            'id' => [
                'name' => 'id',
                'type' => 'int',
                'constraint' => '11',
                'auto_increment' => TRUE
            ]
        ];

        $this->dbforge->modify_column('ea_service_capacity', $fields);
    }

    public function down()
    {
        $fields = [
            'id' => [
                'name' => 'id',
                'type' => 'int',
                'constraint' => '11'
            ]
        ];

        $this->dbforge->modify_column('ea_service_capacity', $fields);
    }
}
