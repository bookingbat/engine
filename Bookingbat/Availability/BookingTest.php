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

}