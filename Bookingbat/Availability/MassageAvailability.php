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
require_once('Booking.php');
require_once('MergeOverlappingRanges.php');
class MassageAvailability extends Availability
{
    function possibleUserIdsForBooking($booking)
    {
        $allUserIds = array();
        for ($index = 0; $index < count($this->availability); $index++) {
            if ($this->availability[$index]['start'] <= $booking->start() && $this->availability[$index]['end'] >= $booking->end()) {
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
        if (is_array($booking)) {
            $booking = new \Bookingbat\Availability\Booking($booking);
        }


        $end = new DateTime($booking->end());
        array_push($this->bookings, $booking);

        // pad between appointments to allow reset time
        if($this->padding) {
            $end->add(new DateInterval("P0Y0DT0H".$this->padding."M"));
        }

        $booking = new \Bookingbat\Availability\Booking(array(
            'start' => $booking->start(),
            'end' => $end->format('H:i:00'),
            'user_id' => $booking->userId()
        ));

        $newAvailability = array();
        foreach ($this->availability as $periodOfAvailability) {
            if (!isset($periodOfAvailability['user_id'])) {
                $periodOfAvailability['user_id'] = null;
            }

            if ($booking->userId() && $booking->userId() != $periodOfAvailability['user_id']) {
                $newAvailability[] = $periodOfAvailability;
                continue;
            }
            $periodOfAvailability['start'] = $this->format($periodOfAvailability['start']);
            $periodOfAvailability['end'] = $this->format($periodOfAvailability['end']);

            if ($booking->start() <= $periodOfAvailability['start'] && $booking->end() >= $periodOfAvailability['end']) {
                continue;
            } // when booking at start of the availability
            else if ($booking->start() == $periodOfAvailability['start']) {
                // should modify availability to start when booking ends
                $newAvailability[] = array('start' => $booking->end(), 'end' => $periodOfAvailability['end'], 'user_id' => $periodOfAvailability['user_id']);
            } // when booking at end  of the availability
            else if ($booking->end() == $periodOfAvailability['end']) {
                //should modify availability to end when booking starts
                $newAvailability[] = array('is-computed' => true, 'start' => $periodOfAvailability['start'], 'end' => $booking->start(), 'user_id' => $periodOfAvailability['user_id']);
            } // when booking is in middle of the availability, should split availability to end at start of booking, and start again at end of booking
            else if ($booking->start() > $periodOfAvailability['start'] && $booking->end() < $periodOfAvailability['end']) {
                // don't allow time blocks smaller than the minimum appointment length
                if ($periodOfAvailability['user_id'] == $booking->userId() && $booking->start() - $periodOfAvailability['start'] > 1) {
                    $newAvailability[] = array('is-computed' => true, 'start' => $periodOfAvailability['start'], 'end' => $booking->start(), 'user_id' => $periodOfAvailability['user_id']);
                }
                if ($periodOfAvailability['user_id'] == $booking->userId() && $periodOfAvailability['end'] - $booking->end() >= 1) {
                    $newAvailability[] = array('start' => $booking->end(), 'end' => $periodOfAvailability['end'], 'user_id' => $periodOfAvailability['user_id']);
                }
            } // booking starts after availability starts, and ends after availability ends
            else if ($booking->start() > $periodOfAvailability['start'] && $booking->end() >= $periodOfAvailability['end']) {
                // don't allow time blocks smaller than the minimum appointment length
                if ($booking->start() - $periodOfAvailability['start'] <= 1) {
                    continue;
                }
                $newAvailability[] = array('is-computed' => true, 'start' => $periodOfAvailability['start'], 'end' => $booking->start(), 'user_id' => $periodOfAvailability['user_id']);
            } // when no bookings during this period, return period unmodified
            else {
                $newAvailability[] = $periodOfAvailability;
            }
        }
        $this->availability = $newAvailability;
        return $this->availability;
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

        foreach ($availability as $periodOfAvailability) {
            $start = new DateTime($periodOfAvailability['start']);
            $end = new DateTime($periodOfAvailability['end']);


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
            $periodOfAvailability['end'] = $end->format('H:i:00');
            if ($periodOfAvailability['start'] == $periodOfAvailability['end']) {
                $return[] = $periodOfAvailability['start'];
                continue;
            }

            foreach ($this->times($duration) as $time) {
                $time = $time['start'];
                if ($periodOfAvailability['start'] <= $time && $periodOfAvailability['end'] >= $time) {
                    $return[] = $time;
                }
            }
        }


        return $return;
    }

}