<?php
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
 * Easy!Appointments Configuration File
 *
 * Set your installation BASE_URL * without the trailing slash * and the database
 * credentials in order to connect to the database. You can enable the DEBUG_MODE
 * while developing the application.
 *
 * Set the default language by changing the LANGUAGE constant. For a full list of
 * available languages look at the /application/config/config.php file.
 *
 * IMPORTANT:
 * If you are updating from version 1.0 you will have to create a new "config.php"
 * file because the old "configuration.php" is not used anymore.
 */
class Config
{

    // ------------------------------------------------------------------------
    // GENERAL SETTINGS
    // ------------------------------------------------------------------------

    const BASE_URL      = 'http://url-to-easyappointments-directory';
    const LANGUAGE      = 'english';
    const APP_ENV       = 'prod';
    const DEBUG_MODE    = false;
    const DATA_DIR      = FCPATH . '../data/';

    // ------------------------------------------------------------------------
    // DATABASE SETTINGS
    // ------------------------------------------------------------------------

    const DB_HOST       = '';
    const DB_NAME       = '';
    const DB_USERNAME   = '';
    const DB_PASSWORD   = '';


    // ------------------------------------------------------------------------
    // APP CONFIG
    // ------------------------------------------------------------------------

    const APP_CONFIG   = '';

    // ------------------------------------------------------------------------
    // SFTP
    // ------------------------------------------------------------------------

    const PRINT_SFTP_HOST       = '';
    const PRINT_SFTP_USERNAME   = '';
    const PRINT_SFTP_PASSWORD   = '';

    // ------------------------------------------------------------------------
    // GOOGLE CALENDAR SYNC
    // ------------------------------------------------------------------------

    const GOOGLE_SYNC_FEATURE   = FALSE; // Enter TRUE or FALSE
    const GOOGLE_PRODUCT_NAME   = '';
    const GOOGLE_CLIENT_ID      = '';
    const GOOGLE_CLIENT_SECRET  = '';
    const GOOGLE_API_KEY        = '';

    // ------------------------------------------------------------------------
    // SES
    // ------------------------------------------------------------------------

    const SES_EMAIL_ADDRESS     = '';
    const SES_EMAIL_NAME        = '';

    const HEALTH_NETWORK_USER_NAME       = '';
    const HEALTH_NETWORK_USER_PWD        = '';
    const HEALTH_NETWORK_MAIL_FROM       = '';
    const HEALTH_NETWORK_MAIL_TO         = '';
    const HEALTH_NETWORK_SMTP_URL        = '';
    const HEALTH_NETWORK_SMTP_PORT       = '';

    // ------------------------------------------------------------------------
    // File Names
    // ------------------------------------------------------------------------

    const FILENAME_PATIENT_APPOINTMENTS = 'patient_appointments.csv';
    const FILENAME_PATIENT_PRINTS = 'patients_form_print.csv';
    const FILENAME_PATIENT_MASTER = 'patient_appointments_master.csv';
    const FILENAME_BUSINESS_MASTER = 'business_master.csv';

    const PRINT_SFTP_STARTING_LOCATION = 'dropbox';

    // ------------------------------------------------------------------------
    // NEVERBOUNCE
    // ------------------------------------------------------------------------

    const NEVERBOUNCE_API_KEY   = '';

    // ------------------------------------------------------------------------
    // Google Maps API
    // ------------------------------------------------------------------------

    const GOOGLE_MAPS_API_KEY   = '';


    // ------------------------------------------------------------------------
    // Webhook metric url
    // ------------------------------------------------------------------------

    const METRICS_WEBHOOK_URL   = '';

    // ------------------------------------------------------------------------
    // Misc
    // ------------------------------------------------------------------------

    const GIT_HASH = '';

    const BUSINESS_CODE_CITY_WORKER = '123456DT';
}

/* End of file config.php */
/* Location: ./config.php */
