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
    public $availability;
    protected $bookings = array();

    function __construct($availability=array())
    {
        $this->availability = $availability;
    }

    function addBooking($booking)
    {
        if (is_array($booking)) {
            $booking = new Booking($booking);
        }
        $newAvailability = array();
        foreach ($this->availability as $periodOfAvailability) {
            $periodOfAvailability['start'] = $this->format($periodOfAvailability['start']);
            $periodOfAvailability['end'] = $this->format($periodOfAvailability['end']);

            if ($booking->start() <= $periodOfAvailability['start'] && $booking->end() >= $periodOfAvailability['end']) {
                continue;
            } // when booking at start of the availability
            else if ($booking->start() == $periodOfAvailability['start']) {
                // should modify availability to start when booking ends
                $newAvailability[] = array('start' => $booking->end(), 'end' => $periodOfAvailability['end']);
            } // when booking at end  of the availability
            else if ($booking->end() == $periodOfAvailability['end']) {
                //should modify availability to end when booking starts'
                $newAvailability[] = array('start' => $periodOfAvailability['start'], 'end' => $booking->start());
            } // when booking is in middle of the availability
            else if ($booking->start() > $periodOfAvailability['start'] && $booking->end() < $periodOfAvailability['end']) {
                // should split availability to end at start of booking, and start again at end of booking
                $newAvailability[] = array('start' => $periodOfAvailability['start'], 'end' => $booking->start());
                $newAvailability[] = array('start' => $booking->end(), 'end' => $periodOfAvailability['end']);
            } // when no bookings during this period, return period unmodified
            else {
                $newAvailability[] = $periodOfAvailability;
            }
        }
        $this->availability = $newAvailability;
        array_push($this->bookings, $booking);
        return $this->availability;
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
        foreach($this->bookings as $booking) {
            array_push($times, array(
                'start'=>$booking->start(),
                'end'=>$booking->end()
            ));
        }
        return $times;
    }

    function incrementize($availability, $duration = 30)
    {
        $return = array();
        foreach ($availability as $periodOfAvailability) {
            $times = $this->times($duration);
            foreach ($times as $time) {
                if ($periodOfAvailability['start'] <= $time['start'] && $periodOfAvailability['end'] >= $time['end']) {
                    $return[] = $time;
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