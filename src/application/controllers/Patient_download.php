<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Patient_download extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->load->library('session');
        $this->load->model('roles_model');
        if ($this->session->userdata('role_slug'))
        {
            $this->privileges = $this->roles_model->get_privileges($this->session->userdata('role_slug'));
        }

        $this->load->model('patientform_model');
        $this->load->model('appointments_model');
        $this->load->library('pdf');
        $this->load->helper('download');

    }

    /**
     * To generate a patient req form, hit:
     * http://localhost/patient_download/req/123456CR
     *
     */
    public function req($hash)
    {
        // Check privilege
        if (empty($this->privileges[PRIV_APPOINTMENTS]['view']))
        {
            throw new Exception('You do not have the required privileges for this task.');
        }

        if (empty($hash))
        {
            throw new Exception('Appointment hash required');
        }

        $appointment = $this->appointments_model->get_appointment_by_hash($hash);
        $html_content = $this->patientform_model->generatePatientsReqForm($appointment);

        $this->pdf->loadHtml($html_content);
        $this->pdf->render();
        $file = $this->pdf->output(["Attachment" => 0]);

        // Force download headers
        $fileName = 'Patient_Req_' . $hash . '.pdf';
        force_download($fileName, $file);
    }
}
