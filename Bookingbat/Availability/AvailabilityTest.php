<?php
require_once(dirname(__FILE__) . '/Availability.php');
require_once('Booking.php');
class AvailabilityTest extends PHPUnit_Framework_TestCase
{
    function test_ShouldIncrementize()
    {
        $input = array(
            array(
                'start' => '00:00:00',
                'end' => '01:30:00'
            )
        );
        $times = $this->incrementize($input);
        $expected = array(
            array(
                'start' => '00:00:00',
                'end' => '00:30:00'
            ),
            array(
                'start' => '00:30:00',
                'end' => '01:00:00'
            ),
            array(
                'start' => '01:00:00',
                'end' => '01:30:00'
            )
        );
        $this->assertEquals($expected, $times, 'should incrementize time span into half hour segments');
    }

    function test_ShouldIncrementize2()
    {
        $input = array(
            array(
                'start' => '01:00:00',
                'end' => '02:30:00'
            )
        );
        $times = $this->incrementize($input);
        $expected = array(
            array(
                'start' => '01:00:00',
                'end' => '01:30:00'
            ),
            array(
                'start' => '01:30:00',
                'end' => '02:00:00'
            ),
            array(
                'start' => '02:00:00',
                'end' => '02:30:00'
            )
        );
        $this->assertEquals($expected, $times, 'should incrementize time span into half hour segments');
    }

    function test_ShouldIncrementizeHourSegmentsFor2HourAvailability()
    {
        $input = array(
            array(
                'start' => '01:00:00',
                'end' => '03:00:00'
            )
        );
        $times = $this->incrementize($input, 60);
        $expected = array(
            array(
                'start' => '01:00:00',
                'end' => '02:00:00'
            ),
            array(
                'start' => '02:00:00',
                'end' => '03:00:00'
            )
        );
        $this->assertEquals($expected, $times, 'should incrementize time span into one hour segments');
    }

    function test_ShouldIncrementizeHourSegmentsFor1HourAvailability()
    {
        $input = array(
            array(
                'start' => '01:00:00',
                'end' => '02:00:00'
            )
        );
        $times = $this->incrementize($input, 60);
        $expected = array(
            array(
                'start' => '01:00:00',
                'end' => '02:00:00'
            ),
        );
        $this->assertEquals($expected, $times, 'should incrementize time span into one hour segments');
    }

    function test_ShouldIncrementizeHourSegmentsFor1HourAvailabilityStartingAtHalfhourMark()
    {
        $input = array(
            array(
                'start' => '03:30:00',
                'end' => '04:30:00'
            )
        );
        $times = $this->incrementize($input, 60);
        $expected = array(
            array(
                'start' => '03:30:00',
                'end' => '04:30:00'
            ),
        );
        $this->assertEquals($expected, $times, 'should incrementize time span into one hour segments');
    }

    function testShouldRevertToHalfHourIfInvalidIncrements()
    {
        $input = array(
            array(
                'start' => '01:00:00',
                'end' => '02:30:00'
            )
        );
        $times = $this->incrementize($input, 0);
        $expected = array(
            array(
                'start' => '01:00:00',
                'end' => '01:30:00'
            ),
            array(
                'start' => '01:30:00',
                'end' => '02:00:00'
            ),
            array(
                'start' => '02:00:00',
                'end' => '02:30:00'
            )
        );
        $this->assertEquals($expected, $times, 'should revert to half hour segments if invalid increment duration passed in');
    }

    function testShouldNotSkipAheadOnDST()
    {
        date_default_timezone_set('America/New_York');
        $dateBefore = date('m-d-Y');
        shell_exec("date 03-10-2013");
        $input = array(
            array(
                'start' => '01:00:00',
                'end' => '02:30:00'
            )
        );
        $times = $this->incrementize($input, 0);
        $expected = array(
            array(
                'start' => '01:00:00',
                'end' => '01:30:00'
            ),
            array(
                'start' => '01:30:00',
                'end' => '02:00:00'
            ),
            array(
                'start' => '02:00:00',
                'end' => '02:30:00'
            )
        );
        shell_exec("date " . $dateBefore);
        $this->assertEquals($expected, $times, 'should not skip ahead on DST');
    }

    function incrementize($availabilityParams, $duration = null)
    {
        $availability = new Availability($availabilityParams);
        return $availability->incrementize($availabilityParams, $duration);
    }
}