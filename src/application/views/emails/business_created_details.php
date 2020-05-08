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
            <p>$intro</p>
            <p>$message</p>
            <div>
              <p style="font-size: 16px; font-weight: bold; margin-bottom: 5px;">
                Business Name
              </p>
              $business_name
            </div>
            <div>
              <p style="font-size: 16px; font-weight: bold; margin-bottom: 5px;">
                Owner/Authorized Representative
              </p>
              $first_name $last_name
            </div>
            <div>
              <p style="font-size: 16px; font-weight: bold; margin-bottom: 5px;">
                Requested Number of Appointments
              </p>
              $slots_requested
            </div>
        </div>
    </div>
</body>
</html>
