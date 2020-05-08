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

namespace EA\Engine\Api\V1\Parsers;

/**
 * Business Parser
 *
 * This class will handle the encoding and decoding from the API requests.
 */
class Business implements ParsersInterface {
    /**
     * Encode Response Array
     *
     * @param array &$response The response to be encoded.
     */
    public function encode(array &$response)
    {
        $encodedResponse = [];

        // Gathering explicit fields
        $this->safeInsert('id', $response, $encodedResponse);
        $this->safeInsert('business_name', $response, $encodedResponse);
        $this->safeInsert('owner_first_name', $response, $encodedResponse);
        $this->safeInsert('owner_last_name', $response, $encodedResponse);
        $this->safeInsert('business_phone', $response, $encodedResponse);
        $this->safeInsert('mobile_phone', $response, $encodedResponse);
        $this->safeInsert('consent_sms', $response, $encodedResponse);
        $this->safeInsert('email', $response, $encodedResponse);
        $this->safeInsert('consent_email', $response, $encodedResponse);
        $this->safeInsert('address', $response, $encodedResponse);
        $this->safeInsert('city', $response, $encodedResponse);
        $this->safeInsert('state', $response, $encodedResponse);
        $this->safeInsert('zip_code', $response, $encodedResponse);
        $this->safeInsert('hash', $response, $encodedResponse);
        $this->safeInsert('modified', $response, $encodedResponse);
        $this->safeInsert('created', $response, $encodedResponse);

        // Get numbers for each business
        $this->safeInsert('slots_requested', $response, $encodedResponse);
        $this->safeInsert('slots_approved', $response, $encodedResponse);
        $this->safeInsert('slots_remaining', $response, $encodedResponse);
        $this->safeInsert('slots_occupied', $response, $encodedResponse);
        $this->safeInsert('codes_approved', $response, $encodedResponse);
        $this->safeInsert('codes_pending', $response, $encodedResponse);
        $this->safeInsert('codes_denied', $response, $encodedResponse);

        $response = $encodedResponse;
    }

    protected function safeInsert(string $key, array $array, array &$response): void
    {
        $response[$key] = $array[$key] ?? null;
    }

    /**
     * Decode Request
     *
     * @param array &$request The request to be decoded.
     * @param array $base Optional (null), if provided it will be used as a base array.
     */
    public function decode(array &$request, array $base = NULL)
    {
    }
}
