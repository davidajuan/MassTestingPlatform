<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');

use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Client;
use Psr\Http\Message\ResponseInterface;
use GuzzleHttp\Exception\RequestException;

class Rc_dashboard {

    /**
     *
     */
    public function __construct() {

    }

    /**
     * Helper function to generate the scheduled date range
     * @return array tuple array with DateTimes for [dateStart, dateEnd]
     */
    public function generateScheduledTotalRange() {
        $dateCurr = new DateTime();

        $apptStart = clone $dateCurr;
        $apptStart->add(date_interval_create_from_date_string('1 day')); // Can't book today's appointments
        $apptStart->setTime(0, 0, 0); // Set to very beginning of the day

        $apptEnd = clone $apptStart;
        $apptEnd->setTime(23, 59, 59); // Set to very end of the day
        $apptEnd->add(date_interval_create_from_date_string('7 days')); // Look forward 7 days;

        return [$apptStart, $apptEnd];
    }

    /**
     * Build the raw array for RC Dashboard
     *
     * start_datetime
     * zip_code
     * city
     * dob
     * caller
     * availableToday - Slots available for the next logical day that can be booked (if next day is 0, do not advance.)
     * availableTotal - Total available appointments in the next 7 days (EX: Number that is displayed on add patient screen)
     * scheduledToday - Number of appointments that were created TODAY (Not Appointments that are occurring today)
     * scheduledTotal - How many appointments have been booked in the next 7 days
     *
     * @param array $data
     * @return array
     */
    public function buildBody(array $data) {
        $age = date_diff(date_create($data['dob']), date_create('now'))->y;
        $isPatient = $data['caller'] === 'patient' ? '1' : '0';

        $body = [
            'name' => 'AppStat',
            'fields' => [
                'AppointmentTime' => date('Y-m-d', strtotime($data['start_datetime'])), // Appointment Date that was just booked
                'Zip' => $data['zip_code'],
                'City' => $data['city'],
                'AvailableToday' => $data['availableToday'],
                'AvailableTotal' => $data['availableTotal'],
                'Age' => $age,
                'IsPatient' => $isPatient,
                'ScheduledToday' => $data['scheduledToday'],
                'ScheduledTotal' => $data['scheduledTotal'],
            ]
        ];
        // Normalize array and remove any NULL values
        $body['fields'] = array_filter($body['fields'], function($value) {
            return !is_null($value) && $value !== '';
        });

        return $body;
    }

    /**
     *
     * @return ResponseInterface
     * @throws Exception Generic Error
     */
    public function pushToWebhook(array $body) {
        $dashboardUrl = Config::METRICS_WEBHOOK_URL;

        $client = new Client();
        $headers = [
            'Content-Type' => 'application/json'
        ];
        $request = new Request('POST', $dashboardUrl, $headers, json_encode($body));
        $response = $client->send($request, ['timeout' => 3]);

        // Success looks like:
        // $code = $response->getStatusCode(); // 200
        // $reason = $response->getReasonPhrase(); // OK
        // $this->logger->debug($code);
        // $this->logger->debug($reason);

        /*
        // TODO: Async not working. Never done it this way before
        $promise = $client->sendAsync($request);
        $promise->then(
            function (ResponseInterface $res) {
                $this->logger->debug(sprintf('Response promise calling RC Dashboard webhook, StatusCode: %s', $res->getStatusCode()));
            },
            function (RequestException $e) {
                $this->logger->error(sprintf('Error on promise calling RC Dashboard webhook, Exception: %s', $e));
            }
        );
        */

        return $response;
    }
}
