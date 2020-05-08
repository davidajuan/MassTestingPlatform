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

/**
 * Check whether current user is logged in and has the required privileges to view a page.
 *
 * The backend page requires different privileges from the users to display pages. Not all pages are available to
 * all users. For example secretaries should not be able to edit the system users.
 *
 * @param string $privilegeName This argument must match the roles field names of each section (eg "appointments", "users"
 * @param boolean $redirect If the user has not the required privileges (either not logged in or insufficient role
 * privileges) then the user will be redirected to another page. Set this argument to FALSE when using ajax (default
 * true).
 * @param string $redirectUrl Url to redirect to if use does not have the permission
 * @return boolean
 */
function _has_privileges(string $privilegeName, bool $redirect = true, string $redirectUrl = null): bool
{
    $ci =& get_instance();
    $ci->load->library('session');

    // Check if user is logged in.
    $user_id = $ci->session->userdata('user_id');
    if ($user_id == FALSE) { // User not logged in, display the login view.
        if ($redirect) {
            $redirectUrl = $redirectUrl ?? site_url('user/login');
            header('Location: ' . $redirectUrl);
        }
        return FALSE;
    }

    // Check if the user has the required privileges for viewing the selected page.
    $role_slug = $ci->session->userdata('role_slug');
    $role_priv = $ci->db->get_where('ea_roles', ['slug' => $role_slug])->row_array();
    if (!isset($role_priv[$privilegeName]) || $role_priv[$privilegeName] < PRIV_VIEW) { // User does not have the permission to view the page. take them to customers pages
        if ($redirect) {
            // all users have access to this page.. need a better solution to send them to a page access by all
            $redirectUrl = $redirectUrl ?? site_url('backend/business_request');
            header('Location: ' . $redirectUrl);
        }
        return FALSE;
    }

    return TRUE;
}
