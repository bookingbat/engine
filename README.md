availability
============

A framework for defining recurring windows of availability, and "subtracting" bookings/appointments from them.

Example usage:

````php
// the availability window is from 9-11am, and 11:30am-4pm
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

// add a booking from 3:30-4pm & get back the adjusted availability
$newAvailability = $availability->addBooking(array(
    'start' => '15:30',
    'end' => '16:00'
));

/*
$newAvailability will show the actual availability is 9-11am, and 11:30-3:30pm
array(
    array(
        'start' => '09:00:00',
        'end' => '11:00:00'
    ),
    array(
        'start' => '11:30:00',
        'end' => '15:30:00'
    ),
);
*/
````
