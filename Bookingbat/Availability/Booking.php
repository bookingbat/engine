<?php
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
        $dateTime = new DateTime('2011-06-28 ' . $this->options['start']);
        return $dateTime->format("H:i:s");
    }

    function end()
    {
        if (isset($this->options['end'])) {
            $dateTime = new DateTime('2011-06-28 ' . $this->options['end']);
        } else {
            $dateTime = new DateTime('2011-06-28 ' . $this->start());
            $dateTime->add(new DateInterval('P0Y0DT0H' . $this->duration() . 'M'));
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

    function allowCancelLostByUser()
    {
        $today = strtotime($this->options['today']);
        $booking = strtotime($this->options['date']);

        return !$this->completed();
    }

    function allowCancelByUser()
    {
        $today = strtotime($this->options['today']);
        $booking = strtotime($this->options['date']);

        if ($today > $booking) {
            return false;
        }

        $difference = ($booking - $today) / 60 / 60;
        if ($difference >= 24) {
            return true;
        }
        return false;
    }

    function completed()
    {
        $today = strtotime($this->options['today']);
        $booking = strtotime($this->options['date']);
        if ($today > $booking) {
            return true;
        }
        return false;
    }
}