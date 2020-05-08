<?php
class PatientForm_model extends CI_Model
{
    const CIE_CONTENT = 'CRITICAL INFRASTRUCTURE EMPLOYEE';

    /**
     * Generate patient req form
     *
     * @param array $appointment
     * @return string $output html content for pdf
     */
    function generatePatientsReqForm(array $appointment)
    {
        $output = "<style>
        .container{ width: 768px;height: 985px; margin-left: -20px; padding: 0; margin-top: -20px; position: relative; }
        .form{position:relative;border-left:25px solid #cb3433}
        .form__content{border-top:1px solid #bfc1c2;border-bottom:1px solid #bfc1c2;border-right:1px solid #bfc1c2;}
        label,.label{display:block;font-weight:bold;padding-left:4px;padding-top:4px;font-size:12.8px;letter-spacing:-0.25}
        .form__title{background-color: #cb3433;color: #fff;font-weight:800;font-size:16px;text-transform:uppercase;letter-spacing:0;position:absolute;top: 96px;left:0;margin-left:-22px;transform:rotate(270deg);transform-origin:0 0;-webkit-print-color-adjust:exact}@media print{.form__title{color:#fff}}
        .formRow--firstName{width:272px}.formRow--middle{width:48px}.formRow--state{width:80px}.formRow--zip{width:80px}.formRow--dob{background:#eadde2;width:108.8px}.formRow--age{width:48px}.formRow--phone{width:192px}.formRow--email{width:240px}.formRow--date{background:#eadde2;width:280.96px}
        .title{color:#cb3433;font-weight:800}
        .borderBottom{border-bottom:1px solid #bfc1c2}
        .borderBottom--red{border-color:#cb3433}
        .borderRight{border-right:1px solid #bfc1c2}
        .borderAll{border:1px solid #bfc1c2}
        .fontSize--medium{font-size:20px}
        .textDecoration--underline{text-decoration:underline}

        </style>";

        $output .= '<div class="container">';

        $lastNameAbbrv = $this->getLastNameAbbrv($appointment['last_name']);
        $app_start = $this->getAppointmentStartTime($appointment['start_datetime']);
        $dobDisplay = date('Y-m-d', strtotime($appointment['dob']));

        // PNG Bar Code
        $generator = new Picqer\Barcode\BarcodeGeneratorPNG();
        $pngBarCode = '<img src="data:image/png;base64,' . base64_encode($generator->getBarcode($appointment['hash'], $generator::TYPE_CODE_39, 1)) . '">';

        $output .= '
            <div style="float: left;">
                <div style="display: inline-block; font-weight: bold; font-size: 35px; width: 175px; vertical-align: top;">
                    ' . $lastNameAbbrv .'
                </div>
                <div style="display: inline-block; width: 350px; text-align: center; vertical-align: top;">
                    <p style="margin: 0 0 -10px; padding: 0;">
                      CV800 - City of Detroit
                    </p>
                    <br/>
                    ' . $pngBarCode .'
                    <p style="margin: 5px 0 0; padding: 0;">
                      ' . $appointment['hash'] .'
                    </p>
                </div>
                <div style="display: inline-block; vertical-align: top; text-align: right; width: 220px;">
                    <span style="font-weight: bold;">Appointment Date/Time</span>
                    <span style="display: block; 4px; font-size: 16px;">' . $app_start . '</span>
                </div>
            </div>
            <div style="font-size: 35px; text-align: center; color: #cb3433; font-weight: bold; margin-top: 16px; clear: both;">
                '. $this->getPrescriptionContent($appointment['business_code']) . '
            </div>

            <div class="form" style="display: block; margin-bottom: 35px; clear: both;">
                <p class="form__title">
                    PATIENT
                </p>
                <div class="form__content">
                    <div class="borderBottom" style="overflow: hidden; height: 40px;">
                        <div class="borderRight" style="display: inline-block; margin: 0; width: 150px;">
                            <label for="" style="display: block; height: 25px; padding-top: 15px;">LAST NAME</label>
                            <span style="display: block; height: 40px; margin-top: -5px; padding-left: 4px; font-size: 14.4px;">' .$appointment['last_name'] . '</span>
                        </div>
                        <div class="borderRight" style="display: inline-block; margin: 0; width: 150px">
                            <label for="" style="display: block; height: 25px; padding-top: 15px;">FIRST NAME</label>
                            <span style="display: block; height: 40px; margin-top: -5px; padding-left: 4px; font-size: 14.4px;">' .$appointment['first_name'] . '</span>
                        </div>
                        <div class="formRow--middle borderRight" style="display: inline-block; margin: 0;">
                            <label for="" style="display: block; height: 25px; padding-top: 15px;">MI</label>
                            <span style="display: block; height: 40px; margin-top: -5px; padding-left: 4px; font-size: 14.4px;">' .$appointment['middle_initial'] . '</span>
                        </div>
                        <div style="display: inline-block; margin: 0; width: 100px;">
                            <label for="" style="display: block; height: 25px; padding-top: 15px;">GENDER</label>
                            <span style="display: block; height: 40px; margin-top: -5px; padding-left: 4px; font-size: 14.4px;">' .$appointment['gender'] . '</span>
                        </div>
                        <div class="formRow--dob borderRight" style="display: inline-block; margin: 0;">
                            <label for="" style="display: block; height: 25px; padding-top: 15px;">DATE OF BIRTH</label>
                            <span style="display: block; height: 40px; margin-top: -5px; padding-left: 4px; font-size: 14.4px;">' . $dobDisplay . '</span>
                        </div>
                        <div style="display: inline-block; width: 112px;">
                            <label for="" style="display: block; height: 25px; padding-top: 15px;">LAST 4 OF SSN.</label>
                            <span style="display: block; height: 40px; margin-top: -5px; padding-left: 4px; font-size: 14.4px;">' .$appointment['ssn'] . '</span>
                        </div>
                    </div>

                    <div class="borderBottom" style="overflow: hidden; height: 40px;">
                        <div class="borderRight" style="display: inline-block; margin: 0; width: 280px;">
                            <label for="" style="display: block; height: 25px; padding-top: 15px;">STREET</label>
                            <span style="display: block; height: 40px; margin-top: -5px; padding-left: 4px; font-size: 14.4px;">' .$appointment['address'] . '</span>
                        </div>
                        <div class="borderRight" style="display: inline-block; margin: 0; width: 70px;">
                            <label for="" style="display: block; height: 25px; padding-top: 15px;">APT. #</label>
                            <span style="display: block; height: 40px; margin-top: -5px; padding-left: 4px; font-size: 14.4px;">' .$appointment['apt'] . '</span>
                        </div>
                        <div class="borderRight" style="display: inline-block; margin: 0; width: 190px;">
                            <label for="" style="display: block; height: 25px; padding-top: 15px;">CITY</label>
                            <span style="display: block; height: 40px; margin-top: -5px; padding-left: 4px; font-size: 14.4px;">' .$appointment['city'] . '</span>
                        </div>
                        <div class="formRow--state borderRight" style="display: inline-block; margin: 0;">
                            <label for="" style="display: block; height: 25px; padding-top: 15px;">STATE</label>
                            <span style="display: block; height: 40px; margin-top: -5px; padding-left: 4px; font-size: 14.4px;">' .$appointment['state'] . '</span>
                        </div>
                        <div class="formRow--zip" style="display: inline-block; margin: 0;">
                            <label for="" style="display: block; height: 25px; padding-top: 15px;">ZIP</label>
                            <span style="display: block; height: 40px; margin-top: -5px; padding-left: 4px; font-size: 14.4px;">' .$appointment['zip_code'] . '</span>
                        </div>
                    </div>

                    <div style="overflow: hidden; height: 40px;">
                        <div class="formRow--age borderRight" style="display: inline-block; margin: 0;">
                            <label for="" style="display: block; height: 25px; padding-top: 15px;">AGE</label>
                            <span style="display: block; height: 40px; margin-top: -5px; padding-left: 4px; font-size: 14.4px;">' . $this->getAge($appointment['dob']) . '</span>
                        </div>
                        <div class="borderRight" style="display: inline-block; width: 136px;">
                            <label for="" style="display: block; height: 25px; padding-top: 15px;">CELL PHONE NO.</label>
                            <span style="display: block; height: 40px; margin-top: -5px; padding-left: 4px; font-size: 14.4px;">' .$appointment['mobile_number'] . '</span>
                        </div>
                        <div class="borderRight" style="display: inline-block; width: 136px;">
                            <label for="" style="display: block; height: 25px; padding-top: 15px;">HOME PHONE NO.</label>
                            <span style="display: block; height: 40px; margin-top: -5px; padding-left: 4px; font-size: 14.4px;">' .$appointment['phone_number'] . '</span>
                        </div>
                        <div class="formRow--email" style="display: inline-block; width: 270px;">
                            <label for="" style="display: block; height: 25px; padding-top: 15px;">PATIENT EMAIL</label>
                            <span style="display: block; height: 40px; margin-top: -5px; padding-left: 4px; font-size: 14.4px;">' .$appointment['email'] . '</span>
                        </div>
                    </div>
                </div>
            </div>

            <div>
                <div class="form" style="display: inline-block; margin-bottom: 20px; width: 400px; margin-right: 20px;">
                    <p class="form__title">
                      Physician
                    </p>
                    <div class="form__content" style="height: 150px;">
                        <div class="borderBottom" style="overflow: hidden; height: 40px;">
                            <div class="borderRight" style="display: inline-block; width: 160px;">
                            <label style="display: block; height: 25px; padding-top: 15px;">NAME</label>
                            <span style="display: block; height: 40px; margin-top: -5px; padding-left: 4px; font-size: 12px;">' .$appointment['doctor_first_name'] . ' ' . $appointment['doctor_last_name'] .'</span>
                            </div>
                            <div class="borderRight" style="display: inline-block; width: 100px;">
                            <label for="" style="display: block; height: 25px; padding-top: 15px;">NPI #</label>
                            <span style="display: block; height: 40px; margin-top: -5px; padding-left: 4px; font-size: 14.4px;">' . $appointment['doctor_npi'] . '</span>
                            </div>
                            <div style="display: inline-block;  width: 120px;">
                            <label for="" style="display: block; height: 25px; padding-top: 15px;">PHONE NUMBER</label>
                            <span style="display: block; height: 40px; margin-top: -5px; padding-left: 4px; font-size: 14.4px;">' . $appointment['doctor_phone_number'] . '</span>
                            </div>
                        </div>
                        <div class="borderBottom" style="height: 40px; overflow: hidden;">
                            <label for="">RX DATE / TIME</label>
                            <span style="display: block; height: 40px; margin-top: 0px; padding-left: 4px; font-size: 14.4px;">' .$appointment['rx_date'] . '</span>
                        </div>
                        <div style="overflow: hidden;">
                            <label for="">Address</label>
                            <span class="label" style="display: block; font-weight: 400;">' . $appointment['doctor_address'] . '</span>
                            <span class="label" style="display: inline-block; font-weight: 400; margin-bottom: 0;">' .$appointment['doctor_city'] . ', ' .$appointment['doctor_state'] . ', ' .$appointment['doctor_zip_code'] . '</span>
                        </div>
                    </div>
                </div>

                <div class="form" style="display: inline-block; width: 293px;">
                    <p class="form__title">
                        Specimen
                    </p>
                    <div class="form__content" style="height: 150px;">
                        <div style="overflow: hidden;">
                            <label for="" style="margin-bottom: 16px;">Date of collection:</label>
                            <label for="" style="margin-bottom: 16px;">Time of collection:</label>
                            <label for="" style="margin-bottom: 0;">Patient ID</label>
                            <label for="">'. $appointment['patient_id'] .'</label>
                        </div>
                    </div>
                </div>
            </div>

            <div style="height: 100px; border-left: 1px solid #bfc1c2; border-right: 1px solid #bfc1c2; border-top: 1px solid #bfc1c2;  border-bottom: 1px solid #cb3433">
                <div style="padding-left: 48px;">
                    <p style="font-size: 20px; text-decoration: underline; font-weight: bold">Nasopharynx (source)</p>
                    <p style="font-size: 20px;">TH68-0 Novel Coronavirus COVID-19 Nasopharynx</p>
                </div>
            </div>
            <div class="borderAll" style="height: 100px; border-left: 1px solid #bfc1c2; border-right: 1px solid #bfc1c2; border-bottom: 1px solid #bfc1c2;"></div>

            <div style="position: absolute; bottom: -20px">
                <div style="width: 192px; height: 96px; display: inline-block; margin: 0;">
                    <label style="margin-left: 10px;">' .$appointment['last_name'] . ', ' .$appointment['first_name'] . ', ' .$appointment['middle_initial'] . '</label>
                    <label style="margin-left: 10px;">' . $dobDisplay . '</label>
                </div>
                <div style="width: 192px; height: 96px; display: inline-block; margin: 0; margin-left: -4px;">
                    <label style="margin-left: 10px;">' .$appointment['last_name'] . ', ' .$appointment['first_name'] . ', ' .$appointment['middle_initial'] . '</label>
                    <label style="margin-left: 10px;">' . $dobDisplay . '</label>
                </div>
                <div style="width: 192px; height: 96px; display: inline-block; margin: 0; margin-left: -4px;">
                    <label style="margin-left: 10px;">' .$appointment['last_name'] . ', ' .$appointment['first_name'] . ', ' .$appointment['middle_initial'] . '</label>
                    <label style="margin-left: 10px;">' . $dobDisplay . '</label>
                </div>
                <div style="width: 192px; height: 96px; display: inline-block; margin: 0; margin-left: -4px;">
                    <label style="margin-left: 10px;">' .$appointment['last_name'] . ', ' .$appointment['first_name'] . ', ' .$appointment['middle_initial'] . '</label>
                    <label style="margin-left: 10px;">' . $dobDisplay . '</label>
                </div> 
            </div>
        ';
        $output .= '</div>';

        return $output;
    }

    function getAge($dob)
    {
        $today = date("Y-m-d");
        $diff = date_diff(date_create($dob), date_create($today));
        return $diff->format('%y');
    }

    /**
     * Get the prescription content for the printers
     *
     * @param array $appointment
     * @param array $orderedKeys array of keys that are in a specific order
     * @return string
     */
    protected function getPrescriptionContent($business_code)
    {
        if (!empty($business_code)) {
            return self::CIE_CONTENT;
        }

        return '';
    }

    function getLastNameAbbrv($last_name)
    {
      return ucfirst(substr($last_name, 0, 2));
    }

    function getAppointmentStartTime($start_datetime)
    {
      return date('l jS, F Y h:i A', strtotime($start_datetime));
    }
}
