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
 * Appointments Parser
 *
 * This class will handle the encoding and decoding from the API requests.
 */
class Metrics implements ParsersInterface {
    const KEY_SCHEDULED_TODAY= 'scheduledToday';
    const KEY_SCHEDULED_TODAY_PROVIDER = 'scheduledTodayProvider';
    const KEY_SCHEDULED_TODAY_PATIENT = 'scheduledTodayPatient';
    const KEY_SCHEDULED_TODAY_CIE = 'scheduledTodayCIE';
    const KEY_AVAILABLE_TOTAL = 'availableTotal';
    const KEY_BOOKED_TOMORROW = 'bookedTomorrow';
    const KEY_BOOKED_TOTAL = 'bookedTotal';

    const KEY_TODAY_BUSINESS_REGISTERED = 'cie_today_business_registered';
    const KEY_TODAY_APPOINTMENT_REQUESTED = 'cie_today_appointment_requested';
    const KEY_TODAY_APPOINTMENT_APPROVED = 'cie_today_appointment_approved';

    const KEY_BUSINESS_REGISTERED = 'cie_business_registered';
    const KEY_APPOINTMENT_REQUESTED = 'cie_appointment_requested';
    const KEY_APPOINTMENT_APPROVED = 'cie_appointment_approved';
    const KEY_PATIENT_CIE_SCHEDULED = 'cie_patient_scheduled';

    const KEY_BUSINESS_SLOT_REMAINING = 'cie_slots_remaining';
    const KEY_BUSINESS_SLOT_OCCUPIED = 'cie_slots_occupied';
    const KEY_BUSINESS_CODE_APPROVED = 'cie_codes_approved';
    const KEY_BUSINESS_CODE_PENDING = 'cie_codes_pending';
    const KEY_BUSINESS_CODE_DENIED = 'cie_codes_denied';

    /**
     * Encode Response Array
     *
     * @param array &$response The response to be encoded.
     */
    public function encode(array &$response)
    {
        $encodedResponse = [
            self::KEY_SCHEDULED_TODAY => $response[self::KEY_SCHEDULED_TODAY] ?? null,
            self::KEY_SCHEDULED_TODAY_PROVIDER => $response[self::KEY_SCHEDULED_TODAY_PROVIDER] ?? null,
            self::KEY_SCHEDULED_TODAY_PATIENT => $response[self::KEY_SCHEDULED_TODAY_PATIENT] ?? null,
            self::KEY_SCHEDULED_TODAY_CIE => $response[self::KEY_SCHEDULED_TODAY_CIE] ?? null,
            self::KEY_AVAILABLE_TOTAL => $response[self::KEY_AVAILABLE_TOTAL] ?? null,
            self::KEY_BOOKED_TOMORROW => $response[self::KEY_BOOKED_TOMORROW] ?? null,
            self::KEY_BOOKED_TOTAL => $response[self::KEY_BOOKED_TOTAL] ?? null,

            self::KEY_TODAY_BUSINESS_REGISTERED => $response[self::KEY_TODAY_BUSINESS_REGISTERED] ?? null,
            self::KEY_TODAY_APPOINTMENT_REQUESTED => $response[self::KEY_TODAY_APPOINTMENT_REQUESTED] ?? null,
            self::KEY_TODAY_APPOINTMENT_APPROVED => $response[self::KEY_TODAY_APPOINTMENT_APPROVED] ?? null,

            self::KEY_BUSINESS_REGISTERED => $response[self::KEY_BUSINESS_REGISTERED] ?? null,
            self::KEY_APPOINTMENT_REQUESTED => $response[self::KEY_APPOINTMENT_REQUESTED] ?? null,
            self::KEY_APPOINTMENT_APPROVED => $response[self::KEY_APPOINTMENT_APPROVED] ?? null,
            self::KEY_PATIENT_CIE_SCHEDULED => $response[self::KEY_PATIENT_CIE_SCHEDULED] ?? null,

            self::KEY_BUSINESS_SLOT_REMAINING => $response[self::KEY_BUSINESS_SLOT_REMAINING] ?? null,
            self::KEY_BUSINESS_SLOT_OCCUPIED => $response[self::KEY_BUSINESS_SLOT_OCCUPIED] ?? null,
            self::KEY_BUSINESS_CODE_APPROVED => $response[self::KEY_BUSINESS_CODE_APPROVED] ?? null,
            self::KEY_BUSINESS_CODE_PENDING => $response[self::KEY_BUSINESS_CODE_PENDING] ?? null,
            self::KEY_BUSINESS_CODE_DENIED => $response[self::KEY_BUSINESS_CODE_DENIED] ?? null,
        ];

        $response = $encodedResponse;
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
