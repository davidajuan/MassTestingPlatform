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

use \EA\Engine\Types\Text;
use \EA\Engine\Types\NonEmptyText;
use \EA\Engine\Types\Url;
use \EA\Engine\Types\Email as EmailAddress;

/**
 * Email Notifications Class
 *
 * This library handles all the notification email deliveries on the system.
 *
 * Important: The email configuration settings are located at: /application/config/email.php
 */
class Email {
    /**
     * Framework Instance
     *
     * @var CI_Controller
     */
    protected $framework;

    /**
     * Contains email configuration.
     *
     * @var array
     */
    protected $config;

    /**
     * Class Constructor
     *
     * @param \CI_Controller $framework
     * @param array $config Contains the email configuration to be used.
     */
    public function __construct(\CI_Controller $framework, array $config, $ses_email)
    {
        $this->framework = $framework;
        $this->config = $config;
        $this->ses_email = $ses_email;
    }

    /**
     * Replace the email template variables.
     *
     * This method finds and replaces the html variables of an email template. It is used to
     * generate dynamic HTML emails that are send as notifications to the system users.
     *
     * @param array $replaceArray Array that contains the variables to be replaced.
     * @param string $templateHtml The email template HTML.
     *
     * @return string Returns the new email html that contain the variables of the $replaceArray.
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
     * Send an email with the appointment details.
     *
     * This email template also needs an email title and an email text in order to complete
     * the appointment details.
     *
     * @param array $appointment Contains the appointment data.
     * @param array $customer Contains the customer data.
     */
    public function sendAppointmentDetails($customer, $appointment, $ses_email_address, $ses_email_name, $blnUpdate = false) {

        if ($customer['patient_consent'] !== "1" || !$customer['email']) {
            return false;
        }

        $emailSubject = ($blnUpdate ? "Appointment Updated" : "Appointment Confirmation");
        $emailHeader = ($blnUpdate ? "Your appointment has been changed" : "Appointment Confirmation");

        // Prepare template replace array.
        $replaceArray = [
            '$first_name' => $customer['first_name'],
            '$last_name' => $customer['last_name'],
            '$datetime_pretty' => date('l jS, F Y h:i A', strtotime($appointment['start_datetime'])),
            '$emailSubject' => $emailSubject,
            '$emailHeader' => $emailHeader,
        ];

        $emailBodyHtml = file_get_contents(__DIR__ . '/../../application/views/emails/appointment_details.php');
        $emailBodyHtml = $this->_replaceTemplateVariables($replaceArray, $emailBodyHtml);

        $emailBodyText = file_get_contents(__DIR__ . '/../../application/views/emails/appointment_details_text.php');
        $emailBodyText = $this->_replaceTemplateVariables($replaceArray, $emailBodyText);

        try {
            $messageId = $this->ses_email->sendEmail(
                $ses_email_address,
                $ses_email_name,
                $customer['email'],
                $customer['first_name'],
                $emailSubject,
                $emailBodyHtml,
                $emailBodyText
            );
            $this->framework->logger->debug(sprintf('Confirmation email sent to patient: %s, messageId: %s', $customer['email'], $messageId));
            return $messageId;
        } catch (\Exception $e) {
            $this->framework->logger->error(sprintf('Failed to send Confirmation email to: %s, Exception: %s', $customer['email'], $e));
        }

        return false;
    }

    /**
     * Send an email to business.
     *
     * Tells them they have a business record created and it is currently pending
     *
     * @param array $business Contains the business data.
     * @param array $business_request Contains the business request data.
     */
    public function sendBusinessCreatedDetails($business, $business_request, $ses_email_address, $ses_email_name) {

        if ($business['consent_email'] !== "1" || !$business['email']) {
            return false;
        }

        $emailSubject = "Registration Confirmation";
        $emailHeader = "Covid Community Care Network";
        $intro = "Thank you for registering with the Covid Community Care Network";
        $message = "Your business is currently being verified for eligibility. Once we have verified your information, a City of Detroit Business Liaison or District Manager will reach out to you within 24-48 hours with instructions on how to help your employees register for testing appointments.";

        // Prepare template replace array.
        $replaceArray = [
            '$business_name' => $business['business_name'],
            '$first_name' => $business['owner_first_name'],
            '$last_name' => $business['owner_last_name'],
            '$slots_requested' => $business_request['slots_requested'],
            '$emailSubject' => $emailSubject,
            '$emailHeader' => $emailHeader,
            '$intro' => $intro,
            '$message' => $message,
        ];

        $emailBodyHtml = file_get_contents(__DIR__ . '/../../application/views/emails/business_created_details.php');
        $emailBodyHtml = $this->_replaceTemplateVariables($replaceArray, $emailBodyHtml);

        $emailBodyText = file_get_contents(__DIR__ . '/../../application/views/emails/business_created_details_text.php');
        $emailBodyText = $this->_replaceTemplateVariables($replaceArray, $emailBodyText);

        try {
            $messageId = $this->ses_email->sendEmail(
                $ses_email_address,
                $ses_email_name,
                $business['email'],
                $business['owner_first_name'],
                $emailSubject,
                $emailBodyHtml,
                $emailBodyText
            );
            $this->framework->logger->debug(sprintf('Confirmation email sent to business: %s, messageId: %s', $business['email'], $messageId));
            return $messageId;
        } catch (\Exception $e) {
            $this->framework->logger->error(sprintf('Failed to send Business email to: %s, Exception: %s', $business['email'], $e));
        }

        return false;
    }

    /**
     * Send an email to business.
     *
     * Tells them their business is now active in our system
     *
     * @param array $business Contains the business data.
     * @param array $business_request Contains the business request data.
     */
    public function sendBusinessActiveDetails($business, $ses_email_address, $ses_email_name) {

        if ($business['consent_email'] !== "1" || !$business['email']) {
            return false;
        }

        $emailSubject = "Your Business Has Been Approved";
        $emailHeader = "Covid Community Care Network";

        // Prepare template replace array.
        $replaceArray = [
            '$slots_approved' => $business['slots_approved'],
            '$business_code' => $business['business_code'],
            '$emailSubject' => $emailSubject,
            '$emailHeader' => $emailHeader,
            '$phone' => "(313) 230-0505"
        ];

        $emailBodyHtml = file_get_contents(__DIR__ . '/../../application/views/emails/business_approved_details.php');
        $emailBodyHtml = $this->_replaceTemplateVariables($replaceArray, $emailBodyHtml);

        $emailBodyText = file_get_contents(__DIR__ . '/../../application/views/emails/business_approved_details_text.php');
        $emailBodyText = $this->_replaceTemplateVariables($replaceArray, $emailBodyText);

        try {
            $messageId = $this->ses_email->sendEmail(
                $ses_email_address,
                $ses_email_name,
                $business['email'],
                $business['owner_first_name'],
                $emailSubject,
                $emailBodyHtml,
                $emailBodyText
            );
            $this->framework->logger->debug(sprintf('Confirmation email sent to business: %s, messageId: %s', $business['email'], $messageId));

            return $messageId;
        } catch (\Exception $e) {
            $this->framework->logger->error(sprintf('Failed to send Business email to: %s, Exception: %s', $business['email'], $e));
        }

        return false;
    }

    /**
     * Send an email notification to both provider and customer on appointment removal.
     *
     * Whenever an appointment is cancelled or removed, both the provider and customer
     * need to be informed. This method sends the same email twice.
     *
     * <strong>IMPORTANT!</strong> This method's arguments should be taken
     * from database before the appointment record is deleted.
     *
     * @param array $appointment The record data of the removed appointment.
     * @param array $provider The record data of the appointment provider.
     * @param array $service The record data of the appointment service.
     * @param array $customer The record data of the appointment customer.
     * @param array $company Some settings that are required for this function. By now this array must contain
     * the following values: "company_link", "company_name", "company_email".
     * @param \EA\Engine\Types\Email $recipientEmail The email address of the email recipient.
     * @param \EA\Engine\Types\Text $reason The reason why the appointment is deleted.
     */
    public function sendDeleteAppointment(
        array $appointment,
        array $provider,
        array $service,
        array $customer,
        array $company,
        EmailAddress $recipientEmail,
        Text $reason
    ) {
        switch ($company['date_format'])
        {
            case 'DMY':
                $date_format = 'd/m/Y';
                break;
            case 'MDY':
                $date_format = 'm/d/Y';
                break;
            case 'YMD':
                $date_format = 'Y/m/d';
                break;
            default:
                throw new \Exception('Invalid date_format value: ' . $company['date_format']);
        }

        switch ($company['time_format'])
        {
            case 'military':
                $timeFormat = 'H:i';
                break;
            case 'regular':
                $timeFormat = 'g:i A';
                break;
            default:
                throw new \Exception('Invalid time_format value: ' . $company['time_format']);
        }

        // Prepare email template data.
        $replaceArray = [
            '$email_title' => $this->framework->lang->line('appointment_cancelled_title'),
            '$email_message' => $this->framework->lang->line('appointment_removed_from_schedule'),
            '$appointment_service' => $service['name'],
            '$appointment_provider' => $provider['first_name'] . ' ' . $provider['last_name'],
            '$appointment_date' => date($date_format . ' ' . $timeFormat, strtotime($appointment['start_datetime'])),
            '$appointment_duration' => $service['duration'] . ' ' . $this->framework->lang->line('minutes'),
            '$company_link' => $company['company_link'],
            '$company_name' => $company['company_name'],
            '$customer_name' => $customer['first_name'] . ' ' . $customer['last_name'],
            '$customer_email' => $customer['email'],
            '$customer_phone' => $customer['phone_number'],
            '$customer_address' => $customer['address'],
            '$reason' => $reason->get(),

            // Translations
            'Appointment Details' => $this->framework->lang->line('appointment_details_title'),
            'Service' => $this->framework->lang->line('service'),
            'Provider' => $this->framework->lang->line('provider'),
            'Date' => $this->framework->lang->line('start'),
            'Duration' => $this->framework->lang->line('duration'),
            'Customer Details' => $this->framework->lang->line('customer_details_title'),
            'Name' => $this->framework->lang->line('name'),
            'Email' => $this->framework->lang->line('email'),
            'Phone' => $this->framework->lang->line('phone'),
            'Address' => $this->framework->lang->line('address'),
            'Reason' => $this->framework->lang->line('reason')
        ];

        $html = file_get_contents(__DIR__ . '/../../application/views/emails/delete_appointment.php');
        $html = $this->_replaceTemplateVariables($replaceArray, $html);

        $mailer = $this->_createMailer();

        // Send email to recipient.
        $mailer->From = $company['company_email'];
        $mailer->FromName = $company['company_name'];
        $mailer->AddAddress($recipientEmail->get()); // "Name" argument crushes the phpmailer class.
        $mailer->Subject = $this->framework->lang->line('appointment_cancelled_title');
        $mailer->Body = $html;

        if ( ! $mailer->Send())
        {
            throw new \RuntimeException('Email could not been sent. Mailer Error (Line ' . __LINE__ . '): '
                . $mailer->ErrorInfo);
        }
    }

    /**
     * This method sends an email with the new password of a user.
     *
     * @param \EA\Engine\Types\NonEmptyText $password Contains the new password.
     * @param \EA\Engine\Types\Email $recipientEmail The receiver's email address.
     * @param array $company The company settings to be included in the email.
     */
    public function sendPassword(NonEmptyText $password, EmailAddress $recipientEmail, array $company)
    {
        $replaceArray = [
            '$email_title' => $this->framework->lang->line('new_account_password'),
            '$email_message' => $this->framework->lang->line('new_password_is'),
            '$company_name' => $company['company_name'],
            '$company_email' => $company['company_email'],
            '$company_link' => $company['company_link'],
            '$password' => '<strong>' . $password->get() . '</strong>'
        ];

        $html = file_get_contents(__DIR__ . '/../../application/views/emails/new_password.php');
        $html = $this->_replaceTemplateVariables($replaceArray, $html);

        $mailer = $this->_createMailer();

        $mailer->From = $company['company_email'];
        $mailer->FromName = $company['company_name'];
        $mailer->AddAddress($recipientEmail->get()); // "Name" argument crushes the phpmailer class.
        $mailer->Subject = $this->framework->lang->line('new_account_password');
        $mailer->Body = $html;

        if ( ! $mailer->Send())
        {
            throw new \RuntimeException('Email could not been sent. Mailer Error (Line ' . __LINE__ . '): '
                . $mailer->ErrorInfo);
        }
    }

    /**
     * Create PHP Mailer Instance
     *
     * @return \PHPMailer
     */
    protected function _createMailer()
    {
        $mailer = new \PHPMailer;

        if ($this->config['protocol'] === 'smtp')
        {
            $mailer->isSMTP();
            $mailer->Host = $this->config['smtp_host'];
            $mailer->SMTPAuth = TRUE;
            $mailer->Username = $this->config['smtp_user'];
            $mailer->Password = $this->config['smtp_pass'];
            $mailer->SMTPSecure = $this->config['smtp_crypto'];
            $mailer->Port = $this->config['smtp_port'];
        }

        $mailer->IsHTML($this->config['mailtype'] === 'html');
        $mailer->CharSet = $this->config['charset'];

        return $mailer;
    }
}
