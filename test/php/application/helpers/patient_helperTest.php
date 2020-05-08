<?php

class patient_helperTest extends PHPUnit_Framework_TestCase {

    public function setUp()
    {
        // TODO: Ghetto for now. PHP Namespacing is wack in this app.
        // There's a better way but this works for now
        $file = realpath(__DIR__ . '/../../../../src/application/helpers/patient_helper.php');
        require_once $file;
    }


    public function tearDown()
    {

    }

    public function testFormatPatientIdMatch()
    {
        $expected = '1234-5678-9012';
        $input = '123456789012';

        $actual = format_patient_id($input);

        $this->assertEquals($expected, $actual);
    }

    public function testFormatPatientIdNoMatch()
    {
        $expected = '1234';
        $input = '1234';

        $actual = format_patient_id($input);

        $this->assertEquals($expected, $actual);
    }

    public function testFormatPatientIdNull()
    {
        $expected = null;
        $input = null;

        $actual = format_patient_id($input);

        $this->assertEquals($expected, $actual);
    }

    public function testSanitizePatientIdNormal()
    {
        $expected = '1234567890';
        $input = ' 12a34@5C6Dv7890&  %';

        $actual = sanitize_patient_id($input);

        $this->assertEquals($expected, $actual);
    }

    public function testSanitizePatientIdNull()
    {
        $expected = null;
        $input = null;

        $actual = sanitize_patient_id($input);

        $this->assertEquals($expected, $actual);
    }
}
