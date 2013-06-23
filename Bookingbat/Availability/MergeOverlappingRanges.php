<?php
namespace Bookingbat\Engine;
use \DateTime;
class MergeOverlappingRanges
{
    public $minimumRange;
    public $availability;
    public $index;

    function __construct($availability, $minimumRange = null)
    {
        $this->minimumRange = $minimumRange;
        $this->availability = $availability;
    }

    function merge()
    {
        $this->doMerge();
        $this->doMerge();
        return $this->doMerge();
    }

    function doMerge()
    {
        $this->availability = array_values($this->availability);
        $this->sort();
        $this->availability = array_values($this->availability);

        for ($this->index = 0; $this->index < count($this->availability) - 1; $this->index++) {

            // if this one starts after next one ends = skip it
            if ($this->thisOneStartsAfterNextOneEnds()) {
                continue;
            }
            // if this one ends before next one starts = skip it
            if ($this->thisOneEndsBeforeNextOneStarts()) {
                continue;
            }


            if ($this->nextOneEndsAfterThisOneEnds()) {
                $this->availability[$this->index]['end'] = $this->availability[$this->index + 1]['end'];
            }
            if ($this->nextOneStartsBeforeThisOneStarts()) {
                $this->availability[$this->index]['start'] = $this->availability[$this->index + 1]['start'];
            }
            $this->mergeUserIds();
            unset($this->availability[$this->index + 1]);
            $this->availability = array_values($this->availability);

        }

        foreach ($this->availability as $key => $periodOfAvailability) {
            $periodOfAvailabilityEnd = new DateTime($periodOfAvailability['end']);
            $periodOfAvailabilityStart = new DateTime($periodOfAvailability['start']);
            $diff = $periodOfAvailabilityEnd->diff($periodOfAvailabilityStart);
            $diff = $diff->format('%H') * 60 + $diff->format('%i');
            if ($diff < $this->minimumRange) {
                unset($this->availability[$key]);
            }
        }

        $this->availability = array_values($this->availability);
        return $this->availability;
    }

    function sort()
    {
        $startTimes = array();
        $endTimes = array();
        $userIds = array();

        foreach ($this->availability as $key => $availability) {
            $startTimes[$key] = $availability['start'];
            $endTimes[$key] = $availability['end'];
            $userIds[$key] = isset($availability['user_id']) ? $availability['user_id'] : null;
        }

        array_multisort($startTimes, SORT_ASC, SORT_STRING, $this->availability);
    }

    function nextOneEndsAfterThisOneEnds()
    {
        return $this->availability[$this->index + 1]['end'] > $this->availability[$this->index]['end'];
    }

    function nextOneStartsBeforeThisOneStarts()
    {
        return $this->availability[$this->index + 1]['start'] < $this->availability[$this->index]['start'];
    }

    function thisOneStartsAfterNextOneEnds()
    {
        return $this->availability[$this->index]['start'] > $this->availability[$this->index + 1]['end'];
    }

    function thisOneEndsBeforeNextOneStarts()
    {
        return $this->availability[$this->index]['end'] < $this->availability[$this->index + 1]['start'];
    }

    function mergeUserIds()
    {
        if (!isset($this->availability[$this->index]['user_id'])) {
            return;
        }
        if (!is_array($this->availability[$this->index]['user_id'])) {
            $this->availability[$this->index]['user_id'] = array($this->availability[$this->index]['user_id']);
        }
        if (!is_array($this->availability[$this->index + 1]['user_id'])) {
            $this->availability[$this->index]['user_id'][] = $this->availability[$this->index + 1]['user_id'];
        } else {
            $this->availability[$this->index]['user_id'] = array_merge($this->availability[$this->index]['user_id'], $this->availability[$this->index + 1]['user_id']);
        }
        sort($this->availability[$this->index]['user_id']);
        $this->availability[$this->index]['user_id'] = array_values(array_unique($this->availability[$this->index]['user_id']));
    }
}