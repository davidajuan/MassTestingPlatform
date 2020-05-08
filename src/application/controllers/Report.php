<?php defined('BASEPATH') or exit('No direct script access allowed');

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
 * Report Controller
 * Creates reports and sends them as downloads
 *
 * @package Controllers
 */
class Report extends CI_Controller
{
    /**
     * @var array
     */
    protected $privileges;

    /**
     * Class Constructor
     */
    public function __construct()
    {
        parent::__construct();

        $this->load->library('session');
        $this->load->model('roles_model');
        if ($this->session->userdata('role_slug'))
        {
            $this->privileges = $this->roles_model->get_privileges($this->session->userdata('role_slug'));
        }

        $this->load->helper('download');

        $this->load->model('business_model');
        $this->load->model('business_request_model');
        $this->load->model('customers_model');
        $this->load->model('appointments_model');
        $this->load->model('providers_model');

        // Check privilege
        if (empty($this->privileges[PRIV_REPORTS]['view']))
        {
            throw new Exception('You do not have the required privileges for this task.');
        }
    }

    public function index()
    {
        // Get report type
        $reportName = $this->input->get('name');

        if ($reportName === 'businessCapacity') {
            $this->reportBusinessCapacity();
        }
        elseif ($reportName === 'patientList') {
            $this->reportPatientList();
        }
        elseif ($reportName === 'appointmentCounts') {
            try {
                // http://localhost/report?name=appointmentCounts&dateStart=2020-04-01&dateEnd=2020-04-30&providerId=2
                $dateStart = new DateTime($this->input->get('dateStart'));
                $dateEnd = new DateTime($this->input->get('dateEnd'));
                $providerId = intval($this->input->get('providerId'));
                $this->reportAppointmentCounts($dateStart, $dateEnd, $providerId);
            } catch (Exception $e) {
                throw new Exception('Invalid inputs');
            }
        }
        elseif ($reportName === 'businessesRequested') {
            try {
                $dateStart = new DateTime($this->input->get('dateStart'));
                $dateEnd = new DateTime($this->input->get('dateEnd'));
                $status = $this->input->get('status');
                $this->reportBusinessesRequested($dateStart, $dateEnd, $status);
            } catch (Exception $e) {
                throw new Exception('Invalid inputs');
            }
        }
        elseif ($reportName === 'appointmentListCod') {
            try {
                $dateStart = new DateTime($this->input->get('dateStart'));
                $dateEnd = new DateTime($this->input->get('dateEnd'));
                $this->reportAppointmentList($dateStart, $dateEnd, true);
            } catch (Exception $e) {
                throw new Exception('Invalid inputs');
            }
        }
        else {
            throw new Exception('You are missing report name');
        }
    }


    /**
     * Patient / Doctor Info Lists
     *
     * @return void
     */
    protected function reportPatientList(): void
    {
        $customers = $this->customers_model->get_batch();

        $fileName = 'Patient_Report_' . date('Y-m-d_H-i-s') . '.csv';
        $this->createDownloadableCsv($fileName, $customers);
    }

    /**
     * Undocumented function
     *
     * @param DateTime $dateStart
     * @param DateTime $dateEnd
     * @param bool|null $codWorker set to true to only show, false to exclude, null to include
     * @return void
     */
    protected function reportAppointmentList(DateTime $dateStart, DateTime $dateEnd, ?bool $codWorker = null): void
    {
        $criteria = [
            'DATE(book_datetime) >=' => $dateStart->format('Y-m-d'),
            'DATE(book_datetime) <=' => $dateEnd->format('Y-m-d'),
        ];

        if ($codWorker === true) {
            $criteria['business_code ='] = Config::BUSINESS_CODE_CITY_WORKER;
        }
        elseif ($codWorker === false) {
            $criteria['business_code !='] = Config::BUSINESS_CODE_CITY_WORKER;
        }

        $data = $this->appointments_model->get_batch_customer($criteria);

        $fileName = 'Appointment_Report_' . date('Y-m-d_H-i-s') . '.csv';
        $this->createDownloadableCsv($fileName, $data);
    }

    /**
     * Appointment counts by Date / Date Range
     * http://localhost/report?name=appointmentCounts
     *
     * Headers:
     *      - Date
     *      - Created Appointments
     *      - Created Patient Appointments
     *      - Created Provider Appointments
     *      - Created CIE Appointments
     *      - Created City Appointments
     *      - Created Business Appointments
     *      - Scheduled For Appointments
     *      - Scheduled For Patient Appointments
     *      - Scheduled For Provider Appointments
     *      - Scheduled For CIE Appointments
     *      - Scheduled City Appointments
     *      - Scheduled Business Appointments
     *      -- Last Row
     *      - Totals for each field
     *
     * @param DateTime $dateStart
     * @param DateTime $dateEnd
     * @param integer $providerId
     * @return void
     */
    protected function reportAppointmentCounts(DateTime $dateStart, DateTime $dateEnd, int $providerId): void
    {
        // Date range
        $interval = new DateInterval('P1D');
        $dateEnd = $dateEnd->modify('+1 day'); // Allow the date to be inclusive
        $period = new DatePeriod($dateStart, $interval, $dateEnd);

        // By iterating over the DatePeriod object, all of the
        // recurring dates within that period are printed.
        $data = [];
        foreach ($period as $date) {
            $data[] = [
                'Date' => $date->format('Y-m-d'),
                'Created Appointments' => 0,
                'Created Patient Appointments' => 0,
                'Created Provider Appointments' => 0,
                'Created CIE Appointments' => 0,
                'Created City Appointments' => 0,
                'Created Business Appointments' => 0,
                'Scheduled For Appointments' => 0,
                'Scheduled For Patient Appointments' => 0,
                'Scheduled For Provider Appointments' => 0,
                'Scheduled For CIE Appointments' => 0,
                'Scheduled City Appointments' => 0,
                'Scheduled Business Appointments' => 0,
            ];
        }

        // Get models
        $provider = $this->providers_model->get_row($providerId);
        // Get Slot Counts
        $allServiceIds = $provider['services'];

        foreach ($data as $idx => $row) {
            // Compile datetime
            $dateTime = new DateTime($row['Date']);
            $dateTime->setTime(0, 0, 0); // Set to very beginning of the day
            $endDate = clone $dateTime;
            $endDate->setTime(23, 59, 59);

            $data[$idx]['Created Appointments'] = $this->appointments_model->count_created_appointments($dateTime, $allServiceIds);
            $data[$idx]['Created Patient Appointments'] = $this->appointments_model->count_created_appointments($dateTime, $allServiceIds, ['ea_users.caller' => CALLER_TYPE_PATIENT]);
            $data[$idx]['Created Provider Appointments'] = $this->appointments_model->count_created_appointments($dateTime, $allServiceIds, ['ea_users.caller' => CALLER_TYPE_PROVIDER]);
            $data[$idx]['Created CIE Appointments'] = $this->appointments_model->count_created_appointments($dateTime, $allServiceIds, ['ea_users.caller' => CALLER_TYPE_CIE]);
            $data[$idx]['Created City Appointments'] = $this->appointments_model->count_created_appointments($dateTime, $allServiceIds, ['ea_users.caller' => CALLER_TYPE_CIE, 'ea_appointments.business_code' => Config::BUSINESS_CODE_CITY_WORKER]);
            $data[$idx]['Created Business Appointments'] = $this->appointments_model->count_created_appointments($dateTime, $allServiceIds, ['ea_users.caller' => CALLER_TYPE_CIE, 'ea_appointments.business_code !=' => Config::BUSINESS_CODE_CITY_WORKER]);
            $data[$idx]['Scheduled For Appointments'] = $this->appointments_model->count_scheduled_appointments($dateTime, $endDate, $allServiceIds);
            $data[$idx]['Scheduled For Patient Appointments'] = $this->appointments_model->count_scheduled_appointments($dateTime, $endDate, $allServiceIds, ['ea_users.caller' => CALLER_TYPE_PATIENT]);
            $data[$idx]['Scheduled For Provider Appointments'] = $this->appointments_model->count_scheduled_appointments($dateTime, $endDate, $allServiceIds, ['ea_users.caller' => CALLER_TYPE_PROVIDER]);
            $data[$idx]['Scheduled For CIE Appointments'] = $this->appointments_model->count_scheduled_appointments($dateTime, $endDate, $allServiceIds, ['ea_users.caller' => CALLER_TYPE_CIE]);
            $data[$idx]['Scheduled City Appointments'] = $this->appointments_model->count_scheduled_appointments($dateTime, $endDate, $allServiceIds, ['ea_users.caller' => CALLER_TYPE_CIE, 'ea_appointments.business_code' => Config::BUSINESS_CODE_CITY_WORKER]);
            $data[$idx]['Scheduled Business Appointments'] = $this->appointments_model->count_scheduled_appointments($dateTime, $endDate, $allServiceIds, ['ea_users.caller' => CALLER_TYPE_CIE, 'ea_appointments.business_code !=' => Config::BUSINESS_CODE_CITY_WORKER]);
        }

        if (!empty($data)) {
            // Compile Totals
            $colNames = array_keys($data[0]);
            // Remove Date col
            $colNames = array_diff($colNames, ['Date']);
            $totals = [
                'Date' => 'Total'
            ];
            // Sum up totals
            foreach ($colNames as $colName) {
                $totals[$colName] = array_sum(array_column($data,$colName));
            }
            // Append Totals
            $data[] = $totals;
        }


        $fileName = 'Appointment_Counts_Report_' . date('Y-m-d_H-i-s') . '.csv';
        $this->createDownloadableCsv($fileName, $data);
    }

    /**
     * Businesses Requested, Approved, by Date / Range
     *
     * @param DateTime $dateStart
     * @param DateTime $dateEnd
     * @param string $status
     * @return void
     */
    protected function reportBusinessesRequested(DateTime $dateStart, DateTime $dateEnd, ?string $status = null): void
    {
        $criteria = [
            'DATE(bus_req.created) >=' => $dateStart->format('Y-m-d'),
            'DATE(bus_req.created) <=' => $dateEnd->format('Y-m-d'),
        ];

        if (!empty($status)) {
            $criteria['bus_req.status'] = $status;
        }
        $data = $this->business_request_model->get_batch_business($criteria);

        $fileName = 'Business_Requests_Report_' . date('Y-m-d_H-i-s') . '.csv';
        $this->createDownloadableCsv($fileName, $data);
    }

    /**
     * Report contains: Business Name, Appointments Set, Appointments Allotted, Percentage Set/Allotted
     *
     * @return void
     */
    protected function reportBusinessCapacity(): void
    {
        // Get counts
        $counts = $this->business_model->get_capacity_counts();

        // Compile records
        $data = [];
        foreach ($counts as $key => $value) {
            $percent = floatval($value['AppointmentsCreated']) / floatval($value['AppointmentsApproved']) * 100;

            $data[] = [
                'Business Name' => $value['business_name'],
                'Appointments Set' => $value['AppointmentsCreated'],
                'Appointments Allotted' => $value['AppointmentsApproved'],
                'Capacity' => round($percent, 1) . '%',
            ];
        }

        $fileName = 'Business_Capacity_Report_' . date('Y-m-d_H-i-s') . '.csv';
        $this->createDownloadableCsv($fileName, $data);
    }

    /**
     * Convert an array of fields to a single CSV line
     * https://www.php.net/manual/en/function.fputcsv.php#121950
     *
     * @param array $fields
     * @return string
     */
    protected function csvStr(array $fields): string
    {
        $f = fopen('php://memory', 'r+');
        if (fputcsv($f, $fields) === false) {
            return false;
        }
        rewind($f);
        $csv_line = stream_get_contents($f);
        fclose($f);
        return rtrim($csv_line);
    }

    /**
     * Create a CSV and force download headers
     *
     * @param string $fileName
     * @param array $records
     * @return boolean success
     */
    protected function createDownloadableCsv(string $fileName, array $records): bool {
        $data = [];

        // Only compile if we have data
        if (!empty($records)) {
            // Get Header Fields
            $csvHeader = array_keys($records[0]);

            // Compile CSV
            $data[] = $this->csvStr($csvHeader);
            foreach ($records as $key => $value) {
                $data[] = $this->csvStr($value);
            }
        }
        else {
            // Fill with empty data
            $data[] = 'NO DATA TO DISPLAY';
        }

        // Force download headers
        force_download($fileName, implode("\n", $data));
        return true;
    }
}
