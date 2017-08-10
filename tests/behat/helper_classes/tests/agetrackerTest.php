<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;


use PantheonSystems\CDNBehatHelpers\agetracker;

/**
 * @covers Email
 */
final class EmailTest extends TestCase
{

    /**
     * Tests ClipCreator::extractClipTitles().
     *
     * @param string $body_text
     *   The body text of a podcast_episode.
     * @param array $extracted_titles
     *   Clip titles extracted from body field.
     *
     * @dataProvider providerPathsAndHeaders
     * @covers ::getTrackedHeaders
     */
    public function testGetTrackedHeaders($path, array $headers) {
        $agetracker = new agetracker('');
        $agetracker->trackHeaders($path, $headers);


        $actual_tracked_headers = $agetracker->getTrackedHeaders($path);

print_r($actual_tracked_headers);


        $this->assertEquals(array($headers), $actual_tracked_headers);
    }
    /**
     * Data provider for testExtractClipTitles.
     *
     * @return array
     *   An array of test data.
     */
    public function providerPathsAndHeaders() {
        $data = array();
        $data[] = [
            '/home',

        array('Age' => array(3))
];


        return $data;
    }




    public function trackHeaders($path, $headers) {


    }
    public function getTrackedHeaders($path) {
        return [];


    }



}