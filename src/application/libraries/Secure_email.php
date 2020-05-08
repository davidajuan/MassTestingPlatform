<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;

class Secure_email {

    protected $smtpHost;
    protected $smtpPort;
    protected $smtpUsername;
    protected $smtpPassword;

    protected $debugLevel;
    protected $debugLogger;

    /**
     * Constructor needs these params:
     *  host
     *  port
     *  username
     *  password
     *
     * Consider doing the following to connect to less secure SMTP servers:
     *
     * sed -re 's/@SECLEVEL=[2-9]//' /etc/ssl/openssl.cnf >~/openssl_nmap.cnf
     * export OPENSSL_CONF=~/openssl_nmap.cnf
     *
     *  debugLevel: (int)
     *      SMTP::DEBUG_OFF (`0`) No debug output, default
     *      SMTP::DEBUG_CLIENT (`1`) Client commands
     *      SMTP::DEBUG_SERVER (`2`) Client commands and server responses
     *      SMTP::DEBUG_CONNECTION (`3`) As DEBUG_SERVER plus connection status
     *      SMTP::DEBUG_LOWLEVEL (`4`) Low-level data output, all messages.
     *
     * Source: https://codeigniter.com/user_guide/general/creating_libraries.html#passing-parameters-when-initializing-your-class
     * @throws Exception Missing required params
     */
    public function __construct(?array $params = []) {
        // Check for required fields
        if (empty($params['host'])
                || empty($params['port'])
                || empty($params['username'])
                || empty($params['password'])) {
            throw new Exception('Missing required params: host, port, username, password');
        }

        $this->smtpHost = $params['host'];
        $this->smtpPort = intval($params['port']);
        $this->smtpUsername = $params['username'];
        $this->smtpPassword = $params['password'];

        if ($this->smtpPort === 0) {
            throw new Exception('Port is invalid');
        }

        // Add debug info
        $this->debugLevel = intval($params['debugLevel'] ?? SMTP::DEBUG_OFF);
        $this->debugLogger = $params['debugLogger'] ?? 'echo';
    }

	/**
	 * Initialize
	 *
	 * @param	array	$params	Configuration parameters
	 * @return	Secure_email
	 */
    /*
	public function initialize(array $params)
	{
        // TODO: Is this another way to init an instance of this library class?
		return $this;
    }
    */

    /**
     *
     * @return bool MessageId if the message was sent to AWS
     * @throws Exception Generic Email Failure
     */
    public function sendEmail(string $fromEmail, string $fromName, string $toEmail, string $toName, string $subject, ?string $bodyHtml, ?string $bodyText, ?string $attachment = null) {
        // Create a new PHPMailer object.
        $mail = new PHPMailer();

        //Enable SMTP debugging
        // SMTP::DEBUG_OFF = off (for production use)
        // SMTP::DEBUG_CLIENT = client messages
        // SMTP::DEBUG_SERVER = client and server messages
        // SMTP::DEBUG_CONNECTION = As DEBUG_SERVER plus connection status
        // SMTP::DEBUG_LOWLEVEL = Low-level data output, all messages.
        $mail->SMTPDebug = $this->debugLevel;
        $mail->Debugoutput = function($str, $level) {
            // Send debugging to PSR-3 logger
            $this->debugLogger->debug($str);
        };

        //Tell PHPMailer to use SMTP
        $mail->isSMTP();
        //Set the hostname of the mail server
        $mail->Host = $this->smtpHost;
        //Set the SMTP port number - 587 for authenticated TLS, a.k.a. RFC4409 SMTP submission
        $mail->Port = $this->smtpPort;
        //Set the encryption mechanism to use - STARTTLS or SMTPS
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;

        //Whether to use SMTP authentication
        $mail->SMTPAuth = true;
        //Username to use for SMTP authentication - use full email address for gmail
        $mail->Username = $this->smtpUsername;
        //Password to use for SMTP authentication
        $mail->Password = $this->smtpPassword;

        // Add components to the email.
        $mail->setFrom($fromEmail, $fromName);
        $mail->addAddress($toEmail, $toName);
        $mail->Subject = $subject;
        $mail->Body = $bodyHtml ?? '';
        $mail->AltBody = $bodyText ?? '';

        // Attachments
        if (!empty($attachment)) {
            if (!file_exists($attachment)) {
                throw new Exception ('Attachment does not exist: '.$attachment);
            }
            $mail->addAttachment($attachment);
        }

        // Prep the email for sending
        if (!$mail->preSend()) {
            throw new Exception('Email could not be prepared: '.$mail->ErrorInfo);
        }

        // Setup callback to get a transaction ID
        // Source: https://github.com/PHPMailer/PHPMailer/blob/master/examples/callback.phps
        $mail->action_function = static function ($result, $to, $cc, $bcc, $subject, $body, $from, $extra) {
            if ($result) {
                // Success let's mark transaction id here
                // FIXME: These loggers throw errors, Exception: Using $this when not in object context /var/mass-testing-platform/src/application/libraries/Secure_email.php 134
                // $this->logger->info(sprintf('Email success with transaction id: %s', $extra['smtp_transaction_id']));
            } else {
                // $this->logger->warning(sprintf('Email failed with transaction id: %s', $extra['smtp_transaction_id']));
            }
        };

        // Send the message, callback handles success/fail messaging
        if (!$mail->send()) {
            // Failure
            throw new Exception(sprintf('Email had a sending error: %s', $mail->ErrorInfo));
        }

        // Success
        return true;
    }
}
