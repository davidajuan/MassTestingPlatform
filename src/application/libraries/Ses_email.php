<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');

use Aws\Exception\AwsException;
use Aws\Ses\SesClient;

class Ses_email {

    protected $client;

    /**
     * https://docs.aws.amazon.com/ses/latest/DeveloperGuide/examples-send-raw-using-sdk.html
     *
     * You use a shared credentials file to pass your AWS access key ID and secret access key.
     * As an alternative to using a shared credentials file, you can specify your AWS access key
     * ID and secret access key by setting two environment variables (AWS_ACCESS_KEY_ID and AWS_SECRET_ACCESS_KEY,
     * respectively). This example doesn't function unless you specify your credentials using one of these methods.
     *
     * AWS_ACCESS_KEY_ID
     * AWS_SECRET_ACCESS_KEY
     * AWS_SESSION_TOKEN
     *
     */
    public function __construct() {
        $this->client = new SesClient([
            'version' => 'latest',
            'region'  => 'us-east-1',
            'http' => [ 'verify' => '/etc/ssl/certs/ca-certificates.crt' ],
        ]);
    }

    /**
     * https://docs.aws.amazon.com/ses/latest/DeveloperGuide/examples-send-raw-using-sdk.html
     *
     * You use a shared credentials file to pass your AWS access key ID and secret access key.
     * As an alternative to using a shared credentials file, you can specify your AWS access key
     * ID and secret access key by setting two environment variables (AWS_ACCESS_KEY_ID and AWS_SECRET_ACCESS_KEY,
     * respectively). This example doesn't function unless you specify your credentials using one of these methods.
     *
     * AWS_ACCESS_KEY_ID
     * AWS_SECRET_ACCESS_KEY
     * AWS_SESSION_TOKEN
     *
     * @return string MessageId if the message was sent to AWS
     * @throws Exception Generic Error
     * @throws Exception Missing Attachment
     * @throws Exception Failed to prepare email
     * @throws AwsException
     */
    public function sendEmail(string $fromEmail, string $fromName, string $toEmail, string $toName, string $subject, ?string $bodyHtml, ?string $bodyText, ?string $attachment = null) {
        // Create a new PHPMailer object.
        // Add components to the email.
        $mail = new PHPMailer();
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

        // Create a new variable that contains the MIME message.
        $message = $mail->getSentMIMEMessage();

        // Send the message
        $result = $this->client->sendRawEmail([
            'RawMessage' => [
                'Data' => $message
            ]
        ]);

        // If the message was sent, show the message ID.
        $messageId = $result->get('MessageId');
        if (!$messageId) {
            throw new Exception('Email send did not return a proper MessageId');
        }
        return $messageId;
    }
}
