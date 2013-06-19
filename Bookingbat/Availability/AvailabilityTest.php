<?php
require_once(dirname(__FILE__) . '/Availability.php');
require_once('Booking.php');
class AvailabilityTest extends PHPUnit_Framework_TestCase
{

    function test_WhenHasOneBookingAtStartShouldModifyAvailability()
    {
        $availability = new Availability(array(
            array(
                'start' => '09:00:00',
                'end' => '11:00:00'
            )

        ));
        $availability->addBooking(array('start' => '09:00', 'end' => '09:30'));
        $newAvailability = $availability->getAvailabilityTimes();

        $expected = array(
            array(
                'start' => '09:30:00',
                'end' => '11:00:00'
            )
        );

        $this->assertEquals($expected, $newAvailability, 'when booking at start of the availability, should modify availability to start when booking ends');
    }

    function test_WhenHasOneBookingAtEndShouldModifyAvailability()
    {
        $availability = new Availability(array(
            array(
                'start' => '09:00:00',
                'end' => '11:00:00'
            ),
            array(
                'start' => '11:30:00',
                'end' => '16:00:00'
            ),
        ));
        $newAvailability = $availability->addBooking(array('start' => '15:30', 'end' => '16:00'));

        $expected = array(
            array(
                'start' => '09:00:00',
                'end' => '11:00:00'
            ),
            array(
                'start' => '11:30:00',
                'end' => '15:30:00'
            ),
        );

        $this->assertEquals($expected, $newAvailability, 'when booking at end  of the availability, should modify availability to end when booking starts');
    }

    function testShouldListBookedTimes()
    {
        $availability = new Availability(array(
            array(
                'start' => '09:00:00',
                'end' => '11:00:00'
            ),
            array(
                'start' => '11:30:00',
                'end' => '16:00:00'
            ),
        ));
        $availability->addBooking(array('start' => '15:30', 'end' => '16:00'));
        $actual = $availability->getBookedTimes();
        $expected = array(array(
            'start' => '15:30:00',
            'end' => '16:00:00'
        ));
        $this->assertEquals($expected, $actual, 'should list booked times');
    }

    function test_WhenHasOneBookingInMiddleShouldModifyAvailability()
    {
        $availability = new Availability(array(
            array(
                'start' => '09:00:00',
                'end' => '11:00:00'
            ),
            array(
                'start' => '11:30:00',
                'end' => '16:00:00'
            ),
        ));
        $newAvailability = $availability->addBooking(array('start' => '12:00', 'end' => '12:30'));

        $expected = array(
            array(
                'start' => '09:00:00',
                'end' => '11:00:00'
            ),
            array(
                'start' => '11:30:00',
                'end' => '12:00:00'
            ),
            array(
                'start' => '12:30:00',
                'end' => '16:00:00'
            ),
        );

        $this->assertEquals($expected, $newAvailability, 'when booking is in middle of the availability, should split availability to end at start of booking, and start again at end of booking');
    }

    function test_WhenBookingStartsBeforeAvailability()
    {
        $availability = new Availability(array(
            array(
                'start' => '09:00:00',
                'end' => '09:30:00'
            ),
        ));
        $booking = new \Bookingbat\Availability\Booking(array(
            'start' => '08:30',
            'end' => '09:30'
        ));
        $newAvailability = $availability->addBooking($booking);


        $expected = array();

        $this->assertEquals($expected, $newAvailability, 'when booking begins before availability, and consumes entire availability, should be no more availability');
    }

    function test_WhenBookingExtendsBeyondAvailability()
    {
        $availability = new Availability(array(
            array(
                'start' => '09:00',
                'end' => '09:30'
            ),
        ));
        $booking = array(
            'start' => '09:00',
            'end' => '10:00'
        );
        $newAvailability = $availability->addBooking($booking);


        $expected = array();

        $this->assertEquals($expected, $newAvailability, 'when booking extends beyond availability, and consumes entire availability, should be no more availability');
    }

    function test_WhenBookingStartsBeforeAvailabilityAndExtendsBeyond()
    {
        $availability = new Availability(array(
            array(
                'start' => '09:00',
                'end' => '09:30'
            ),
        ));
        $booking = array(
            'start' => '08:00',
            'end' => '10:00'
        );
        $newAvailability = $availability->addBooking($booking);


        $expected = array();

        $this->assertEquals($expected, $newAvailability, 'when booking starts before available, & extends beyond availability, should be no more availability');
    }

    function test_WhenBookingMatchesAvailability()
    {
        $availability = new Availability(array(
            array(
                'start' => '09:00',
                'end' => '09:30'
            ),
        ));
        $booking = array(
            'start' => '09:00',
            'end' => '09:30'
        );
        $newAvailability = $availability->addBooking($booking);


        $expected = array();

        $this->assertEquals($expected, $newAvailability, 'when booking matches [consumes entire] availability, should be no more availability');
    }

    function test_WhenBookingIsSameAsAvailability()
    {
        $availability = new Availability(array(
            array(
                'start' => '00:30:00',
                'end' => '01:00:00'
            )
        ));
        $booking = array(
            'start' => '00:30:00',
            'end' => '01:00:00'
        );
        $newAvailability = $availability->addBooking($booking);


        $expected = array();

        $this->assertEquals($expected, $newAvailability, 'when booking consumes availability and extends past , should have no more availability');
    }

    function test_WhenBookingIsSameAsAvailabilityAndTheyEndAtMidnight()
    {
        $availability = new Availability(array(
            array(
                'start' => '23:30:00',
                'end' => '00:00:00'
            )
        ));
        $booking = array(
            'start' => '23:30:00',
            'end' => '00:00:00'
        );
        $newAvailability = $availability->addBooking($booking);


        $expected = array();

        $this->assertEquals($expected, $newAvailability, 'when booking consumes availability and they end at midnight should still block off availability');
    }

    function test_WhenHourLongAvailabilitySameAsBooking()
    {
        $availability = new Availability(array(
            array(
                'start' => '03:30:00',
                'end' => '04:30:00'
            )
        ));
        $booking = array(
            'start' => '03:30:00',
            'end' => '04:30:00'
        );
        $newAvailability = $availability->addBooking($booking);


        $expected = array();

        $this->assertEquals($expected, $newAvailability, 'when booking is an hour long and consumes availability should no longer be available');
    }

    function test_WhenBookingConsumesAndExtendsPastAvailability()
    {
        $availability = new Availability(array(
            array(
                'start' => '09:00',
                'end' => '09:30'
            )
        ));
        $booking = array(
            'start' => '09:00',
            'end' => '10:00'
        );
        $newAvailability = $availability->addBooking($booking);


        $expected = array();

        $this->assertEquals($expected, $newAvailability, 'when booking extends past availability, should have no more availability');
    }

    function test_ShouldCompareByTimeNotString()
    {
        $availability = new Availability(array(
            array(
                'start' => '00:30:00',
                'end' => '01:00:00'
            )
        ));
        $booking = array(
            'start' => '00:30',
            'end' => '01:00'
        );
        $newAvailability = $availability->addBooking($booking);


        $expected = array();

        $this->assertEquals($expected, $newAvailability, 'should compare time value, not string value');
    }

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