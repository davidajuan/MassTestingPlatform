<?php
// Having this blocks unit testing!
// TODO: Can this file be hit from the public?
// defined('BASEPATH') OR exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * Easy!Appointments - Open Source Web Scheduler
 *
 * @package     EasyAppointments
 * @author      A.Tselegidis <alextselegidis@gmail.com>
 * @copyright   Copyright (c) 2013 - 2018, Alex Tselegidis
 * @license     http://opensource.org/licenses/GPL-3.0 - GPLv3
 * @link        http://easyappointments.org
 * @since       v1.0.0
 * ---------------------------------------------------------------------------- */

/**
 * Formats a patient id for easy display: YYMM-DDXX-XXXX
 * If an improper id is given, it is returned as-is
 *
 * @param string $id patient id
 * @return string Returns formatted patient id
 */
function format_patient_id($id)
{
    $formattedId = $id;

    // Get grouped nums
    preg_match('/^(\d{4})(\d{4})(\d{4})$/i', $id, $matches);

    // See if we found a valid id
    if ($matches) {
        $formattedId = sprintf('%s-%s-%s', $matches[1], $matches[2], $matches[3]);
    }

    return $formattedId;
}

/**
 * Remove any invalid characters from id to check against or insert into the database
 *
 * @param string $id patient id
 * @return string Returns clean patient id
 */
function sanitize_patient_id($id)
{
    return preg_replace('/\D/', '', $id);
}
