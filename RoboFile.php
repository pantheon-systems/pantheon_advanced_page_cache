<?php

use Robo\Tasks;
use Robo\Exception\TaskException;

/**
 * This is project's console commands configuration for Robo task runner.
 *
 * @see https://robo.li/
 */
class RoboFile extends Tasks {

  /**
   * Run tests.
   */
  public function testFull(string $drupal_version = '10', string $site_id = null) {
    if (!$site_id) {
      throw new TaskException($this, 'No testing Site ID provided.');
    }

    throw new TaskException($this, 'Not implemented yet.');
  }

}
