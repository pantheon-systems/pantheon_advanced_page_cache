<?php

/**
 * @file
 * Global-namespace mock for pantheon_clear_edge_keys().
 *
 * Loaded by CacheTagsInvalidatorTest to capture CDN purge calls.
 */

use Drupal\Tests\pantheon_advanced_page_cache\Unit\CacheTagsInvalidatorTest;

if (!function_exists('pantheon_clear_edge_keys')) {

  /**
   * Mock implementation that records calls for test assertions.
   */
  function pantheon_clear_edge_keys(array $keys) {
    CacheTagsInvalidatorTest::$capturedEdgeKeys = $keys;
  }

}
