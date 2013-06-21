<?php
/**
 * SEE AvailabilityTest.php FOR UNIT TESTS SHOWING EXTENSIVE EXAMPLE INPUTS & OUTPUTS
 *
 *
 * takes all availability and events and comes out w/ the actual availability
 * Ex: Trainer has recurring availability from 1:00 to 3:00 but an appt from 1:30 to 2:30,
 * after this function, trainer has availability from 1:00 to 1:30 and 2:30 to 3:00
 *
 */
require_once(__DIR__.'/Booking.php');

class Availability
{
    protected $availability;
    protected $periodOfAvailability;
    protected $bookings = array();
    protected $padding=0;
    protected $booking;
    protected $newAvailability;

    function __construct($availability=array(), $options = array())
    {
        $this->availability = $availability;
        foreach($options as $option=>$value) {
            switch($option) {
                case 'padding':
                    $this->padding = $value;
            }
        }
    }

    function addBooking($booking)
    {
        $this->booking = $booking;
        if (is_array($this->booking)) {
            $this->booking = new \Bookingbat\Availability\Booking($this->booking);
        }
        $this->newAvailability = array();
        foreach ($this->availability as $this->periodOfAvailability) {
            $this->periodOfAvailability['start'] = $this->format($this->periodOfAvailability['start']);
            $this->periodOfAvailability['end'] = $this->format($this->periodOfAvailability['end']);

            if ($this->bookingConsumesAvailability()) {
                continue;
            } else if ($this->bookingAtStartOfAvailability()) {
                // when booking at start of the availability
                // should modify availability to start when booking ends
                $this->modifyAvailabilityToStartWhenBookingEnds();
            } else if ($this->bookingAtEndOfAvailability()) {
                // when booking at end  of the availability
                //should modify availability to end when booking starts'
                $this->newAvailability[] = array(
                    'start' => $this->periodOfAvailability['start'],
                    'end' => $this->booking->start()
                );
            } else if ($this->bookingInMiddleOfAvailability()) {
                // when booking is in middle of the availability
                // should split availability to end at start of booking, and start again at end of booking
                $this->splitAvailabilityAroundBooking();
            } else {
                // when no bookings during this period, return period unmodified
                $this->newAvailability[] = $this->periodOfAvailability;
            }
        }
        $this->availability = $this->newAvailability;
        array_push($this->bookings, $this->booking);
        return $this->availability;
    }

    function bookingConsumesAvailability()
    {
        return $this->booking->start() <= $this->periodOfAvailability['start'] && $this->booking->end() >= $this->periodOfAvailability['end'];
    }

    function bookingAtStartOfAvailability()
    {
        return $this->booking->start() == $this->periodOfAvailability['start'];
    }

    function bookingAtEndOfAvailability()
    {
        return $this->booking->end() == $this->periodOfAvailability['end'];
    }

    function bookingInMiddleOfAvailability()
    {
        return $this->booking->start() > $this->periodOfAvailability['start'] && $this->booking->end() < $this->periodOfAvailability['end'];
    }

    function bookingEntirelyAfterAvailability()
    {
        return $this->booking->start() > $this->periodOfAvailability['start'] && $this->booking->end() >= $this->periodOfAvailability['end'];
    }

    function modifyAvailabilityToStartWhenBookingEnds()
    {
        $this->newAvailability[] = array(
            'start' => $this->booking->end(),
            'end' => $this->periodOfAvailability['end'],
            'user_id' => $this->periodOfAvailability['user_id']
        );
    }

    function modifyAvailabilityToEndWhenBookingStarts()
    {
        $this->newAvailability[] = array(
            'is-computed' => true,
            'start' => $this->periodOfAvailability['start'],
            'end' => $this->booking->start(),
            'user_id' => $this->periodOfAvailability['user_id']
        );
    }

    function splitAvailabilityAroundBooking()
    {
        if(!array_key_exists('user_id',$this->periodOfAvailability)) {
            $this->newAvailability[] = array(
                'start' => $this->periodOfAvailability['start'],
                'end' => $this->booking->start()
            );
            $this->newAvailability[] = array(
                'start' => $this->booking->end(),
                'end' => $this->periodOfAvailability['end']
            );
        } else {

            $wouldLeaveOpeningSmallerThanMinimumBefore = $this->booking->start() - $this->periodOfAvailability['start'] <= 1;
            if ($this->periodOfAvailability['user_id'] == $this->booking->userId() && !$wouldLeaveOpeningSmallerThanMinimumBefore) {
                $this->newAvailability[] = array(
                    'is-computed' => true,
                    'start' => $this->periodOfAvailability['start'],
                    'end' => $this->booking->start(),
                    'user_id' => $this->periodOfAvailability['user_id']
                );
            }

            $wouldLeaveOpeningSmallerThanMinimumAfter = $this->periodOfAvailability['end'] - $this->booking->end() < 1;
            if ($this->periodOfAvailability['user_id'] == $this->booking->userId() && !$wouldLeaveOpeningSmallerThanMinimumAfter) {
                $this->newAvailability[] = array(
                    'start' => $this->booking->end(),
                    'end' => $this->periodOfAvailability['end'],
                    'user_id' => $this->periodOfAvailability['user_id']
                );
            }
        }
    }

    function mergeOverlappingRanges()
    {
        // not implemented, here as a hack for polymorpism
        return $this->availability;
    }

    function getAvailabilityTimes()
    {
        return $this->availability;
    }

    function getBookedTimes()
    {
        $times = array();
        foreach($this->bookings as $this->booking) {
            array_push($times, array(
                'start'=>$this->booking->start(),
                'end'=>$this->booking->end()
            ));
        }
        return $times;
    }

    function incrementize($availability, $duration = 30)
    {
        $return = array();
        foreach ($availability as $this->periodOfAvailability) {
            $times = $this->times($duration);
            foreach ($times as $time) {
                if ($this->periodOfAvailability['start'] <= $time['start'] && $this->periodOfAvailability['end'] >= $time['end']) {
                    $return[] = $time['start'];
                }
            }
        }


        return $return;
    }

    function format($time)
    {
        $dateTime = new DateTime('2011-06-28 ' . $time);
        return $dateTime->format("H:i:s");
    }

    function times($duration = 30)
    {
        if (!in_array($duration, array(30, 60))) {
            $duration = 30;
        }

        $start = $this->availability[0]['start'];
        $end = $this->availability[count($this->availability) - 1]['end'];
        return $this->timeIntervals($start,$end,$duration);
    }

    function timeIntervals($startTime, $endTime, $duration=30)
    {
        if (!in_array($duration, array(30, 60))) {
            $duration = 30;
        }

        $start = new DateTime('2013-03-21 ' . $startTime);

        $durationToAdd = new DateInterval("P0Y0DT0H" . $duration . "M");

        $end = new DateTime('2013-03-21 ' . $start->format('H:i:00'));
        $end->add($durationToAdd);

        $times = array();
        $goUntil = new DateTime('2013-03-21 ' .$endTime);

        while ($end <= $goUntil) {
            $times[] = array(
                'start' => $start->format('H:i:00'),
                'end' => $end->format('H:i:00')
            );
            $start->add($durationToAdd);
            $end->add($durationToAdd);
        }
        return $times;
    }
}