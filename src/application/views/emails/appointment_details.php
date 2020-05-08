<html>
<head>
    <title>$emailSubject</title>
</head>
<body style="font: 13px arial, helvetica, tahoma;">
    <div class="email-container" style="width: 650px; border: 1px solid #eee;">
        <div id="header" style="background-color: #004445; padding: 20px;">
            <strong id="logo" style="color: #ffffff; font-size: 20px;">
              $emailHeader
            </strong>
        </div>

        <div id="content" style="padding: 20px;">
            <h2 style="margin-top: 0;">Appointment Details</h2>
            <table id="appointment-details" style="border-spacing: 0 10px;">
                <tr  style="vertical-align: top; margin-bottom: 5px;">
                    <td class="label" style="padding-bottom: 3px;font-weight: bold;">Patient Name</td>
                    <td style="padding: 3px;">$first_name $last_name</td>
                </tr>
                <tr  style="vertical-align: top;">
                    <td class="label" style="padding-bottom: 3px;font-weight: bold;">Date/Time</td>
                    <td style="padding: 3px;">$datetime_pretty</td>
                </tr>
                <tr style="vertical-align: top;">
                    <td class="label" style="padding-bottom: 3px;font-weight: bold;">Directions</td>
                    <td style="padding: 3px;">
                      Joe Dumars Fieldhouse
                      <br/>
                      Enter off E. State Fair Ave.
                    </td>
                </tr>
            </table>

            <h2></h2>
            <a
            href="https://detroitmi.gov/departments/detroit-health-department/programs-and-services/communicable-disease/coronavirus-covid-19/coronavirus-community-care-network-drive-thru-testing"
            style="width: 600px; color: #279989; font-size: 16px;">View More Information</a>
        </div>
    </div>
</body>
</html>
