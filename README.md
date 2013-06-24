[![Latest Stable Version](https://poser.pugx.org/bookingbat/engine/version.png)](https://packagist.org/packages/symfony/symfony)
[![Total Downloads](https://poser.pugx.org/bookingbat/engine/d/total.png)](https://packagist.org/packages/symfony/symfony)

Introduction
============

A framework for defining recurring windows of availability, and "subtracting" bookings/appointments from them. Has features for multiple windows of availability within one day, automatically 'fixes' overlapping windows, and can enforce padding between bookings.

Example
============
Lets set the availability window from 9-11am, and 11:30am-4pm

````php
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
````


Now we'll add a booking from 3:30-4pm & get back the adjusted availability
````php
$newAvailability = $availability->addBooking(array(
    'start' => '15:30',
    'end' => '16:00'
));
````

$newAvailability will show the actual availability is 9-11am, and 11:30-3:30pm:
````
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
````
