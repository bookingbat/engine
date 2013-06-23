<?php
/**
 * Booking Bat - Availability Engine (http://bookingbat.com)
 *
 * @link      http://github.com/bookingbat/engine for the canonical source repository
 * @copyright Copyright (c) 2013 Josh Ribakoff
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
namespace Bookingbat\Engine;
use \DateTime,
    \DateInterval;


require_once(__DIR__.'/Booking.php');

/** Takes all availability and events and comes out w/ the actual availability */
class Availability
{
    protected $minimum_booking_duration=0;
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
                case 'minimum_booking_duration':
                    $this->minimum_booking_duration = $value;
            }
        }
    }

    function addBooking($booking)
    {
        $this->booking = $booking;
        if (is_array($this->booking)) {
            $this->booking = new \Bookingbat\Engine\Booking($this->booking);
        }

        $end = new DateTime($this->booking->end());
        array_push($this->bookings, $this->booking);

        // pad between appointments to allow reset time
        if ($this->padding) {
            $end->add(new DateInterval("P0Y0DT0H" . $this->padding . "M"));
        }

        $this->booking = new \Bookingbat\Engine\Booking(array(
            'start' => $this->booking->start(),
            'end' => $end->format('H:i:00'),
            'user_id' => $this->booking->userId()
        ));

        $this->newAvailability = array();
        foreach ($this->availability as $this->periodOfAvailability) {
            if (!isset($this->periodOfAvailability['user_id'])) {
                $this->periodOfAvailability['user_id'] = null;
            }

            if ($this->booking->userId() && $this->booking->userId() != $this->periodOfAvailability['user_id']) {
                $this->newAvailability[] = $this->periodOfAvailability;
                continue;
            }
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
                //should modify availability to end when booking starts
                $this->modifyAvailabilityToEndWhenBookingStarts();
            } else if ($this->bookingInMiddleOfAvailability()) {
                // when booking is in middle of the availability, should split availability to end at start of booking, and start again at end of booking
                // don't allow time blocks smaller than the minimum appointment length
                $this->splitAvailabilityAroundBooking();
            } else if ($this->bookingOverlapsEndOfAvailability()) {
                // booking starts after availability starts, and ends after availability ends
                // don't allow time blocks smaller than the minimum appointment length
                if ($this->booking->start() - $this->periodOfAvailability['start'] <= $this->minimum_booking_duration) {
                    continue;
                }
                $this->newAvailability[] = array(
                    'is-computed' => true,
                    'start' => $this->periodOfAvailability['start'],
                    'end' => $this->booking->start(),
                    'user_id' => $this->periodOfAvailability['user_id']
                );
            }
            else {
                // when no bookings during this period, return period unmodified
                $this->newAvailability[] = $this->periodOfAvailability;
            }
        }
        $this->availability = $this->newAvailability;
        return $this->getAvailabilityTimes();
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

    function bookingOverlapsEndOfAvailability()
    {
        return $this->booking->start() > $this->periodOfAvailability['start'] &&
            $this->booking->start() < $this->periodOfAvailability['end'] &&
            $this->booking->end() >= $this->periodOfAvailability['end'];
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
        if(!array_key_exists('user_id',$this->periodOfAvailability) || !$this->periodOfAvailability['user_id']) {
            $wouldLeaveOpeningSmallerThanMinimumBefore = $this->booking->start() - $this->periodOfAvailability['start'] <= $this->minimum_booking_duration;
            if(!$wouldLeaveOpeningSmallerThanMinimumBefore) {
                $this->newAvailability[] = array(
                    'start' => $this->periodOfAvailability['start'],
                    'end' => $this->booking->start()
                );
            }
            $wouldLeaveOpeningSmallerThanMinimumAfter = $this->periodOfAvailability['end'] - $this->booking->end() < $this->minimum_booking_duration;
            if(!$wouldLeaveOpeningSmallerThanMinimumAfter) {
                $this->newAvailability[] = array(
                    'start' => $this->booking->end(),
                    'end' => $this->periodOfAvailability['end']
                );
            }
        } else {
            $wouldLeaveOpeningSmallerThanMinimumBefore = $this->booking->start() - $this->periodOfAvailability['start'] <= $this->minimum_booking_duration;
            if ($this->periodOfAvailability['user_id'] == $this->booking->userId() && !$wouldLeaveOpeningSmallerThanMinimumBefore) {
                $this->newAvailability[] = array(
                    'is-computed' => true,
                    'start' => $this->periodOfAvailability['start'],
                    'end' => $this->booking->start(),
                    'user_id' => $this->periodOfAvailability['user_id']
                );
            }

            $wouldLeaveOpeningSmallerThanMinimumAfter = $this->periodOfAvailability['end'] - $this->booking->end() < $this->minimum_booking_duration;
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
        $merger = new MergeOverlappingRanges($this->availability, 60);
        $this->availability = $merger->merge();
        return $this->availability;
    }

    function getAvailabilityTimes()
    {
        $availability = $this->availability;
        foreach($availability as $key=>$val) {
            if(array_key_exists('user_id',$availability[$key]) && !$availability[$key]['user_id']) {
                unset($availability[$key]['user_id']);
            }
        }
        return $availability;
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

    function incrementize($availability, $duration = 30, $lengthOfAppointmentToMake = null)
    {
        if (!count($availability)) {
            return array();
        }
        if (!isset($availability[count($availability) - 1]['is-computed'])) {
            $lastSegmentEndTime = $availability[count($availability) - 1]['end'];
            $lastSegmentEndTime = new DateTime($lastSegmentEndTime);
            $lastSegmentEndTime->add(new DateInterval("P0Y0DT0H30M"));
            $availability[count($availability) - 1]['end'] = $lastSegmentEndTime->format('H:i:00');
        }
        if ($availability[count($availability) - 1]['end'] == '00:00:00') {
            $availability[count($availability) - 1]['end'] = '24:00:00';
        }

        $return = array();

        if ($lengthOfAppointmentToMake) {
            $lengthOfAppointmentToMake += 30;
        }

        foreach ($availability as $this->periodOfAvailability) {
            $start = new DateTime($this->periodOfAvailability['start']);
            $end = new DateTime($this->periodOfAvailability['end']);


            if ($lengthOfAppointmentToMake) {
                $diff = $end->diff($start);
                $diff = $diff->format('%H') * 60 + $diff->format('%i');
                if ($lengthOfAppointmentToMake > $diff) {
                    continue;
                }
            }
            if ($lengthOfAppointmentToMake == 120) {
                $end->sub(new DateInterval("P0Y0DT2H00M"));
            } elseif ($lengthOfAppointmentToMake == 90) {
                $end->sub(new DateInterval("P0Y0DT1H30M"));
            }
            $this->periodOfAvailability['end'] = $end->format('H:i:00');
            if ($this->periodOfAvailability['start'] == $this->periodOfAvailability['end']) {
                $return[] = $this->periodOfAvailability['start'];
                continue;
            }

            foreach ($this->times($duration) as $time) {
                $time = $time['start'];
                if ($this->periodOfAvailability['start'] <= $time && $this->periodOfAvailability['end'] >= $time) {
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

    function possibleUserIdsForBooking($booking)
    {
        $this->booking = $booking;
        $allUserIds = array();
        for ($index = 0; $index < count($this->availability); $index++) {
            if ($this->availability[$index]['start'] <= $this->booking->start() && $this->availability[$index]['end'] >= $this->booking->end()) {
                $theseUserIds = is_array($this->availability[$index]['user_id']) ? $this->availability[$index]['user_id'] : array($this->availability[$index]['user_id']);
                $allUserIds = array_merge($allUserIds, $theseUserIds);
            }
        }
        return $allUserIds;
    }
}