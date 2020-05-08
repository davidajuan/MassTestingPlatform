# Mass Testing Platform [![Code Igniter](https://img.shields.io/badge/CodeIgniter-v3.1.6-red)](https://codeigniter.com) [![PHP](https://img.shields.io/badge/PHP-v7.0-green)](https://www.php.net) [![Node](https://img.shields.io/badge/Node-v8.6.0-green)](https://nodejs.org) [![License GPLv3](https://img.shields.io/badge/license-GPLv3-blue)](https://www.gnu.org/licenses/gpl-3.0.en.html) [![COVID-19](https://img.shields.io/badge/virus-free-brightgreen)](https://www.cdc.gov/coronavirus/2019-ncov/index.html)

Healthcare Appointment & Scheduling Web Application. Provides an easy way to sign up as a patient or provider to facilitate COVID-19 tests in the metro Detroit area. Powered by the FOC and [Easy!Appointments](https://github.com/alextselegidis/easyappointments).

## Getting Started

These instructions will get you a copy of the project up and running on your local machine for development and testing purposes. See deployment for notes on how to deploy the project on a live system.

### Prerequisites

Software that is needed to get started:

* Docker
* Git

### Installation

#### Initial Steps

1) Clone the repository to your local machine
    * `git clone https://github.com/QuickenLoans/MassTestingPlatform`
2) Enter the newly created directory
    * `cd mass-testing-platform`
3) Setup environment file
    * `cp .sample.env .env`
    * Edit `.env` with appropriate values
4) Build the docker containers (may take some time to initialize)
    * `docker-compose build --build-arg CERT_URL=`
5) Stand up the docker container
    * `docker-compose up`
6) Open up a **new terminal** and get into the container
    * `docker exec -it mtp_app bash`
7) Build application
      1) Easy All-in-One Build Script
          * `bin/build.sh`
      2) Manual steps (if you don't want to use `build.sh`)
          1) Run *composer* to install PHP packages
              * `composer install`
          2) Run *npm* to install Node packages
              * `nvm install 8.6.0`
              * `nvm use 8.6.0`
              * `npm install`
          3) Run *gulp* to build css
              * `npm rebuild node-sass`
              * `gulp sass`
          4) Run initial db migration
              * `php src/index.php migrate update`
8) Restart Docker Container (Needed to remount php and node modules)
      1) `Ctrl+C` in your docker-compose terminal to kill instance
      2) Re-run `docker-compose up` in your docker-compose terminal
      3) Re-run `docker exec -it mtp_app bash` in your other terminal
9) Visit [http://localhost/installation/index](http://localhost/installation/index) in your browser!
    * Follow the on screen installation guide


#### Reoccurring Steps (After initial install)

1) Stand up the docker container
    * `docker-compose up`
2) Open up a **new terminal** and get into the container
    * `docker exec -it mtp_app bash`
3) Build application with All-in-One Build Script
    * `bin/build.sh`

4) Develop and Engineer!
5) Finish and kill your instance
    * `Ctrl+C` in your docker-compose terminal to kill instance

#### MySQL Admin App (Adminer)

This environment comes with Adminer, a web app to manage the local MySQL database.

After starting the docker containers, visit the following URL with password: `veryhardpassword`

<http://localhost:8080/?server=database&username=easyapp&db=easyapp>

#### Troubleshooting

If you are having issues with your environment consider the following:

1) Have you `fetched` and `pulled` down the `latest` master branch?
2) Have you built the app lately? Run `./bin/build.sh` in the container.
3) You can run `docker-compose build --no-cache --build-arg CERT_URL=` to force rebuild the containers.
4) As a last resort, you can completely delete and start over using `docker rm -v mtp_database`. Use only if all options have been tried because this deletes the existing database.

## Running the tests

### Unit Testing

>WIP

### Integration Testing

>WIP

### End to End (E2E) Testing

>WIP

## Docker (Fork Notes)

To start over and delete the existing database (fresh install), run:

```bash
docker rm -v mtp_database
```

To start Easy!Appointments using Docker in development configuration, with source files mounted into container, run:

```bash
docker-compose up
```

Production deployment can be made by changing required values in .env file (DB_PASSWORD, APP_URL, APP_PORT) and running:

```bash
docker-compose -f docker-compose.prod.yml up -d
```

Database data will be stored in named volume `easyappointments_easy-appointments-data`, and app storage (logs, cache, uploads) in `easyappointments_easy-appointments-storage`.
To find where exactly they are stored, you can run:

```bash
docker volume inspect easyappointments_easy-appointments-storage
```

Production containers will automatically be restarted in case of crash / server reboot. For more info, take a look into `docker-compose.prod.yml` file.

### Migration

#### Updating your Verison

##### In order to seamlessly upgrade your instance, run the following

```bash
# Run the following in the docker container
# Perform migration command
php src/index.php migrate update

# You will see a successful message
# {"status":"success","message":"Version is now 13"}
```

##### In order to migrate to particular version, run the following

```bash
# Run the following in the docker container
php src/index.php migrate updateTo {version_number}
```

#### Checking your Version

##### In order to check the current version of your instance

Use this URL: <http://localhost/migrate>

```bash
# Use the CLI
php src/index.php migrate
# {"expectedVersion":13,"actualVersion":"12"}
```



## Built With

* [easyappointments.org](http://easyappointments.org) - The web schedule app we forked from

## Notes from Fork

![easyappointments banner](https://easyappointments.org/img/easyappointments-banner.png)

### Organize your business! Exploit human resources that can be used in other tasks more efficiently

**Easy!Appointments** is a highly customizable web application that allows your customers to book
appointments with you via the web. Moreover, it provides the ability to sync your data with
Google Calendar so you can use them with other services. It is an open source project and you
can download and install it **even for commercial use**. Easy!Appointments will run smoothly with
your existing website, because it can be installed in a single folder of the server and of course,
both sites can share the same database.

You will find the latest release at [easyappointments.org](http://easyappointments.org).
If you have problems installing or configuring the application take a look on the
[wiki pages](https://github.com/alextselegidis/easyappointments/wiki) or visit the
[official support group](https://groups.google.com/forum/#!forum/easy-appointments).
You can also report problems on the [issues page](https://github.com/alextselegidis/easyappointments/issues)
and help the development progress.

### User Feedback

Whether it is new ideas or defects, your feedback is highly appreciated and will be taken into
consideration for the following releases of the project. Share your experience and discuss your
thoughts with other users through communities. Create issues with suggestions on new features or
bug reports.

### Translate Easy!Appointments

As of version 1.0 Easy!Appointments supports translated user interface. If you want to contribute to the
translation process read the [get involved](https://github.com/alextselegidis/easyappointments/blob/master/doc/get-involved.md)
page for additional information.
