<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');

use Aws\Exception\AwsException;
use Aws\Sns\SnsClient;
use libphonenumber\PhoneNumberUtil;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\NumberParseException;

class Sns_sms {

    protected $client;

    /**
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
        $this->client = new SnsClient([
            'version' => 'latest',
            'region'  => 'us-east-1',
            'http' => [ 'verify' => '/etc/ssl/certs/ca-certificates.crt' ],
        ]);
    }

    /**
     * Send SMS
     * https://docs.aws.amazon.com/sdk-for-php/v3/developer-guide/sns-examples-sending-sms.html
     * https://docs.aws.amazon.com/sns/latest/api/sns-api.pdf#API_Publish
     *
     * @param string $phoneNumber Phone number in E.164 format
     * @param string $message String message
     * @return string MessageId if the message was sent to AWS
     * @throws Exception Generic Error
     * @throws AwsException
     */
    public function sendSms(string $phoneNumber, string $message) {
        // What happens if user is opted out? Will AWS still accept message attempt to send? False positive?
        // Tested: AWS will accept message as normal, return a successful messageId, but not send the SMS at all.
        // List of opted out users are listed in AWS
        // Users can OPT OUT by doing the following: https://docs.aws.amazon.com/sns/latest/dg/sms_manage.html#sms_manage_optout
        //
        // We can check the opt out status by doing, not sure if needed right now atm.
        // $result = $this->client->checkIfPhoneNumberIsOptedOut([
        //     'phoneNumber' => $phoneNumber,
        // ]);
        // var_dump($result);

        // Send SMS
        $result = $this->client->publish([
            'Message' => $message,
            'PhoneNumber' => $phoneNumber,
            'MessageAttributes' => [
                'DefaultSenderID' => [
                    'DataType' => 'String',
                    'StringValue' => 'COVIDDet',
                ],
                'DefaultSMSType' => [
                    'DataType' => 'String',
                    'StringValue' => 'Transactional',
                ],
            ],
        ]);

        // If the message was sent, show the message ID.
        $messageId = $result->get('MessageId');
        if (!$messageId) {
            throw new Exception('SMS send did not return a proper MessageId');
        }
        return $messageId;
    }

    /**
     * Normalize number into E164 format
     *
     * @param string $phoneNumber US Phone number
     * @return string PhoneNumber in E164 format
     * @throws NumberParseException Parsing error
     */
    public function formatPhone(string $phoneNumber) {
        $phoneUtil = PhoneNumberUtil::getInstance();
        $numberProto = $phoneUtil->parse($phoneNumber, 'US');
        return $phoneUtil->format($numberProto, PhoneNumberFormat::E164);
    }
}
