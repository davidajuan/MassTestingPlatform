---
layout: default
title: Platform Runbook
description: Overview of Drive Through Testing Process
permalink: /mtp-runbook
---

Mass Testing Platform is a scheduling, logistics and data orchestration application designed to facilitate drive-through testing during the COVID-19 pandemic. This runbook will give you a high-level overview of the drive-through testing process, end-to-end. Please note, this process was designed around the regulations in effect in Detroit, Michigan, during the COVID-19 pandemic. As such, you may need to make adjustments to the process and technology in order to meet the current regulations in your area.

There are four key components to the drive-through testing process that we designed:

- [**Scheduling**](#scheduling) -  A call center handles inbound calls from patients, care providers and businesses to schedule a test. Operating days and hours for a drive-through testing site must be established, along with hourly testing capacity.
- [**Data Orchestration**](#data-orchestration) -  Patient and appointment data from the call center is routed to a printing company for onsite drive-through testing materials, to the testing lab and to a Health Information Network or government agency.
- [**Onsite Testing**](#onsite-testing) - Field agents and medical workers use the onsite drive-through testing materials to verify appointments, confirm eligibility, facilitate the test and prepare the sample to be sent to a lab.
- [**Results**](#results) - Health Department staff and care providers receive test results and notify patients.

## Scheduling

The scheduling process consists of an Interactive Voice Response (IVR) system, call center and scheduling application. The scheduling application was a fork of the [Easy!Appointments](https://easyappointments.org/) open-sourced project, which we extended to meet the needs of drive-through testing. Care providers, individuals, employers and employees all contact the call center directly to schedule or register. A separate landing page and web form was also created to facilitate online self-scheduling. Use the [IVR script](./call-scripts/IVR) to record and configure the necessary prompts for the call center. If a person calls into the call center with a general question, use the [General Call Script](./call-scripts/general).  You can find detailed information on the administration of the scheduling tool in the [Scheduling Runbook](./scheduling-runbook).

### Care Providers

Care providers may call the call center to schedule appointments on a patient's behalf. Call center representatives should leverage the [Care Provider Call Script](../call-scripts/provider) in this flow. They should gather patient, care provider and prescription information and select an appointment day and time. 

### Individuals

After receiving a doctor’s order or prescription, patients contact the call center to schedule an appointment. Call center representatives will leverage the  [Individual Call Script](./call-scripts/individual) in this flow. This flow is similar to the care providers flow above but call center representatives should collect additional information regarding the primary care physician.

### Employers

Employers that wish to have essential employees tested can contact the call center to receive an allotment of testing capacity. Call center representatives will leverage the [Employer Call Script](./call-scripts/employer) in this flow. They’ll gather employer information, along with the number of tests they wish to have allocated to their company. Employers that have requested testing are reviewed on a periodic basis by the approving authority (in our case, this was a team of business liaisons from the city government), and on approval will receive a unique code to provide to their employees to individually schedule appointments.

### Employees

After an employer is approved, and the unique code provided to them is activated, an employee may contact the call center to request testing. To schedule an appointment without a prescription, the employee must have a unique code with available appointments associated to it. Call center representatives will leverage the [Employee Call Script](./call-scripts/employee) in this flow.

## Data Orchestration

The flow and orchestration of patient, employer, scheduling and results information is key to the drive-through testing process. The key files that are output to various entities on a regular basis are covered in this section. In our implementation, we integrated with HIPAA compliant box.com, SSH File Transfer Protocol and Direct Secure Messaging. You can find full details in the [Data Orchestration Runbook](./data-runbook).

### Daily Print File

This file goes to a printing company on a nightly basis. It contains the information needed to print several copies of a master schedule to be used at the testing site the following day, as well as a Patient Requisition form for each scheduled test.  [The Patient Requisition form](./docs/sample_files/Patient_Req_157980DJ.pdf) has a unique barcode on it, along with two integrated labels, which are used to attach patient information to the testing kit bag and vial.

### Daily Patient Information File

This file goes to the Health Information Network and the city (or other governing body) on a daily basis. This file contains all patient and scheduling information, so test results can be associated to the patients’ medical records.

### Employer File

This file contains the employers that have requested test allocation for their employees, along with the number of requested appointments. This file is sent to the city (or other governing body) on an hourly basis to review employers and approve them for testing. The city returns a file of approved employers to have their unique codes activated, allowing employees to schedule appointments through the call center. Alternatively, registered business can be reviewed and approved through the Business Requests screen.

## Onsite Testing

This section covers the three primary stations at the drive-through testing site. You can find full details about the logistics and operations of the drive-through testing site in the  [Testing Site Runbook](./testing-runbook).

### Station 1: Check-In

The check-in attendant will have a copy of the daily appointment schedule, organized by appointment blocks and alphabetically by patient last name within each block. When the patient arrives onsite, the attendant will verify their identity and appointment time and find them on the schedule. Then, the attendant will write the corresponding "car number" listed on the schedule on the patient’s windshield using a washable windshield marker.

### Station 2:  Prescription Verification

The prescription verification attendant will pull the patient requisition form, using the corresponding car number written on the patient’s windshield. This form will indicate whether or not a prescription needs to be physically verified. Then, the attendant will place the patient requisition form under the windshield wiper of the car and direct the patient to the next available testing station.

### Station 3:  Testing

 The testing station is staffed by a medical worker who will collect the patient requisition form from the windshield, confirm the patient’s identity and conduct the test. After the test has been completed, the medical worker will remove two labels from the patient requisition form and apply them to the test vile and specimen bag. Then, they will fold the patient requisition form and place it in the bag along with the test tube. These bags will be stored onsite in a cooler and transported to the lab within the time frame specified by the lab.

## Results

When the test lab receives the samples, they will scan the barcode on the patient requisition form and either manually enter the data from the form or pull the data from the Health Information Network, which patient scheduling data is sent to each evening using the barcode as a key. After the test is complete, results can be transmitted back to the Health Department, and the Health Information Network can be leveraged to match the results to a patient record. The Health Department or the care provider can then call patients to notify them of their results.
