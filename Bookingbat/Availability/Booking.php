<?php
/**
 * Booking Bat - Availability Engine (http://bookingbat.com)
 *
 * @link      http://github.com/bookingbat/engine for the canonical source repository
 * @copyright Copyright (c) 2013 Josh Ribakoff
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
namespace Bookingbat\Engine;
class Booking
{
    protected $options;

    function __construct($options)
    {
        $this->options = $options;
    }

    function date()
    {
        return isset($this->options['date']) ? $this->options['date'] : null;
    }

    function start()
    {
        $dateTime = new \DateTime('2011-06-28 ' . $this->options['start']);
        return $dateTime->format("H:i:s");
    }

    function end()
    {
        if (isset($this->options['end'])) {
            $dateTime = new \DateTime('2011-06-28 ' . $this->options['end']);
        } else {
            $dateTime = new \DateTime('2011-06-28 ' . $this->start());
            $dateTime->add(new \DateInterval('P0Y0DT0H' . $this->duration() . 'M'));
        }
        return $dateTime->format("H:i:s");
    }

    function duration()
    {
        return isset($this->options['duration']) ? $this->options['duration'] : 30;
    }

    function userId()
    {
        return isset($this->options['user_id']) ? $this->options['user_id'] : null;
    }


}