<?php
defined('BASEPATH') or exit('No direct script access allowed');

class PatientForm extends CI_Controller
{

    // List and Order of CSV headers
    const PRINT_HEADERS = [
        'car_number',
        'book_datetime',
        'start_datetime',
        'end_datetime',
        'notes',
        'first_name',
        'last_name',
        'middle_initial',
        'email',
        'mobile_number',
        'phone_number',
        'address',
        'apt',
        'city',
        'state',
        'zip_code',
        'id_roles',
        'gender',
        'dob',
        'ssn',
        'language_pref',
        'language_pref_other',
        'patient_id',
        'patient_contact_pref',
        'patient_consent',
        'patient_consent_sms',
        'patient_pui',
        'patient_pui_reason_1',
        'patient_pui_reason_2',
        'patient_pui_reason_3',
        'patient_pui_reason_4',
        'patient_pui_reason_5',
        'patient_pui_reason_6',
        'doctor_first_name',
        'doctor_last_name',
        'doctor_npi',
        'doctor_address',
        'doctor_city',
        'doctor_state',
        'doctor_zip_code',
        'doctor_provider',
        'doctor_provider_other',
        'provider_patient_id',
        'doctor_phone_number',
        'rx_date',
        'caller',
        'age',
        'prescription_content',
        'last_name_abbrv',
        'app_start',
        'first_responder',
        'pediatric_swab',
        'ereqnumber',
        'patient_pui_reason_7',
    ];

    const HEALTH_NETWORK_HEADERS = [
        'car_number',
        'book_datetime',
        'start_datetime',
        'end_datetime',
        'notes',
        'first_name',
        'last_name',
        'middle_initial',
        'email',
        'mobile_number',
        'phone_number',
        'address',
        'apt',
        'city',
        'state',
        'zip_code',
        'county',
        'id_roles',
        'gender',
        'dob',
        'ssn',
        'language_pref',
        'language_pref_other',
        'patient_id',
        'patient_contact_pref',
        'patient_consent',
        'patient_consent_sms',
        'patient_pui',
        'patient_pui_reason_1',
        'patient_pui_reason_2',
        'patient_pui_reason_3',
        'patient_pui_reason_4',
        'patient_pui_reason_5',
        'patient_pui_reason_6',
        'patient_pui_reason_7',
        'doctor_first_name',
        'doctor_last_name',
        'doctor_npi',
        'doctor_address',
        'doctor_city',
        'doctor_state',
        'doctor_zip_code',
        'doctor_provider',
        'doctor_provider_other',
        'provider_patient_id',
        'doctor_phone_number',
        'rx_date',
        'pcp_first_name',
        'pcp_last_name',
        'caller',
        'first_responder',
        'pediatric_swab',
        'ereqnumber',
        'pcp_phone_number',
        'pcp_address',
        'pcp_city',
        'pcp_state',
        'pcp_zip_code',
    ];

    public function __construct()
    {
        parent::__construct();
        $this->load->model('patientform_model');
        $this->load->model('appointments_model');
        $this->load->model('business_model');
        $this->load->model('business_request_model');
        $this->load->library('pdf');

        // Don't allow browser
        if (!$this->input->is_cli_request()) {
            $ret = [
                'status' => 'error',
                'message' => 'Missing permissions',
            ];
            echo json_encode($ret) . PHP_EOL;
            die();
        }
    }

    /**
     * To generate a patient form, hit:
     * http://localhost/patientform/generate
     *
     * To run on a specific day, hit:
     * http://localhost/patientform/generate/2020-03-25
     */
    public function generate($customDate = "")
    {
        // use date that comes in from url, otherwise use todays date
        $day = $customDate ? date('Y-m-d', strtotime($customDate)) : date('Y-m-d');

        if (!is_dir(Config::DATA_DIR . $day)) {
            mkdir(Config::DATA_DIR . $day, 0755);
        }
        $cntFilesSent = 0;

        // generate csv for entire day
        $appointments = $this->appointments_model->get_appointment_per_day($day);

        // generate file for the day (smaller checklist)
        if ($this->generateCsvForDay($day, $appointments)) {
            $cntFilesSent++;
        }

        // large dump of data
        if ($this->generateCsvMasterForDay($day, $appointments)) {
            $cntFilesSent++;
        }

        // dump for printing company
        if ($this->generateCsvForDayForPrint($day, $appointments)) {
            $cntFilesSent++;
        }

        $message = sprintf('Files generated for %s', $day);
        $this->logger->notice($message);
        $ret = [
            'status' => 'success',
            'message' => $message,
            'files_sent' => $cntFilesSent
        ];
        echo json_encode($ret) . PHP_EOL;
    }

    protected function generateFileName($start_datetime, $file)
    {
        $day = date('Y-m-d', strtotime($start_datetime));

        return "$day/$file";
    }

    protected function generateCsvMasterForDay($day, $appointments): bool
    {

        if (!empty($appointments)) {

            // Massage Data
            $appointments = $this->prependCarNumber($appointments);
            foreach ($appointments as $key => $value) {
                // Calculate data
                $age = $this->patientform_model->getAge($appointments[$key]['dob']);
                $appointments[$key]['pediatric_swab'] = $age < 16 ? 'Yes' : 'No';
                $appointments[$key]['ereqnumber'] = $appointments[$key]['hash'];

                // Add these dummy fields because they no longer exist in DB, but are needed for ordering/padding!
                $appointments[$key]['language_pref'] = '';
                $appointments[$key]['language_pref_other'] = '';
                $appointments[$key]['doctor_provider'] = '';
                $appointments[$key]['doctor_provider_other'] = '';

                // Normalize Data
                $appointments[$key] = $this->normalizeArray($appointments[$key], self::HEALTH_NETWORK_HEADERS);
            }


            // Get Header Fields
            $csvHeader = array_keys($appointments[0]);

            // Write file
            $fileName = $this->generateFileName($day, Config::FILENAME_PATIENT_MASTER);
            $fp = fopen(Config::DATA_DIR . $fileName, 'w');
            fputcsv($fp, $csvHeader);
            foreach ($appointments as $appointment) {
                fputcsv($fp, $appointment);
            }
            fclose($fp);

            return true;
        }

        return false;
    }

    protected function generateCsvForDay($day, $appointments): bool
    {
        if (!empty($appointments)) {
            $fileName = $this->generateFileName($day, Config::FILENAME_PATIENT_APPOINTMENTS);

            $fp = fopen(Config::DATA_DIR . $fileName, 'w');

            $cleanAppointments = $this->cleanAppointments($appointments);

            $csvHeader = $this->getCSVHeaderRow($cleanAppointments[0]);
            fputcsv($fp, $csvHeader);

            foreach ($cleanAppointments as $appointment) {
                $appointment = $this->removeAppointmentKeys($appointment);
                fputcsv($fp, $appointment);
            }

            fclose($fp);

            return true;
        }

        return false;
    }

    protected function generateCsvForDayForPrint($day, $appointments): bool
    {
        if (!empty($appointments)) {

            // Massage Data
            $appointments = $this->prependCarNumber($appointments);
            foreach ($appointments as $key => $value) {
                // Calculate data
                $age = $this->patientform_model->getAge($appointments[$key]['dob']);
                $appointments[$key]['age'] = $age;
                $appointments[$key]['prescription_content'] = $this->patientform_model->getPrescriptionContent($appointments[$key]['business_code']);
                $appointments[$key]['last_name_abbrv'] = $this->patientform_model->getLastNameAbbrv($appointments[$key]['last_name']);
                $appointments[$key]['app_start'] = $this->patientform_model->getAppointmentStartTime($appointments[$key]['start_datetime']);
                $appointments[$key]['dob'] = date('Y-m-d', strtotime($appointments[$key]['dob']));
                $appointments[$key]['pediatric_swab'] = $age < 16 ? 'Yes' : 'No';
                $appointments[$key]['first_responder'] = $appointments[$key]['first_responder'] == "1" ? 'Yes' : 'No';
                $appointments[$key]['ereqnumber'] = $appointments[$key]['hash'];

                // Add these dummy fields because they no longer exist in DB, but are needed for ordering/padding!
                $appointments[$key]['language_pref'] = '';
                $appointments[$key]['language_pref_other'] = '';
                $appointments[$key]['doctor_provider'] = '';
                $appointments[$key]['doctor_provider_other'] = '';

                // Normalize Data
                $appointments[$key] = $this->normalizeArray($appointments[$key], self::PRINT_HEADERS);
            }

            // Get Header Fields
            $csvHeader = array_keys($appointments[0]);

            // Write file
            $fileName = $this->generateFileName($day, Config::FILENAME_PATIENT_PRINTS);
            $fp = fopen(Config::DATA_DIR . $fileName, 'w');
            fputcsv($fp, $csvHeader);
            foreach ($appointments as $appointment) {
                fputcsv($fp, $appointment);
            }
            fclose($fp);

            return true;
        }

        return false;
    }

    protected function removeAppointmentKeys($appointment)
    {
        // rename has to ereqnumber in files
        if (isset($appointment['hash'])) {
            $appointment['ereqnumber'] = $appointment['hash'];
        }

        unset($appointment['id']);
        unset($appointment['hash']);
        unset($appointment['is_unavailable']);
        unset($appointment['id_users_provider']);
        unset($appointment['id_users_customer']);
        unset($appointment['id_services']);
        unset($appointment['id_google_calendar']);

        return $appointment;
    }
    protected function getCSVHeaderRow($appointment)
    {
        $appointment = $this->removeAppointmentKeys($appointment);

        return array_keys($appointment);
    }

    /**
     * Goes through appointments from the database and filters the columns we need
     */
    protected function cleanAppointments($appointments)
    {
        $cleanAppointments = [];

        foreach ($appointments as $key => $appointment) {
            $cleanAppointments[] = [
                'car_number' => $key + 1,
                'start_datetime' => date('M d h:iA', strtotime($appointment['start_datetime'])),
                'name' => $appointment['last_name'] . ', ' . $appointment['first_name'],
                'dob' => date('Y-m-d', strtotime($appointment['dob'])),
                'Patient Type' =>  $this->patientType($appointment['business_code']),
            ];
        }

        return $cleanAppointments;
    }

    /**
     * Appends the car number to the front of the file
     */
    protected function prependCarNumber($appointments)
    {
        for ($i = 0; $i < count($appointments); $i++) {
            // put car_number at the front of the file
            $appointments[$i]  = ['car_number' => $i + 1] + $appointments[$i];
        }

        return $appointments;
    }

    /**
     * Takes an array and normalizes it.
     *
     * Puts keys in specific indexed order.
     * Removes any keys that don't exist in $orderedKeys.
     * Will throw an exception if a key doesn't exist in $orderedKeys.
     * Source: https://stackoverflow.com/a/9098675/1583548
     *
     * @param array $data records/rows of data
     * @param array $orderedKeys array of keys that are in a specific order
     * @throws Exception If a record doesn't have a listed key in $orderedKeys
     */
    protected function normalizeArray(array $data, array $orderedKeys)
    {
        // Check for non existent keys
        $diff = array_diff_key(array_flip($orderedKeys), $data);
        if (!empty($diff)) {
            // Remove PII
            $diff = array_keys($diff);
            $diffKeys = implode(', ', $diff);
            $diffKeys = rtrim($diffKeys, ',');

            throw new Exception(sprintf('Data array is missing required key(s): %s', $diffKeys));
        }

        // order keys
        $data = array_merge(array_flip($orderedKeys), $data);
        // trim non existent keys
        $data = array_intersect_key($data, array_flip($orderedKeys));

        return $data;
    }

    protected function patientType($business_code)
    {
        return !empty($business_code) ? "Critical Infrastructure Employee" : "General Public";
    }
}
