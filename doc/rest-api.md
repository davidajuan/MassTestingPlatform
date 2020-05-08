# REST API

## Introduction

Easy!Appointments exposes a flexible REST API that will enables you to handle all the information of your installations through HTTP requests. The API is using JSON as its data transaction format and features many best practices in order to make the resources easily consumable.

### Activation

Each of the API endpoint routes are deactivated by default. This is to increase security by minimizing the attack surface. Expose these endpoints at your own risk.

To activate/deactivate:
> Edit `src/application/config/routes.php` and uncomment/comment any route.

## Making Requests

The new API (v1) supports [Basic Authentication](https://en.wikipedia.org/wiki/Basic_access_authentication) which means that you will have to send the "Authorization" header with every request you make. **Always use SSL/TLS when making requests to a production installation.** That way you can ensure that no passwords will be stolen during the requests. The API expects the username and password of an admin user.

The API follows the REST structure which means that the client can use various HTTP verbs in order to perform various operations to the resources. For example you should use a GET request for fetching resources, a POST for creating new and PUT for updating existing ones in the database. Finally a DELETE request will remove a resource from the system.

GET requests accept some parameter helpers that enable the sort, search, pagination and minification of the responses information. Take a look in the following examples:

### Search

Provide the `q` parameter to perform a search in the resource.

`http://localhost/api/v1/appointments?q=keyword`

### Sort

Sort the results in ascending (+) or descending (-) direction by providing the the respective sign and the property name to be used for sorting.

`http://localhost/api/v1/appointments?sort=-id,+book,-hash`

You can provide up to three sorting fields which will be applied in the provided order.

### Paginate

Paginate the result by providing the `page` parameter along with the optional `length` parameter that defaults to 20.

`http://localhost/api/v1/appointments?page=1&length=10`

### Minimize

If you need to get only specific values from each JSON resource provide the `fields` GET parameter with a list of the required property names.

`http://localhost/api/v1/appointments?fields=id,book,hash,notes`

### Aggregate

Aggregate related data into result payload by providing the `aggregates` parameter.

`http://localhost/api/v1/appointments?aggregates`

*This parameter is currently only available for appointment resources.*

### Expected Responses

Most of the times the API will return the complete requested data in a JSON string but there are some cases that the responses will contain a simple message like the following:

```json
{
    "code": 404,
    "message": "The requested record was not found!"
}
```

Such simple messages contain the HTTP code and a message stating a problem or a success to an operation.

### Try it out

At this point you can start experimenting with the API and your installation. The following section of this document describes the available resources and how they can be used. Before building your API consumer you can use [cURL](https://en.wikipedia.org/wiki/CURL) or [Postman](https://chrome.google.com/webstore/detail/postman/fhbjgbiflinjbdggehcddcbncdddomop) to try out the API.

Get all the registered appointments:

```bash
curl http://localhost/api/v1/appointments --user username:password
```

Get the data of a customer with ID 34:

```bash
curl http://localhost/api/v1/customers/34 --user username:password
```

Update the name of a category with ID 23:

```bash
curl -H 'Content-Type: application/json' -X PUT -d '{"name": "New Name!"}' http://localhost/api/v1/categories/23 --user username:password
```

Delete the service with ID 15:

```bash
curl -X DELETE http://localhost/api/v1/services/15 --user username:password
```

You can also try the GET requests with your browser by navigating to the respective URLs.

## Resources & URIs

### Availabilities

```json
[
    "09:30",
    "13:00",
    "13:15",
    "14:00"
]
```

- `GET /api/v1/availabilities?providerId=:id&serviceId=:id[&date=:date]` Get the available appointment hours for a specific provider, service and date. The date must be in the following format `Y-m-d` e.g. `2016-07-19`.

### Appointments

```json
{
    "id": 1,
    "book": "2016-07-08 12:57:00",
    "start": "2016-07-08 18:00:00",
    "end": "2016-07-08 18:30:00",
    "hash": "asdf809a8sdf987a9d8f7",
    "notes": "These are some test notes.",
    "customerId": 56,
    "providerId": 4,
    "serviceId": 7,
    "googleCalendarId": 134
}
```

- `GET /api/v1/appointments[/:id]` Get all the appointments or a specific one by providing the ID in the URI.
- `POST /api/v1/appointments` Provide the new appointment JSON in the request body to insert a new record.
- `PUT /api/v1/appointments/:id` Provide the updated appointment JSON in the request body to update an existing record. The ID in the URI is required.
- `DELETE /api/v1/appointments/:id` Remove an existing appointment record.

### Unavailabilities

```json
{
    "id": 1,
    "book": "2016-07-08 12:57:00",
    "start": "2016-07-08 18:00:00",
    "end": "2016-07-08 18:30:00",
    "notes": "These are some test notes.",
    "providerId": 4,
    "googleCalendarId": 474
}
```

- `GET /api/v1/unavailabilities[/:id]` Get all the unavailabilities or a specific one by providing the ID in the URI.
- `POST /api/v1/unavailabilities` Provide the new unavailability JSON in the request body to insert a new record.
- `PUT /api/v1/unavailabilities/:id` Provide the updated unavailability JSON in the request body to update an existing record. The ID in the URI is required.
- `DELETE /api/v1/unavailabilities/:id` Remove an existing unavailability record.

### Customers

```json
{
    "id": 97,
    "firstName": "John",
    "lastName": "Doe",
    "email": "john@doe.com",
    "phone": "0123456789",
    "address": "Some Str. 123",
    "city": "Some City",
    "zip": "12345",
    "notes": "Test customer notes."
}
```

- `GET /api/v1/customers[/:id]` Get all the customers or a specific one by providing the ID in the URI.
- `POST /api/v1/customers` Provide the new customer JSON in the request body to insert a new record.
- `PUT /api/v1/customers/:id` Provide the updated customer JSON in the request body to update an existing record. The ID in the URI is required.
- `DELETE /api/v1/customers/:id` Remove an existing customer record.

### Services

```json
{
    "id": 74,
    "name": "Male Haircut",
    "duration": 60,
    "price": 10.00,
    "currency": "Euro",
    "description": "Male haircut trends.",
    "availabilitiesType": "flexible",
    "attendantsNumber": 1,
    "categoryId": null
}
```

- `GET /api/v1/services[/:id]` Get all the services or a specific one by providing the ID in the URI.
- `POST /api/v1/services` Provide the new service JSON in the request body to insert a new record.
- `PUT /api/v1/services/:id` Provide the updated service JSON in the request body to update an existing record. The ID in the URI is required.
- `DELETE /api/v1/services/:id` Remove an existing service record.

The `availabilitiesType` must be either `flexible` or `fixed`.

### Categories

```json
{
    "id": 5,
    "name": "Test Category",
    "description": "This category includes test services"
}
```

- `GET /api/v1/categories[/:id]` Get all the categories or a specific one by providing the ID in the URI.
- `POST /api/v1/categories` Provide the new category JSON in the request body to insert a new record.
- `PUT /api/v1/categories/:id` Provide the updated category JSON in the request body to update an existing record. The ID in the URI is required.
- `DELETE /api/v1/categories/:id` Remove an existing category record.

### Admins

```json
{
    "id": 143,
    "firstName": "Chris",
    "lastName": "Doe",
    "email": "chris@doe.com",
    "mobile": "012345679-0",
    "phone": "0123456789-1",
    "address": "Some Str. 123",
    "city": "Some City",
    "state": "Some City",
    "zip": "12345",
    "notes": "Test admin notes.",
    "settings":{
        "username": "chrisdoe",
        "notifications": true,
        "calendarView": "default"
    }
}
```

- `GET /api/v1/admins[/:id]` Get all the admins or a specific one by providing the ID in the URI.
- `POST /api/v1/admins` Provide the new admin JSON in the request body to insert a new record.
- `PUT /api/v1/admins/:id` Provide the updated admin JSON in the request body to update an existing record. The ID in the URI is required.
- `DELETE /api/v1/admins/:id` Remove an existing admin record.

### Providers

```json
{
    "id": 143,
    "firstName": "Chloe",
    "lastName": "Doe",
    "email": "chloe@doe.com",
    "mobile": "012345679-0",
    "phone": "0123456789-1",
    "address": "Some Str. 123",
    "city": "Some City",
    "state": "Some State",
    "zip": "12345",
    "notes": "Test provider notes.",
    "services": [
        1,
        5,
        9
    ],
    "settings":{
        "username": "chloedoe",
        "notifications":true,
        "googleSync":true,
        "googleCalendar": "calendar-id",
        "googleToken": "23897dfasdf7a98gas98d9",
        "syncFutureDays":10,
        "syncPastDays":10,
        "calendarView": "default",
        "workingPlan":{
            "monday":{
                "start": "09:00",
                "end": "18:00",
                "breaks":[
                    {
                        "start": "14:30",
                        "end": "15:00"
                    }
                ]
            },
            "tuesday":{
                "start": "09:00",
                "end": "18:00",
                "breaks":[
                    {
                        "start": "14:30",
                        "end": "15:00"
                    }
                ]
            },
            "wednesday":null,
            "thursday":{
                "start": "09:00",
                "end": "18:00",
                "breaks":[
                    {
                        "start": "14:30",
                        "end": "15:00"
                    }
                ]
            },
            "friday":{
                "start": "09:00",
                "end": "18:00",
                "breaks":[
                    {
                        "start": "14:30",
                        "end": "15:00"
                    }
                ]
            },
            "saturday":null,
            "sunday":null
        }
    }
}
```

- `GET /api/v1/providers[/:id]` Get all the providers or a specific one by providing the ID in the URI.
- `POST /api/v1/providers` Provide the new provider JSON in the request body to insert a new record.
- `PUT /api/v1/providers/:id` Provide the updated provider JSON in the request body to update an existing record. The ID in the URI is required.
- `DELETE /api/v1/providers/:id` Remove an existing provider record.

### Secretaries

```json
{
    "id": 143,
    "firstName": "Chris",
    "lastName": "Doe",
    "email": "chris@doe.com",
    "mobile": "012345679-0",
    "phone": "0123456789-1",
    "address": "Some Str. 123",
    "city": "Some City",
    "zip": "12345",
    "notes": "Test secretary notes.",
    "providers": [
        53,
        17
    ],
    "settings":{
        "username":"chrisdoe",
        "notifications": true,
        "calendarView": "default"
    }
}
```

- `GET /api/v1/secretaries[/:id]` Get all the secretaries or a specific one by providing the ID in the URI.
- `POST /api/v1/secretaries` Provide the new secretary JSON in the request body to insert a new record.
- `PUT /api/v1/secretaries/:id` Provide the updated secretary JSON in the request body to update an existing record. The ID in the URI is required.
- `DELETE /api/v1/secretaries/:id` Remove an existing secretary record.

### City Admin

```json
{
    "id": 9,
    "firstName": "jake",
    "lastName": "level",
    "email": "jake@level.com",
    "mobile": "456-456-4566",
    "phone": "123-123-1234",
    "address": "123 st",
    "city": "gaat",
    "state": "MI",
    "zip": "48195",
    "notes": "these are notes",
    "providers": [
        "2"
    ],
    "settings": {
        "username": "jlevel",
        "notifications": false,
        "calendarView": "default"
    }
}
```

- `GET /api/v1/cityadmin[/:id]` Get all the city admins or a specific one by providing the ID in the URI.
- `POST /api/v1/cityadmin` Provide the new city admin JSON in the request body to insert a new record.
- `PUT /api/v1/cityadmin/:id` Provide the updated city admin JSON in the request body to update an existing record. The ID in the URI is required.
- `DELETE /api/v1/cityadmin/:id` Remove an existing city admin record.

### City Business Admin

```json
{
    "id": 10,
    "firstName": "jake",
    "lastName": "lever",
    "email": "jake@lever.com",
    "mobile": "456-456-4567",
    "phone": "123-123-1234",
    "address": "123 test st",
    "city": "gaat",
    "state": "MI",
    "zip": "48323",
    "notes": "these are notes",
    "providers": [
      "2"
    ],
    "settings": {
      "username": "jlever",
      "notifications": false,
      "calendarView": "default"
    }
}
```

- `GET /api/v1/citybusiness[/:id]` Get all the city business admins or a specific one by providing the ID in the URI.
- `POST /api/v1/citybusiness` Provide the new city business admin JSON in the request body to insert a new record.
- `PUT /api/v1/citybusiness/:id` Provide the updated city business admin JSON in the request body to update an existing record. The ID in the URI is required.
- `DELETE /api/v1/citybusiness/:id` Remove an existing city business admin record.

### Settings

```json
{
    "name": "book_advance_timeout",
    "value": "100"
}
```

- `GET /api/v1/settings[/:name]` Get all the settings or a specific one by providing the setting name in the URI.
- `PUT /api/v1/settings/:name` Insert or update a setting in the database. Provide a snake_case name in order to keep the conventions.
- `DELETE /api/v1/settings/:name` Remove a setting from the database. **Notice:** Be careful when removing settings that are required by the application because this will cause error later on.

### Health Check

```text
healthy
```

- `GET /health-check` Check the status of the site with an HTTP OK 200.

### Appointments Left

```json
[
  {
    "appointments_remaining": 2399,
    "days": 7
  }
]
```

- `GET /api/v1/totals/remainingappointments?service_id=[:id]&provider_id=[:id]&days=[:int]` Get the remaining number of appointments.

Provide the `days` parameter to select range from now until `days`

### Metrics

```json
[
  {
    "scheduledToday": 0,
    "scheduledTodayProvider": 0,
    "scheduledTodayPatient": 0,
    "scheduledTodayCIE": 0,
    "availableTotal": 0,
    "bookedTomorrow": 0,
    "bookedTotal": 1,
    "cie_today_business_registered": 0,
    "cie_today_appointment_requested": 0,
    "cie_today_appointment_approved": 0,
    "cie_business_registered": 1,
    "cie_appointment_requested": 50,
    "cie_appointment_approved": 50,
    "cie_patient_scheduled": 0,
    "cie_slots_remaining": 0,
    "cie_slots_occupied": 0,
    "cie_codes_approved": 1,
    "cie_codes_pending": 0,
    "cie_codes_denied": 0
  }
]
```

- `GET /api/v1/metrics?service_id=[:id]&provider_id=[:id]&date=[:YYYY-MM-DD]` Get a number of metrics for the day.

The `date` parameter will default to current date if not provided.

### Patients Anonymous List

```json
[
  {
    "createDate": "2020-05-07 11:54:43",
    "zip": "482",
    "doctorNpi": "5555471764",
    "callerType": "provider"
  },
  {
    "createDate": "2020-05-07 21:08:13",
    "zip": "482",
    "doctorNpi": "5555471764",
    "callerType": "provider"
  }
]
```

- `GET /api/v1/appointmentsanon?service_id=[:id]&provider_id=[:id]&date_start=[:YYYY-MM-DD]&date_end=[:YYYY-MM-DD]` Get a list of patients without any PII.

The `date_start` parameter will default to the oldest past date if not provided.

The `date_end` parameter will default to the latest future date if not provided.

### Business List

```json
[
  {
    "id": "1",
    "business_name": "XYZ Warehouse",
    "owner_first_name": "Test",
    "owner_last_name": "Test",
    "business_phone": "123-456-7489",
    "mobile_phone": "",
    "consent_sms": "0",
    "email": "",
    "consent_email": "0",
    "address": "12340 Test St",
    "city": "Orlando",
    "state": "FL",
    "zip_code": "32824",
    "hash": "619626AB",
    "modified": "2020-05-07 11:52:47",
    "created": "2020-05-07 11:52:47",
    "slots_requested": 50,
    "slots_approved": 50,
    "slots_remaining": 0,
    "slots_occupied": 0,
    "codes_approved": 1,
    "codes_pending": 0,
    "codes_denied": 0
  }
]
```

- `GET /api/v1/business/list` Get a list of all businesses.

## API Roadmap

Although the current state should be sufficient for working with the application data there are some other features of that will make the consume more flexible and powerful. These will be added gradually with the future releases of Easy!Appointments.

[ ] Add auto-generated links whenever external resource IDs are provided.

[ ] Add pagination header links when the client provides pagination parameters.

[ ] Add support for sub-resourcing e.g. /api/v1/customers/:id/appointments must return all the appointments of a specific customer.

[ ] Add custom filtering parameters e.g. /api/v1/appointments?book=>2016-07-10

[ ] Improved exception handling.

Feel free to make pull requests if you have the time to develop one of those.

## Troubleshooting

### Authorization Issues

If your server runs PHP through FastCGI you will the authorization will not work because the `Authorization` header is not available to the PHP scripts. You can easily fix this by applying the following adjustments depending your server software:

### Apache

Add the following code snippet to an `.htaccess` file in the installation root directory if you have `mod_rewrite` installed and enabled:

```apacheconf
RewriteEngine on
RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization},L]
```

[[Source]](http://stackoverflow.com/a/22554102/1718162)

Add the following code snippet to an `.htaccess` file in the installation root directory if you have `mod_setenvif` installed and enabled:

```apacheconf
SetEnvIf Authorization .+ HTTP_AUTHORIZATION=$0
```

[[Source]](http://stackoverflow.com/a/27229807/1718162)

### NGINX

Add the following code snippet to the NGINX `.conf` file:

```apacheconf
fastcgi_param PHP_AUTH_USER $remote_user;
fastcgi_param PHP_AUTH_PW $http_authorization;
```

[[Source]](http://serverfault.com/a/520943)

*This document applies to Mass Testing Platform

[Back](readme.md)
