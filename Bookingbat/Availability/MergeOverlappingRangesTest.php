<?php
require_once(dirname(__FILE__) . '/MergeOverlappingRanges.php');
class MergeOverlappingRangesTest extends PHPUnit_Framework_TestCase
{
    function test_WhenTwoRangesStartAtSameTime_ShouldMerge()
    {
        $availability = array(
            array(
                'start' => '02:30:00',
                'end' => '04:00:00',
            ),
            array(
                'start' => '02:30:00',
                'end' => '03:00:00'
            )
        );
        $merge = new \Bookingbat\Engine\MergeOverlappingRanges($availability);
        $mergedAvailability = $merge->merge();

        $expected = array(array('start' => '02:30:00', 'end' => '04:00:00'));
        $this->assertEquals($expected, $mergedAvailability);

    }

    function testShouldNotMergeNonOverlapping()
    {
        $availability = array(
            array(
                'start' => '09:00:00',
                'end' => '11:00:00'
            ),
            array(
                'start' => '11:30:00',
                'end' => '16:00:00'
            ),
        );
        $merge = new \Bookingbat\Engine\MergeOverlappingRanges($availability);
        $mergedAvailability = $merge->merge();
        $this->assertEquals($availability, $mergedAvailability,'should not merge non-overlapping ranges');
    }

    function test_WhenTwoRangesStartAtSameTime_ShouldMergeUserIds()
    {
        $availability = array(
            array(
                'start' => '01:00:00',
                'end' => '02:00:00',
                'user_id' => 1
            ),
            array(
                'start' => '02:30:00',
                'end' => '04:00:00',
                'user_id' => 1
            ),
            array(
                'start' => '02:30:00',
                'end' => '03:00:00',
                'user_id' => 2
            )
        );
        $merge = new \Bookingbat\Engine\MergeOverlappingRanges($availability);
        $mergedAvailability = $merge->merge();

        $expected = array(
            array(
                'start' => '01:00:00',
                'end' => '02:00:00',
                'user_id' => 1
            ),
            array(
                'start' => '02:30:00',
                'end' => '04:00:00',
                'user_id' => array(1, 2)
            )
        );
        $this->assertEquals($expected, $mergedAvailability);

    }

    function testWhenTwoRangesWithSameGap_ShouldPreserveGap()
    {
        $availability = array(
            array(
                'start' => '00:30:00',
                'end' => '20:00:00',
                'user_id' => 1
            ),
            array(
                'start' => '20:30:00',
                'end' => '23:30:00',
                'user_id' => 1
            ),
            array(
                'start' => '19:00:00',
                'end' => '20:00:00',
                'user_id' => 2
            ),
            array(
                'start' => '20:30:00',
                'end' => '22:30:00',
                'user_id' => 2
            )
        );

        $merge = new \Bookingbat\Engine\MergeOverlappingRanges($availability);
        $mergedAvailability = $merge->merge();

        $expected = array(
            array(
                'start' => '00:30:00',
                'end' => '20:00:00',
                'user_id' => array(1, 2)
            ),
            array(
                'start' => '20:30:00',
                'end' => '23:30:00',
                'user_id' => array(1, 2)
            )
        );
        $this->assertEquals($expected, $mergedAvailability);
    }

    function test_RemovesRangeSmallerThanThreshold()
    {
        $availability = array(
            array(
                'start' => '02:30:00',
                'end' => '03:00:00',
            ),
        );
        $merge = new \Bookingbat\Engine\MergeOverlappingRanges($availability, 60);
        $mergedAvailability = $merge->merge();

        $expected = array();
        $this->assertEquals($expected, $mergedAvailability);
    }

//    function test_RemovesRangeSmallerThanThresholdPreserveUserId()
//    {
//        $availability = array(
//                array(
//                    'start'=>'00:30:00',
//                    'end'=>'03:30:00',
//                    'user_id'=>array(14)
//                ),
//                array(
//                    'start'=>'05:30:00',
//                    'end'=>'07:30:00',
//                    'user_id'=>array(14)
//                ),
//                array(
//                    'start'=>'09:00:00',
//                    'end'=>'21:30:00',
//                    'user_id'=>array(14)
//                ),
//                array(
//                    'start'=>'23:00:00',
//                    'end'=>'23:30:00',
//                    'user_id'=>array(14)
//                ),
//                array(
//                    'start'=>'19:00:00',
//                    'end'=>'22:30:00',
//                    'user_id'=>array(15)
//                ),
//        );
//        $merge = new \Bookingbat\Engine\MergeOverlappingRanges($availability,60);
//        $mergedAvailability = $merge->merge();
//        print_r($mergedAvailability);exit();
//        $expected = array();
//        $this->assertEquals($expected, $mergedAvailability); 
//    }
}