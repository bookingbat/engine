<?php
/**
 * SEE AvailabilityTest.php FOR UNIT TESTS SHOWING EXTENSIVE EXAMPLE INPUTS & OUTPUTS
 * takes all availability and events and comes out w/ the actual availability
 * Ex: Trainer has recurring availability from 1:00 to 3:00 but an appt from 1:30 to 2:30,
 * after this function, trainer has availability from 1:00 to 1:30 and 2:30 to 3:00

 */
require_once('Booking.php');
require_once('MergeOverlappingRanges.php');
class MassageAvailability extends Availability
{
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

    function mergeOverlappingRanges()
    {
        $merger = new MergeOverlappingRanges($this->availability, 60);
        $this->availability = $merger->merge();
        return $this->availability;
    }

    function addBooking($booking)
    {
        $this->booking = $booking;
        if (is_array($this->booking)) {
            $this->booking = new \Bookingbat\Availability\Booking($this->booking);
        }

        $end = new DateTime($this->booking->end());
        array_push($this->bookings, $this->booking);

        // pad between appointments to allow reset time
        if ($this->padding) {
            $end->add(new DateInterval("P0Y0DT0H" . $this->padding . "M"));
        }

        $this->booking = new \Bookingbat\Availability\Booking(array(
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
                if ($this->booking->start() - $this->periodOfAvailability['start'] <= 1) {
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
}