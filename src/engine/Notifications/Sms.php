<?php

/* ----------------------------------------------------------------------------
 * Easy!Appointments - Open Source Web Scheduler
 *
 * @package     EasyAppointments
 * @author      A.Tselegidis <alextselegidis@gmail.com>
 * @copyright   Copyright (c) 2013 - 2018, Alex Tselegidis
 * @license     http://opensource.org/licenses/GPL-3.0 - GPLv3
 * @link        http://easyappointments.org
 * @since       v1.2.0
 * ---------------------------------------------------------------------------- */

namespace EA\Engine\Notifications;

use Exception;

/**
 * Sms Notifications Class
 *
 * This library handles all the notification sms deliveries on the system.
 */
class Sms {
    /**
     * Framework Instance
     *
     * @var CI_Controller
     */
    protected $framework;

    /**
     * Contains sms configuration.
     *
     * @var array
     */
    protected $config;


    /**
     * SMS service
     *
     * @var object
     */
    protected $service;

    /**
     * Class Constructor
     *
     * @param \CI_Controller $framework
     * @param array $config Contains the sms configuration to be used.
     */
    public function __construct(\CI_Controller $framework, array $config, object $service)
    {
        $this->framework = $framework;
        $this->config = $config;
        $this->service = $service;
    }

    /**
     * Replace the sms template variables.
     *
     * This method finds and replaces the html variables of an sms template. It is used to
     * generate dynamic HTML sms that are send as notifications to the system users.
     *
     * @param array $replaceArray Array that contains the variables to be replaced.
     * @param string $templateHtml The sms template HTML.
     *
     * @return string Returns the new sms html that contain the variables of the $replaceArray.
     */
    protected function _replaceTemplateVariables(array $replaceArray, $templateHtml)
    {
        foreach ($replaceArray as $name => $value)
        {
            $templateHtml = str_replace($name, $value, $templateHtml);
        }

        return $templateHtml;
    }

    /**
     * Send an sms with the appointment details.
     *
     * @param array $appointment Contains the appointment data.
     * @param array $customer Contains the customer data.
     */
    public function sendAppointmentDetails($customer, $appointment, $blnUpdate = false) {
        if ($customer['patient_consent_sms'] !== '1') {
            return false;
        }
        $phoneNumber = $customer['mobile_number'] ?? null;

        $changedCopy = ($blnUpdate ? "Your appointment has been changed. " : "");

        $replaceArray = [
            '$first_name' => $customer['first_name'],
            '$datetime_pretty' => date('l jS, F Y h:i A', strtotime($appointment['start_datetime'])),
            '$changedCopy' => $changedCopy,
        ];

        $message = file_get_contents(__DIR__ . '/../../application/views/sms/appointment_details.php');
        $message = $this->_replaceTemplateVariables($replaceArray, $message);
        $message = trim($message);

        try {
            $messageId = $this->service->sendSms(
                $this->service->formatPhone($phoneNumber),
                $message
            );
            $this->framework->logger->debug(sprintf('Confirmation sms sent to patient: %s, messageId: %s', $phoneNumber, $messageId));
            return $messageId;
        } catch (Exception $e) {
            $this->framework->logger->error(sprintf('Failed to send Confirmation sms to: %s, Exception: %s', $phoneNumber, $e));
        }

        return false;
    }

    /**
     * Send an sms with the business details.
     *
     * @param array $business Contains the business data.
     * @param array $business_request Contains the business request data.
     */
    public function sendBusinessCreatedDetails($business)
    {
        if ($business['consent_sms'] !== '1' || !$business['mobile_phone']) {
            return false;
        }
        $phoneNumber = $business['mobile_phone'] ?? null;

        $message = file_get_contents(__DIR__ . '/../../application/views/sms/business_created_details.php');

        try {
            $messageId = $this->service->sendSms(
                $this->service->formatPhone($phoneNumber),
                $message
            );
            $this->framework->logger->debug(sprintf('Confirmation sms sent to business: %s, messageId: %s', $phoneNumber, $messageId));
            return $messageId;
        } catch (Exception $e) {
            $this->framework->logger->error(sprintf('Failed to send Business sms to: %s, Exception: %s', $phoneNumber, $e));
        }

        return false;
    }

        /**
     * Send an sms with the business details.
     *
     * @param array $business Contains the business data.
     * @param array $business_request Contains the business request data.
     */
    public function sendBusinessActiveDetails($business)
    {
        if ($business['consent_sms'] !== '1' || !$business['mobile_phone']) {
            return false;
        }
        $phoneNumber = $business['mobile_phone'] ?? null;
        // Prepare template replace array.
        $replaceArray = [
            '$slots_approved' => $business['slots_approved'],
            '$business_code' => $business['business_code'],
            '$phone' => "(313) 230-0505",
        ];

        $message = file_get_contents(__DIR__ . '/../../application/views/sms/business_approved_details.php');
        $message = $this->_replaceTemplateVariables($replaceArray, $message);

        try {
            $messageId = $this->service->sendSms(
                $this->service->formatPhone($phoneNumber),
                $message
            );
            $this->framework->logger->debug(sprintf('Confirmation sms sent to business: %s, messageId: %s', $phoneNumber, $messageId));
            return $messageId;
        } catch (Exception $e) {
            $this->framework->logger->error(sprintf('Failed to send Business sms to: %s, Exception: %s', $phoneNumber, $e));
        }

        return false;
    }

    /**
     * Send an sms notification to both provider and customer on appointment removal.
     *
     * Whenever an appointment is cancelled or removed, both the provider and customer
     * need to be informed. This method sends the same sms twice.
     *
     * <strong>IMPORTANT!</strong> This method's arguments should be taken
     * from database before the appointment record is deleted.
     */
    public function sendDeleteAppointment() {
        throw new Exception('Not implemented');
    }
}
