<?php

declare(strict_types=1);

namespace PantheonSystems\CDNBehatHelpers\tests;

use PHPUnit\Framework\TestCase;
use PantheonSystems\CDNBehatHelpers\AgeTracker;

/**
 * @covers AgeTracker
 */
final class AgeTrackerTest extends TestCase {

  /**
   * Tests AgeTracker::getTrackedHeaders.
   *
   * @param string $path
   *   The url being tracked
   * @param array $headers_set
   *   The headers of each time the URL was checked
   *
   * @dataProvider providerPathsAndHeaders
   * @covers ::getTrackedHeaders
   */
  public function testGetTrackedHeaders($path, array $headers_set) {
    $ageTracker = new AgeTracker();
    foreach ($headers_set as $headers) {
      $ageTracker->trackHeaders($path, $headers);
    }
    $actual_tracked_headers = $ageTracker->getTrackedHeaders($path);
    $this->assertEquals($headers_set, $actual_tracked_headers);
  }

  /**
   * Data provider for testGetTrackedHeaders.
   *
   * @return array
   *   An array of test data.
   */
  public function providerPathsAndHeaders() {
    $data = [];
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
    $data = [];
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
   * Tests AgeTracker::getTrackedHeaders.
   *
   * @param string $path
   *   The url being tracked
   * @param array $headers_set
   *   The headers of each time the URL was checked
   * @param bool $expected_cache_clear
   *   Whether the cache was cleared between the last two requests
   *
   * @dataProvider providerExpectedCacheClears
   * @covers ::wasCacheClearedBetweenLastTwoRequests
   * @covers ::ageIncreasedBetweenLastTwoRequests
   */
  public function testCheckCacheClear($path, array $headers_set, $expected_cache_clear) {
    $ageTracker = new AgeTracker();

    foreach ($headers_set as $headers) {
      $ageTracker->trackHeaders($path, $headers);
    }
    $this->assertEquals($expected_cache_clear, $ageTracker->wasCacheClearedBetweenLastTwoRequests($path));
    $this->assertEquals(!$expected_cache_clear, $ageTracker->ageIncreasedBetweenLastTwoRequests($path));
  }

  protected function cacheLifeIncreasing() {
    return [
        [
            'cache-control' => 'max-age=600, public',
            'age' => 3,
            'x-timer' => 'S1502402462.916272,VS0,VE1',
        ],
        [
            'cache-control' => 'max-age=600, public',
            'age' => 10,
            'x-timer' => 'S1502402469.916272,VS0,VE1',
        ],
      ];
  }

  protected function cacheGotClearedHeaders() {
    return [
          [
              'cache-control' => 'max-age=600, public',
              'age' => 30,
              'x-timer' => 'S1502402462.916272,VS0,VE1',
          ],
          [
              'cache-control' => 'max-age=600, public',
              'age' => 0,
              'x-timer' => 'S1502402469.916272,VS0,VE1',
          ],
      ];
  }

}
