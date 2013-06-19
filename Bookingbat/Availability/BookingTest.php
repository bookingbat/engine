<?php
require_once('Booking.php');
class BookingTest extends PHPUnit_Framework_TestCase
{
    function testShouldReturnDate()
    {
        $booking = new \Bookingbat\Availability\Booking(array(
            'date' => '2013-02-05'
        ));
        $this->assertEquals('2013-02-05', $booking->date(), 'should return date');
    }

    function testShouldReturnStartTime()
    {
        $booking = new \Bookingbat\Availability\Booking(array(
            'start' => '23:00'
        ));
        $this->assertEquals('23:00:00', $booking->start(), 'should return start time');
    }

    function testShouldSetEndTime()
    {
        $booking = new \Bookingbat\Availability\Booking(array(
            'end' => '23:30'
        ));
        $this->assertEquals('23:30:00', $booking->end(), 'should set specific end time');
    }

    function testShouldInferEndTime()
    {
        $booking = new \Bookingbat\Availability\Booking(array(
            'start' => '23:00'
        ));
        $this->assertEquals('23:30:00', $booking->end(), 'should return end time by adding 30 minutes to start time');
    }

    function testShouldInferEndTimeWhenDurationIs60()
    {
        $booking = new \Bookingbat\Availability\Booking(array(
            'start' => '23:00',
            'duration' => 60
        ));
        $this->assertEquals('00:00:00', $booking->end(), 'should return end time by adding 60 minutes to start time');
    }

    function testShouldNotAllowCancelByClientWithin24Hours()
    {
        $booking = new \Bookingbat\Availability\Booking(array(
            'date' => '2013-04-18',
            'today' => '2013-04-18'
        ));
        $this->assertFalse($booking->allowCancelByUser(), 'should not allow cancel by user within 24hrs');
    }

    function testShouldAllowCancelLostByClientWithin24Hours()
    {
        $booking = new \Bookingbat\Availability\Booking(array(
            'date' => '2013-04-18',
            'today' => '2013-04-18'
        ));
        $this->assertTrue($booking->allowCancelLostByUser(), 'should allow cancel w/ lost credit by user within 24hrs');
    }

    function testShouldAllowCancelByClientBefore24Hours()
    {
        $booking = new \Bookingbat\Availability\Booking(array(
            'date' => '2013-04-18',
            'today' => '2013-04-17'
        ));
        $this->assertTrue($booking->allowCancelByUser(), 'should allow cancel by user before 24hrs from appointment start');
    }

    function testShouldNotAllowCancelByClientAfterAppointment()
    {
        $booking = new \Bookingbat\Availability\Booking(array(
            'date' => '2013-04-18',
            'today' => '2013-04-25'
        ));
        $this->assertFalse($booking->allowCancelByUser(), 'should not allow cancel by user after appointment');
    }

    function testShouldNotAllowCancelLostByClientAfterAppointment()
    {
        $booking = new \Bookingbat\Availability\Booking(array(
            'date' => '2013-04-18',
            'today' => '2013-04-25'
        ));
        $this->assertFalse($booking->allowCancelLostByUser(), 'should not allow cancel w/ lost credit by user after appointment');
    }

    function testShouldConsiderPastAppointmentCompleted()
    {
        $booking = new \Bookingbat\Availability\Booking(array(
            'date' => '2013-04-18',
            'today' => '2013-04-25'
        ));
        $this->assertTrue($booking->completed(), 'should consider past appointment completed');
    }

    function testShouldConsiderCurrentAppointmentPending()
    {
        $booking = new \Bookingbat\Availability\Booking(array(
            'date' => '2013-04-25',
            'today' => '2013-04-25'
        ));
        $this->assertFalse($booking->completed(), 'should consider current appointment pending');
    }

}