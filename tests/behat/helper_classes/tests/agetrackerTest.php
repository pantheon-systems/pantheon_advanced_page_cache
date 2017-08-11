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
     * Tests agetracker::getTrackedHeaders
     *
     * @param string $path
     *   The url being tracked
     * @param array $headers
     *   The headers of each time the URL was checked
     *
     * @dataProvider providerPathsAndHeaders
     * @covers ::getTrackedHeaders
     */
    public function testGetTrackedHeaders($path, array $headers) {
        $agetracker = new agetracker('');
        $agetracker->trackHeaders($path, $headers);


        $actual_tracked_headers = $agetracker->getTrackedHeaders($path);
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
            $this->cacheLifeIncreasing(),
        ];
        $data[] = [
            '/cache-got-cleared',
            $this->cacheGotClearedHeaders(),
        ];

        return $data;
    }


    public function providerExpectedCacheClears() {
        $data = array();
        $data[] = [
            '/home',
            $this->cacheLifeIncreasing(),
            FALSE,
        ];
        $data[] = [
            '/cache-got-cleared',
            $this->cacheGotClearedHeaders(),
            TRUE,
        ];

        return $data;
    }




    /**
     * Tests agetracker::getTrackedHeaders
     *
     * @param string $path
     *   The url being tracked
     * @param array $headers
     *   The headers of each time the URL was checked
     *
     * @dataProvider providerExpectedCacheClears
     * @covers ::wasCacheClearedBetweenLastTwoRequests
     */
    public function testCheckCacheClear($path, array $headers_set, $expected_cache_clear) {
        $agetracker = new agetracker('');

        foreach ($headers_set as $headers) {
            $agetracker->trackHeaders($path, $headers);
        }
        $this->assertEquals($expected_cache_clear, $agetracker->wasCacheClearedBetweenLastTwoRequests($path));
    }



    protected function cacheLifeIncreasing(){
      return  [
          [
              'Cache-Control' => ['max-age=600, public'],
              'Age' => [3],
              'X-Timer' => ['S1502402462.916272,VS0,VE1']
          ],
          [
              'Cache-Control' => ['max-age=600, public'],
              'Age' => [10],
              'X-Timer' => ['S1502402469.916272,VS0,VE1']
          ],
      ];

    }

    protected function cacheGotClearedHeaders(){
        return             [
            [
                'Cache-Control' => ['max-age=600, public'],
                'Age' => [30],
                'X-Timer' => ['S1502402462.916272,VS0,VE1']
            ],
            [
                'Cache-Control' => ['max-age=600, public'],
                'Age' => [4],
                'X-Timer' => ['S1502402469.916272,VS0,VE1']
            ],
        ];

    }






    public function trackHeaders($path, $headers) {


    }
    public function getTrackedHeaders($path) {
        return [];


    }



}