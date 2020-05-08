# App specific documentation

Documentation here is specific to the app and how we've manipulated it to meet our business needs.

## Roles

| Role  | Abilities  |
|---|---|
| Provider  | Book Appointments, Customer Search (new/edit/delete)  |
| Secretary  | Customer Search (edit)  |
| Admin  | Everything. Book Appointments, Customer Search (new/edit/delete), Calender (delete appointments)  |

| Our Roles  | Role  |
|---|---|
| Rock Central  | Provider  |
| Patient Pre-Checker  | Secretary  |
| Patient Technician  | Secretary  |
| Admin  | Admin  |

## CSV Patient Import

Run this command from CLI:

`php src/index.php importappointmentscli <test|insert> <CsvFile> <ProviderId> <ServiceId>`

* `test|insert` - `test` action to do a dry-run, `insert` action to validate and actually insert/update
* `CsvFile` - relative to the `data` directory
* `ProviderId` - Provider Id you want to insert under
* `ServiceId` - Service Id you want to insert under

If you get an error output, then something went wrong and you need to check data.
If you get nothing back, then it was successful
Database transactions are *ATOMIC*. Any failures are automatically rolled back.
Insertion is all or nothing, there are no partial inserts.

## Business CSV File Import

Run this command from CLI:

`php src/index.php importbusiness <test|insert> <CsvFile>`

* `test|insert` - `test` action to do a dry-run, `insert` action to validate and actually insert/update
* `CsvFile` - Absolute path to file. Note: If file is not in the `data` directory, it will throw an error.

If you get an error output, then something went wrong and you need to check data.
Database transactions are *ATOMIC*. Any failures are automatically rolled back.
Insertion/Updating is conditional based on the existing state of the database and each business request status.

## Business CSV File Generation

### Generate Approval CSV Template

Run this command from CLI:

`php src/index.php businessform generateApproval <Filename> <DateStart> <DateEnd>`

* `Filename` - Filename to use during CSV generation. Files will generate in the `data/business` directory of the app.
* `DateStart` - Set a date range (inclusive) of when businesses made a request to increase slots. Specified in ISO8601 format
* `DateEnd` - Set a date range (inclusive) of when businesses made a request to increase slots. Specified in ISO8601 format

### Generate Master CSV List

Files will generate in the `data` directory of the app followed by a folder of date where the file belongs.
Run this command from CLI:

`php src/index.php businessform generateMaster <Date>`

* `Date` - Specified in format `YYYY-MM-DD`. This is optional and will assume current date if omitted.

## Patient CSV File Generation

Files will generate in the `data` directory of the app followed by a folder of date where the file belongs.

`php src/index.php patientform generate <Date>`

* `Date` - Specified in format `YYYY-MM-DD`. This is optional and will assume current date if omitted.

The following will be generated:

* `patient_appointments.csv` - This is the list of the patient appointments which will be sent to the city. Used for the people on the ground to check patients coming in have appointments

* `patients_form_print.csv` - This is the list of the days appointments which will be sent to the printers (the format is different than the cities)

* `patient_appointments_master.csv` - This is the list of the days appointments which will be an entire dump of user/appointment data we have

* `business_master.csv` - This is the list of businesses that registered on the specified date

## SFTP

To sftp the `patients_form_print.csv` file to the printing company, hit `/sendcsv/sendprint` or `/sendcsv/sendprint/2020-03-27` to be date specific

## Cron Ordering

1. Generate the CSV Files for tomorrows date
    `php src/index.php patientform generate 2020-03-26`
2. Send the CSV Files to printing company
    `php src/index.php sendcsv sendprint 2020-03-26`
3. Email CSV Files to City and Health Network
    `php src/index.php documentpush index 2020-03-26`
