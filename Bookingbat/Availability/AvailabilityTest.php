<?php
require_once(dirname(__FILE__) . '/Availability.php');
require_once(dirname(__FILE__) . '/MassageAvailability.php');
require_once('Booking.php');
class AvailabilityTest extends PHPUnit_Framework_TestCase
{
    function test_WhenHasOneBookingAtStartShouldModifyAvailability()
    {
        $availability = new MassageAvailability(array(
            array(
                'start' => '09:00:00',
                'end' => '11:00:00'
            )

        ));
        $availability->addBooking(array(
            'start' => '09:00',
            'end' => '09:30'
        ));
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
        $availability = new MassageAvailability(array(
            array(
                'start' => '09:00:00',
                'end' => '11:00:00'
            ),
            array(
                'start' => '11:30:00',
                'end' => '16:00:00'
            ),
        ));
        $newAvailability = $availability->addBooking(array(
            'start' => '15:30',
            'end' => '16:00'
        ));

        $expected = array(
            array(
                'start' => '09:00:00',
                'end' => '11:00:00'
            ),
            array(
                'start' => '11:30:00',
                'end' => '15:30:00',
                'is-computed'=>true
            ),
        );

        $this->assertEquals($expected, $newAvailability, 'when booking at end  of the availability, should modify availability to end when booking starts');
    }

    function testShouldListBookedTimes()
    {
        $availability = new MassageAvailability(array(
            array(
                'start' => '09:00:00',
                'end' => '11:00:00'
            )
        ));
        $availability->addBooking(array(
            'start' => '09:00',
            'end' => '10:00'
        ));
        $actual = $availability->getBookedTimes();
        $expected = array(array(
            'start' => '09:00:00',
            'end' => '10:00:00'
        ));
        $this->assertEquals($expected, $actual, 'should list booked times');
    }

    function test_WhenHasOneBookingInMiddleShouldModifyAvailability()
    {
        $availability = new MassageAvailability(array(
            array(
                'start' => '09:00:00',
                'end' => '11:00:00'
            ),
            array(
                'start' => '11:30:00',
                'end' => '16:00:00'
            ),
        ));
        $newAvailability = $availability->addBooking(array(
            'start' => '12:00',
            'end' => '12:30'
        ));

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

    function test_ShouldMergeAdjacentRanges()
    {
        $availability = new MassageAvailability(array(
            array(
                'start' => '09:00:00',
                'end' => '11:00:00'
            ),
            array(
                'start' => '11:00:00',
                'end' => '12:00:00'
            )
        ));

        $newAvailability = $availability->mergeOverlappingRanges();

        $expected = array(
            array(
                'start' => '09:00:00',
                'end' => '12:00:00'
            )
        );
        $this->assertEquals($expected, $newAvailability, 'should merge adjacent ranges');
    }

    function test_ShouldMergeOverlappingRanges()
    {
        $availability = new MassageAvailability(array(
            array(
                'start' => '09:00:00',
                'end' => '11:00:00'
            ),
            array(
                'start' => '10:00:00',
                'end' => '11:30:00'
            )
        ));

        $newAvailability = $availability->mergeOverlappingRanges();

        $expected = array(
            array(
                'start' => '09:00:00',
                'end' => '11:30:00'
            )
        );
        $this->assertEquals($expected, $newAvailability, 'should merge overlapping ranges');
    }

    function test_ShouldMergeOverlappingRanges2()
    {
        $availability = new MassageAvailability(array(
            array(
                'start' => '01:00:00',
                'end' => '03:00:00'
            ),
            array(
                'start' => '02:00:00',
                'end' => '04:00:00'
            )
        ));

        $newAvailability = $availability->mergeOverlappingRanges();

        $expected = array(
            array(
                'start' => '01:00:00',
                'end' => '04:00:00'
            )
        );
        $this->assertEquals($expected, $newAvailability, 'should merge overlapping ranges');
    }

    function test_ShouldMergeUserId()
    {
        $availabilityForUser1 = array(
            array('user_id' => 1, 'start' => '01:00:00', 'end' => '03:00:00'),
        );
        $availabilityForUser2 = array(
            array('user_id' => 2, 'start' => '02:00:00', 'end' => '04:00:00'),
        );

        $availability = new MassageAvailability(array_merge($availabilityForUser1, $availabilityForUser2));
        $newAvailability = $availability->mergeOverlappingRanges();
        $expected = array(
            array(
                'start' => '01:00:00',
                'end' => '04:00:00',
                'user_id' => array(1, 2)
            )
        );
        $this->assertEquals($expected, $newAvailability, 'should merge user IDs for overlapping ranges');
    }

    function test_ShouldMergeUserId2()
    {
        $availability1 = array(
            array('user_id' => array(1, 2), 'start' => '01:00:00', 'end' => '03:00:00'),
        );
        $availability2 = array(
            array('user_id' => 2, 'start' => '02:00:00', 'end' => '04:00:00'),
        );

        $availability = new MassageAvailability(array_merge($availability1, $availability2));
        $newAvailability = $availability->mergeOverlappingRanges();

        $expected = array(
            array(
                'start' => '01:00:00',
                'end' => '04:00:00',
                'user_id' => array(1, 2)
            )
        );
        $this->assertEquals($expected, $newAvailability, 'should merge user IDs for overlapping ranges');
    }

    function test_ShouldMergeWhenSecondAvailabilityEndsBeforeFirst()
    {
        $availability1 = array(
            array('user_id' => array(1, 2), 'start' => '01:00:00', 'end' => '03:00:00'),
        );
        $availability2 = array(
            array('user_id' => 2, 'start' => '02:00:00', 'end' => '02:30:00'),
        );

        $availability = new MassageAvailability(array_merge($availability1, $availability2));
        $newAvailability = $availability->mergeOverlappingRanges();

        $expected = array(
            array(
                'start' => '01:00:00',
                'end' => '03:00:00',
                'user_id' => array(1, 2)
            )
        );
        $this->assertEquals($expected, $newAvailability, 'should merge when second availability ends before first');
    }

    function test_WhenSingleUserShouldGetPossibleUserIdsForBooking()
    {
        $availability = array(
            array('user_id' => 1, 'start' => '01:00:00', 'end' => '03:00:00'),
        );

        $availability = new MassageAvailability($availability);
        $possibleUserIds = $availability->possibleUserIdsForBooking(new \Bookingbat\Availability\Booking(array('start' => '02:00', 'end' => '03:00')));
        $this->assertEquals(array(1), $possibleUserIds);
    }

    function test_ShouldGetPossibleUserIdsForBooking()
    {
        $availabilityForUser1 = array(
            array('user_id' => 1, 'start' => '01:00:00', 'end' => '03:00:00'),
        );
        $availabilityForUser2 = array(
            array('user_id' => 2, 'start' => '02:00:00', 'end' => '04:00:00'),
        );

        $availability = new MassageAvailability(array_merge($availabilityForUser1, $availabilityForUser2));
        $possibleUserIds = $availability->possibleUserIdsForBooking(new \Bookingbat\Availability\Booking(array('start' => '02:00', 'end' => '03:00')));
        $this->assertEquals(array(1, 2), $possibleUserIds);
    }

    function test_ShouldGetPossibleUserIdsForBooking_WhenOneTherapistAlreadyBooked()
    {
        $availabilityForUser1 = array(
            array('user_id' => 1, 'start' => '00:30:00', 'end' => '23:30:00'),
        );
        $availabilityForUser2 = array(
            array('user_id' => 2, 'start' => '19:00:00', 'end' => '22:30:00'),
        );

        $availability = new MassageAvailability(array_merge($availabilityForUser1, $availabilityForUser2));
        $availability->addBooking(new \Bookingbat\Availability\Booking(array('start' => '20:00', 'end' => '20:30', 'user_id' => 2)));

        $possibleUserIds = $availability->possibleUserIdsForBooking(new \Bookingbat\Availability\Booking(array('start' => '20:00', 'end' => '20:30')));
        $this->assertEquals(array(1), $possibleUserIds);
    }

    function test_PossibleUserIdsShouldNotIncludeAvailabilityWindowsSmallerThanRequestedAppointmentLength()
    {
        $availabilityForUser1 = array(
            array('user_id' => 14, 'start' => '09:00:00', 'end' => '21:30:00'),
        );
        $availabilityForUser2 = array(
            array('user_id' => 15, 'start' => '19:00:00', 'end' => '22:30:00'),
        );
        $availability = new MassageAvailability(array_merge($availabilityForUser1, $availabilityForUser2));
        $possibleUserIds = $availability->possibleUserIdsForBooking(new \Bookingbat\Availability\Booking(array('start' => '21:00:00', 'duration' => 90)));
        $this->assertEquals(array(15), $possibleUserIds, 'availability windows shorter than the requested appointment length should not be included in the list of possible user IDs');
    }

    function test_ShouldGetSingleUserId()
    {
        $availabilityForUser1 = array(
            array('user_id' => 1, 'start' => '01:00:00', 'end' => '03:00:00'),
        );
        $availabilityForUser2 = array(
            array('user_id' => 2, 'start' => '02:00:00', 'end' => '04:00:00'),
        );

        $availability = new MassageAvailability(array_merge($availabilityForUser1, $availabilityForUser2));
        $possibleUserIds = $availability->possibleUserIdsForBooking(new \Bookingbat\Availability\Booking(array('start' => '01:00', 'end' => '02:00')));
        $this->assertEquals(array(1), $possibleUserIds);
    }

    function test_ShouldGetSingleUserId_When2ndAvailabilityEndsBeforeFirst()
    {
        $availabilityForUser1 = array(
            array('user_id' => 1, 'start' => '01:00:00', 'end' => '04:00:00'),
        );
        $availabilityForUser2 = array(
            array('user_id' => 2, 'start' => '02:00:00', 'end' => '03:00:00'),
        );

        $availability = new MassageAvailability(array_merge($availabilityForUser1, $availabilityForUser2));
        $possibleUserIds = $availability->possibleUserIdsForBooking(new \Bookingbat\Availability\Booking(array('start' => '03:00', 'end' => '03:30')));
        $this->assertEquals(array(1), $possibleUserIds);
    }

    function test_AddBookingShouldPreserveUserId()
    {
        $availability = new MassageAvailability(array(
            array(
                'start' => '09:00:00',
                'end' => '11:00:00',
                'user_id' => 1
            )
        ));
        $newAvailability = $availability->addBooking(array(
            'start' => '09:00',
            'end' => '09:30'
        ));

        $this->assertEquals(1, $newAvailability[0]['user_id'], 'addBooking() should preserve userID');
    }

    function test_AddBookingShouldPad30MinutesAfter()
    {
        $availability = new MassageAvailability(array(
            array(
                'start' => '09:00:00',
                'end' => '11:00:00',
                'user_id' => 1
            )
        ), array(
            'padding'=>30
        ));
        $newAvailability = $availability->addBooking(array(
            'start' => '09:00',
            'end' => '09:30'
        ));

        $expected = array(
            array(
                'start' => '10:00:00',
                'end' => '11:00:00',
                'user_id' => 1,
            )
        );

        $this->assertEquals($expected, $newAvailability, 'addBooking() should pad 30 minutes after');
    }

    function test_AddBookingShouldPad60MinutesAfter()
    {
        $availability = new MassageAvailability(array(
            array(
                'start' => '09:00:00',
                'end' => '11:00:00',
                'user_id' => 1
            )
        ), array(
            'padding'=>60
        ));
        $newAvailability = $availability->addBooking(array(
            'start' => '09:00',
            'end' => '09:30'
        ));

        $expected = array(
            array(
                'start' => '10:30:00',
                'end' => '11:00:00',
                'user_id' => 1,
            )
        );

        $this->assertEquals($expected, $newAvailability, 'addBooking() should pad 60 minutes after');
    }

    function test_ShouldAddExtra30MinutesAfterBooking()
    {
        $availability = new MassageAvailability(array(
            array(
                'start' => '09:00:00',
                'end' => '11:00:00'
            )

        ), array(
            'padding'=>30
        ));
        $newAvailability = $availability->addBooking(array(
            'start' => '09:00',
            'end' => '09:30'
        ));

        $expected = array(
            array(
                'start' => '10:00:00',
                'end' => '11:00:00',
            )
        );

        $this->assertEquals($expected, $newAvailability, 'should padd an extra 30m after booking to allow massage therapist to commute to new condos');
    }

    function test_ShouldAddExtra30MinutesBeforeBooking()
    {
        $availability = new MassageAvailability(array(
            array(
                'start' => '09:00:00',
                'end' => '11:00:00'
            )

        ), array(
            'padding'=>30,
            'minimum_booking_duration'=>1
        ));
        $newAvailability = $availability->addBooking(array(
            'start' => '10:00',
            'end' => '11:00'
        ));
        $this->assertEquals(array(), $newAvailability, 'Should not allow two 2-hour sessions in a 2-hour window due to required 30m padding between appointments');
    }


    function test_WhenTwoTherapistsBookingOneShouldLeaveOtherAvailable()
    {
        $availabilityForUser1 = array(
            array('user_id' => 1, 'start' => '01:00:00', 'end' => '04:00:00'),
        );
        $availabilityForUser2 = array(
            array('user_id' => 2, 'start' => '02:00:00', 'end' => '03:00:00'),
        );

        $availability = new MassageAvailability(
            array_merge($availabilityForUser1, $availabilityForUser2),
            array(
                'padding'=>30,
                'minimum_booking_duration'=>1
            ));
        $newAvailability = $availability->addBooking(array(
            'start' => '02:00',
            'end' => '02:30',
            'user_id' => 1
        ));

        $expected = array(
            array(
                'start' => '03:00:00',
                'end' => '04:00:00',
                'user_id' => 1,
            ),
            array(
                'start' => '02:00:00',
                'end' => '03:00:00',
                'user_id' => 2
            )
        );

        $this->assertEquals($expected, $newAvailability, 'when two therapists are available, and then one is booked, that booking should not affect the other\'s availability');
    }

    function test_WhenOneTherapistBookedAndSecondIsAvailableShouldMerge()
    {
        $availabilityForUser1 = array(
            array('user_id' => 1, 'start' => '01:00:00', 'end' => '04:00:00'),
        );
        $availabilityForUser2 = array(
            array('user_id' => 2, 'start' => '01:00:00', 'end' => '04:00:00'),
        );

        $availability = new MassageAvailability(
            array_merge($availabilityForUser1, $availabilityForUser2),
            array(
                'padding'=>30,
                'minimum_booking_duration'=>1
            ));
        $availabilityArray = $availability->addBooking(array(
            'start' => '02:00',
            'end' => '02:30',
            'user_id' => 1
        ));

        $newAvailability = $availability->mergeOverlappingRanges();

        $expected = array(
            array(
                'start' => '01:00:00',
                'end' => '04:00:00',
                'user_id' => array(1, 2),
                //'is-computed'=>true,
            ),
        );

        $this->assertEquals($expected, $newAvailability, 'when one therapist is booked out of two available, merging should reflect that the other is still available');
    }

    function test_WhenBothTherapistsBookedShouldMerge()
    {
        $availabilityForUser1 = array(
            array('user_id' => 1, 'start' => '01:00:00', 'end' => '04:00:00'),
        );
        $availabilityForUser2 = array(
            array('user_id' => 2, 'start' => '02:00:00', 'end' => '03:30:00'),
        );

        $availability = new MassageAvailability(
            array_merge($availabilityForUser1, $availabilityForUser2),
            array(
                'padding'=>30,
                'minimum_booking_duration'=>1
            ));
        $availability->addBooking(array(
            'start' => '02:00',
            'end' => '02:30',
            'user_id' => 1
        ));
        $availabilityArray = $availability->addBooking(array(
            'start' => '02:00',
            'end' => '02:30',
            'user_id' => 2
        ));

        $newAvailability = $availability->mergeOverlappingRanges();

        $expected = array(
            array(
                'start' => '03:00:00',
                'end' => '04:00:00',
                'user_id' => array(1, 2),
            ),
        );

        $this->assertEquals($expected, $newAvailability, 'when both therapists booked should merge');
    }

    function test_Bug1()
    {
        $availabilityForUser1 = array(
            array('user_id' => 1, 'start' => '00:30:00', 'end' => '23:30:00'),
        );
        $availabilityForUser2 = array(
            array('user_id' => 2, 'start' => '19:00:00', 'end' => '22:30:00'),
        );

        $availability = new MassageAvailability(
            array_merge($availabilityForUser1, $availabilityForUser2),
            array(
                'padding'=>30,
                'minimum_booking_duration'=>1
            ));
        $availabilityArray = $availability->addBooking(array(
            'start' => '20:00',
            'user_id' => 2
        ));

        $newAvailability = $availability->mergeOverlappingRanges();

        $expected = array(
            array(
                'start' => '00:30:00',
                'end' => '23:30:00',
                'user_id' => array(1, 2)
            ),
        );

        $this->assertEquals($expected, $newAvailability);
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
            '00:00:00',
            '00:30:00',
            '01:00:00',
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
            '01:00:00',
            '01:30:00',
            '02:00:00',
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
            '01:00:00',
            '02:00:00',
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
            '01:00:00',
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
            '03:30:00',
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
            '01:00:00',
            '01:30:00',
            '02:00:00',
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
            '01:00:00',
            '01:30:00',
            '02:00:00',
        );
        shell_exec("date " . $dateBefore);
        $this->assertEquals($expected, $times, 'should not skip ahead on DST');
    }

    function testShouldNotIncrementizeLastHalfHourBlock()
    {
        $input = array(
            array(
                'start' => '01:00:00',
                'end' => '02:30:00'
            )
        );
        $times = $this->incrementize($input, 0);
        $expected = array(
            '01:00:00',
            '01:30:00',
            '02:00:00'
        );
        $this->assertEquals($expected, $times, 'should not show last 30m block as available, to enforce time between appointments!');
    }

    function testShouldMakeRoomFor90MinuteAppointment()
    {
        $input = array(
            array(
                'start' => '01:00:00',
                'end' => '04:00:00'
            )
        );
        $availability = new MassageAvailability($input,
            array(
                'padding'=>30
            ));
        $availabilityArray = $availability->addBooking(array(
            'start' => '01:00:00',
            'duration' => 90
        ));
        $times = $this->incrementize($availabilityArray, 0, 90);
        $this->assertEquals(array(), $times, 'when available from 1-4am, has an appointment from 1-2:30am cannot schedule 90m appointment in remaining window of 2:30-4:00 because of extra 30m travel');
    }

    function testShouldSqueezeIn90MinuteAppointmentAtEndOfAvailability()
    {
        $input = array(
            array(
                'start' => '01:00:00',
                'end' => '05:00:00'
            )
        );
        $availability = new MassageAvailability($input,
            array(
                'padding'=>30
            ));
        $availabilityArray = $availability->addBooking(array(
            'start' => '01:00:00',
            'duration' => 90
        ));
        $times = $this->incrementize($availabilityArray, 0, 90);
        $expected = array(
            '03:00:00',
            '03:30:00'
        );
        $this->assertEquals($expected, $times, 'when available from 1-5am, has an appointment from 1-2:30am can schedule a 90m appointment at 3am or 3:30am');
    }

    function testShouldNotSqueezeIn90MinuteAppointmentAtEndOfAvailabilityWithPadding()
    {
        $input = array(
            array(
                'start' => '01:00:00',
                'end' => '04:30:00'
            )
        );
        $availability = new MassageAvailability($input,array('padding'=>30));
        $availabilityArray = $availability->addBooking(array(
            'start' => '01:00:00',
            'duration' => 90
        ));
        $times = $this->incrementize($availabilityArray, 0, 90);
        $expected = array('03:00:00');
        $this->assertEquals($expected, $times, 'when available from 1-4:30am, and has an appt. from 1-2:30am, should allow 90m apt from 3-4:30am');
    }

    function testShouldSqueezeIn60MinuteAppointmentAtEndOfAvailability()
    {
        $input = array(
            array(
                'start' => '01:00:00',
                'end' => '04:30:00'
            )
        );
        $availability = new MassageAvailability($input,
            array(
                'padding'=>30
            ));
        $availabilityArray = $availability->addBooking(array('start' => '01:00:00', 'duration' => 90));
        $times = $this->incrementize($availabilityArray, 0, 60);
        $expected = array(
            '03:00:00',
            '03:30:00'
        );
        $this->assertEquals($expected, $times, 'when availabile from 1-4:30am, has an apt. from 1-2:30am allow 60m apt at 3, or 3:30am');
    }

    function testShouldMakeRoomFor60MinuteAppointment()
    {
        $input = array(
            array(
                'start' => '01:00:00',
                'end' => '05:00:00'
            )
        );
        $availability = new MassageAvailability($input,
            array(
                'padding'=>30
            ));
        $availabilityArray = $availability->addBooking(array('start' => '01:00:00', 'duration' => 90));
        $times = $this->incrementize($availabilityArray, 0, 60);
        $expected = array(
            '03:00:00',
            '03:30:00',
            '04:00:00'
        );
        $this->assertEquals($expected, $times, 'when available from 1-5am, has an apt. from 1-2:30am, should allow 60m apt at 3am, 3:30am, or 4am');
    }

    function testShouldMakeRoomFor90MinuteAppointment2()
    {
        $input = array(
            array(
                'start' => '01:00:00',
                'end' => '7:00:00'
            )
        );
        $availability = new MassageAvailability($input,
            array(
                'padding'=>30,
                'minimum_booking_duration'=>1
            ));
        $availabilityArray = $availability->addBooking(array('start' => '03:30:00', 'duration' => 90));

        $times = $this->incrementize($availabilityArray, 0, 90);

        $expected = array(
            '01:00:00',
            '01:30:00',
            '05:30:00',

        );
        $this->assertEquals($expected, $times, 'when available from 1am-7am, has an apt. from 3:30am-5am, should allow 90m apt at 1-2:30am, 1:30-3am, or 5:30am-7am');
    }

    function testShouldFit60mAppointmentIn60MSlot()
    {
        $input = array(
            array(
                'start' => '01:00:00',
                'end' => '02:00:00'
            )
        );
        $availability = new MassageAvailability($input,
            array(
                'padding'=>30
            ));

        $times = $this->incrementize($input, 0, 60);
        $expected = array('01:00:00');
        $this->assertEquals($expected, $times, 'when availabile from 1-2am, should fit a 60m appointment in');
    }

    function testBug2()
    {
        $input = array(
            array(
                'start' => '00:30:00',
                'end' => '23:30:00'
            )
        );
        $times = $this->incrementize($input, 0, 60);
        $this->assertEquals('22:30:00', $times[count($times) - 1], 'when ends at 11:30pm should list times');
    }

    function testBug3()
    {
        $input = array(
            array(
                'start' => '21:00:00',
                'end' => '22:30:00'
            )
        );
        $availability = new MassageAvailability($input,array('padding'=>30));
        $availabilityArray = $availability->addBooking(array('start' => '21:30:00', 'duration' => 60));
        $expected = array();
        $this->assertEquals($expected, $availabilityArray, 'should mark out availability when booking & availability end at same time');
    }

    function testBug4()
    {
        $input = array(
            array(
                'start' => '19:00:00',
                'end' => '22:30:00'
            )
        );
        $availability = new MassageAvailability($input,
            array(
                'padding'=>30,
                'minimum_booking_duration'=>1
            ));
        $availabilityArray = $availability->addBooking(array('start' => '21:30:00', 'duration' => 60));
        $times = $this->incrementize($availabilityArray, 0, 60);
        $expected = array(
            '19:00:00',
            '19:30:00',
            '20:00:00'
        );
        $this->assertEquals($expected, $times, 'should not allow an appointment to be booked at 8:30 because it doesn\'t allow the 30m padding before the 9:30 appointment!');
    }

    function testBug5()
    {
        $input = array(
            array(
                'start' => '19:00:00',
                'end' => '22:30:00'
            )
        );
        $availability = new MassageAvailability($input,
            array(
                'padding'=>30
            ));
        $availabilityArray = $availability->addBooking(array(
            'start' => '20:00:00',
            'duration' => 90
        ));
        $this->assertEquals(array(), $availabilityArray, 'should not allow appointments smaller than the minimum length');
    }

    function incrementize($availabilityParams, $duration = null, $lengthOfAppointmentToMake = null)
    {
        $availability = new MassageAvailability($availabilityParams);
        return $availability->incrementize($availabilityParams, $duration, $lengthOfAppointmentToMake);
    }
}