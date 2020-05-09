---
layout: page
title: Scheduling Administration
description: Administration of the scheduling system
permalink: /scheduling-runbook
---

This runbook provides a guide to the adminstration of the Mass Testing Platform.

The following sections will be covered:

- [Initial Setup](#initial-setup)
- [Creating a Testing Site](#creating-a-testing-site)
- [Creating a Working Plan](#creating-a-working-plan)
- [Creating a Testing Service](#creating-a-testing-service)
- [Booking an Appointment](#booking-an-appointment)
- [Registering a Business](#registering-a-business)
- [Reviewing and Approving a Business](#reviewing-and-approving-a-business)
- [Bulk Scheduling and Business Files](#bulk-scheduling-and-business-files)
- [Reporting](#reporting)

## Initial Setup

Follow the steps in the [Installation Guide]().  To complete the remaining steps, use the admin account account created during installation.

## Creating a Testing Site

A "Site" acts as a testing location in the Mass Testing Platform.  To create a testing site, follow these steps:

- From the Users screen, select Add on the Sites tab
- For the sake of creating a testing location, use the First Name and Last Name to name the site.  In our instance, the First Name was "Michigan State" and the Last Name was "Fairgrounds"
- Complete the remaining required fields and hit save to finish creating a testing site

## Creating a Working Plan

A "Working Plan" establishes operating days and hours for the testing site.  To create a working plan, follow these steps:

- From the Users screen, select a site that you wish to create a working plan for from the Sites tab
- Select Working Plan under Current View, and then Edit
- Select days that the site will be open, and add Start and End times for each day
- Add breaks for each day to allow time for lunches and shift changes
- Hit save to complete the working plan

## Creating a Testing Service

"Services" are the testing services that will be offered at the site.  Services determine the duration of appointment windows, and the number of tests allowed per window.  Multiple services can be leveraged for various types of testing, or dedicated capacity for a specified group.  There are three primary services that will need to be created, one for Patients, one for Critical Infrastructure Workers, and one for Priority businesses, in addition to any other testing services or capacity necessary.  To create a service, follow these steps:

- From the Services screen, select Add on the Services tab to create a service
- Name the service, and set a duration for the appointment windows.  We used 60 minute durations to create 1 hour blocks throughout the day
- Price can be set to 0.00
- Attendants Number should be set to the number of appointments you wish to allow per appointment window
- All other information can be left blank or with the default selections
- Hit save to finish adding a testing service

## Booking an Appointment

Appointments are scheduled by a call center representative using the Book an Appointment screen.  Appointments can be scheduled for a patient, by a care provider on behalf of a patient, or by a Critical Infrastructure Worker.  You may wish to change which fields are required based on the Caller selection.  This will need to be done through updates to either the UI or backend code.  To book an appointment, follow these steps:

- From the Book an Appointment screen, select Patient, Provider, or Critical Infrastructure Employees (C.I.E.) as the Caller type
- Complete all required information.  C.I.E. appointments will require the use of a qualified Business Code
- Select an appointment date and time from the available slots listed
- Hit Submit to save the appointment, and confirm details on the Appointment Confirmation page

## Registering a Business

Businesses may request to have testing appointments approved for their employees through the call center.  Businesses are registered through the Business screen, and reviewed and approved in the process covered in the next section.  To register a business, follow these steps:

- From the Business screen, complete all required business information, and enter the number of requested appointments 
- Hit Submit to save the business request

## Reviewing and Approving a Business

Registered business can be reviewed and approved through the Business Requests screen.  To review and approve/deny a business for a set number of appointments, follow these steps:

- From the Business Requests screen, you can either search for a specific business, or filter by Pending, Active, Denied, or Clear statuses.  
- Select a business, and enter the number of appointments you wish to approve them for (leave blank if you will be denying the business)
- Select the check box to approve, or no symbol to deny the business
- Once the Business is approved, the Business Code can now be used by employees to schedule appointments.

## Bulk Scheduling and Business Files

Appointments and business approvals can be handled through bulk scheduling files that are either uploaded or ingested through an automated process. Importing appointments in this manner 
 will bypass the attendants number settings, so use with care to manage your location's testing capacity.

- The [New Patient Sample File](./sample_files/New_Appointment_Template.csv) is used for uploading new patients.  It can be uploaded from the Upload CSV screen.
- The [Retest Sample File](./sample_files/Retest_Template.csv)  is used for uploading rescheduled or subsequent tests for patients.  It can be uploaded from the Upload CSV screen.
- The [Business Request Sample File](./sample_files/Business_Request_Template.csv) is used for uploading business approval files.  This file is ingested through an automated process that is described in the Data Runbook.

## Reporting

Reports can be generated from the reports screen.  There are different types of reports, which are customized in the application. You can select a start/end date for a report and a csv file with the customized data will be generated.