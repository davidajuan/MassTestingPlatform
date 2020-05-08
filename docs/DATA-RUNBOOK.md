---
layout: page
title: Data Orchestration
description: Overview of Data Orchestration
permalink: /data-runbook
---
Data Orchestration is composed of the following processes to exchange data to and from the system

1) **Printer Files**
   These are files created for a printing company to use and generate the [patient requistion form](../docs/sample_files/patient_requisition_sample.pdf) and [patient scheduling form](../docs/sample_files/patient_appointments-sample.pdf) by a printing company.
   The sample files generated are listed below
   a) [patient_appointments.csv](../docs/sample_files/patient_appointments.csv)
   b) [patients_form_print.csv](../docs/sample_files/patients_form_print.csv)
   
2) **Master Patient Files**
   This file contains a master data export for all appointments scheduled during a given day. This data can be shared with State and City Health Departments via a secure channel i.e PHI compliant box account, secure email, etc.
   [patient_appointments_master.csv](../docs/sample_files/patient_appointments_master.csv)

3) **Business Requisition Outbound**
   This file contains Business requests including the number of appointments requested to help facilitate mass testing efforts. The file is used as an input by business liasons of the city/local government to approve/deny testing requests from local businesses.
   [Business_Request_Template.csv](../docs/sample_files/Business_Request_Template.csv)

4) **Business Requistion Inbound**
   The file contains the Business Requests with number of slots approved by business liasons of the city/local government. It allows the system to ingest approvals and enable businesses to schedule appointments for their employees.
   [Business_Request_Template.csv](../docs/sample_files/Business_Request_Template.csv)

### Integrations

##### Generate Printer Files
The following command generates files that are needed to be shared with the printing company and state/local health departments

```
php index.php patientform generate {yyyy-mm-dd}
i.e. php index.php patientform generate 2020-05-05
```

The command will generate the following files at path /var/www/data/{yyyy-mm-dd}

1. Printer files
   * patient_appointments.csv (patient scheduling form). 
   * patients_form_print.csv (patient requistion form).
2. Master Patient File 
   * patient_appointments_master.csv

<br/>

##### Send Printer Files
The following command picks up the printer files from /var/www/data/{yyyy-mm-dd} directory and uses secure ftp to transfer the file to the printing company.

```
php index.php sendcsv sendprint {yyyy-mm-dd}
i.e. php index.php sendcsv sendprint 2020-05-05
```

The following environment variables store the secrets needed for sftp

```
PRINT_SFTP_HOST
PRINT_SFTP_USERNAME
PRINT_SFTP_PASSWORD
```

<br/>

##### Send Master Patient File
The following commands picks up the master patient file from /var/www/data/{yyyy-mm-dd} directory and uses secure smtp to transfer the file to PHI compliant secure email. 

```
php /var/www/html/index.php documentpush index {yyyy-mm-dd}
i.e. php /var/www/html/index.php documentpush index 2020-05-05
```

The secure email usage requires the application to have an active account on a secure email server.

The following environment variables store the secrets for secure email.

```
HEALTH_NETWORK_USER_NAME
HEALTH_NETWORK_USER_PWD
HEALTH_NETWORK_MAIL_FROM
HEALTH_NETWORK_MAIL_TO
HEALTH_NETWORK_SMTP_URL
HEALTH_NETWORK_SMTP_PORT
```
<br/>

##### Send Business Requisition File
The following command generates business requistion files that are needed to be shared with the business liasons/local government for business requesting appointments slots for testing.

```
php index.php businessform generate {fileName} {startDateTime} {endDateTime}
i.e. php index.php businessform generate business_requistion.csv 2020-05-05T11:00:00 2020-05-05T12:00:00
```

##### Receive Business Requisition File
The following command receives business requistion files from the business liasons/local government for business requesting appointments slots for testing and updates the system with approval/decline decisions and slots approved for businesses.
```
php index.php importbusiness insert {csvfile}
i.e. php index.php importbusiness insert business_requsition.csv
```

### Data Transfer to City/Local Goverment
The primary method of transferring application data files to the city/local government for the initial implementation is using a secure [box.com](https://www.box.com) account.
The integration is primarily using [box cli](https://github.com/box/boxcli).

Given below are steps mentioned to enable box cli integration
1) Navigate to box dev console.
2) Create a new custom app --> Enterprise Integration.
3) Choose OAuth 2.0 with JWT.
4) Give your app name.
5) Configuration --> Add and Manage Public Keys --> Generate a public/private key pair.
6) Save the generate json file.(box_config.json)
7) Navigate to the folder and add the app as a collaborator.

Given below is a sample of box cli usage with generated json file
```
npx box configure:environments:add box_config.json
boxdir=$(npx box folders:get {box-folder-id})
npx box files:upload {csvfile} -p $boxdir
npx box files:download {fileId} --destination {localdir}
```

#### AWS Services Utilized
The following system functionality and the corresponding AWS service used is listed below
1) Confirmation Emails to Patients/Business Contacts - [AWS Simple Email Service](https://aws.amazon.com/ses/).
2) Confirmation Texts to Patients/Business Contacts - [AWS Simple Notificaton Service Mobile Notification](https://aws.amazon.com/sns)
3) Data files backup - [AWS S3](https://aws.amazon.com/s3/)
4) Store parameters/secrets needed for the application - [AWS SSM Parameter Store](https://docs.aws.amazon.com/systems-manager/latest/userguide/systems-manager-parameter-store.html)

#### External Services Utilized
The external services utilized are optional, if not set the corresponding services will not be utilized.
1) Address Validation - [Google maps API](https://maps.googleapis.com)
2) Email Address Validation - [Neverbounce](https://neverbounce.com)

#### Utility Scripts
The scripts are provided as a template to extract data from and ingest data into the system. The scripts provided utilize the following methodology
 - Use AWS native services like SES, SNS, S3, etc.
- Utilizes controllers from the application by invoking them via docker runtime

1) scripts/sendEODData.sh

   ```
   sendEODData.sh $1 (optional)
   i.e. sendEODData.sh 2020-05-05
   ```

   The date parameter is optional. If not provided the scripts assumes the next Day.
   The script does the following
   - generates the [printer files](#generate-printer-files) and [master patient file](#generate-printer-files), 
   - [Sends the file to the printer using sftp](#send-printer-files)
   - [Sends the file to the health department using secure email](#send-master-patient-file)
   - uploads the generated files to the city/local government phi compliant box account

2) scripts/uploadPrinterData.sh
   This script uploads the printer files (pdfs) to the local/city government box account.
   The scripts looks for files in folder with the current date in the printers pickup location.(i.e pickbox/2020-05-05)

3) scripts/sendBZInfo.sh
   This script generates the [business requisition file](#send-business-requisition-file) and sends it to the city/local goverment box account. The script is defaulted to extract business requests for the last hour.

4) scripts/receiveBZinfo.sh
   The scripts downloads the approved/denied business requistions from the city/local government and [updates/activates the businesses in the system](#receive-business-requisition-file).
