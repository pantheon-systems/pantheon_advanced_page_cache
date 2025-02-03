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
   * @var string
   *   The terminus executable path.
   */
  public static $TERMINUS_EXE = '/usr/local/bin/terminus';

  /**
   * @var \DateTime
   *   When this run started.
   */
  public DateTime $started;

  /**
   * configs for module installed by default
   * @var array $search_index
   */
  public array $cache_settings;

  /**
   * configs for module installed by default
   * @var array $search_server
   */
  public array $cache_schema;


  /**
   * Class constructor.
   */
  public function __construct() {
    $this->started = new DateTime();
    require_once 'vendor/autoload.php';
    $this->getInstallConfigs();
  }


  /**
   * Run tests.
   */
  public function testFull(string $drupal_version = '10', string $site_id = null) {
    $this->output()->writeln('RoboFile constructor: ' . $this->started->format('Y-m-d H:i:s'));
    $this->output()->writeln('Running tests for Drupal ' . $drupal_version);
    $this->output()->writeln('Site ID: ' . $site_id);
    $this->output()->writeln('Cache settings: ' . print_r($this->cache_settings, TRUE));
    $this->output()->writeln('Cache schema: ' . print_r($this->cache_schema, TRUE));

    if (empty($site_id)) {
      throw new TaskException($this, 'No testing Site ID provided.');
    }

    throw new TaskException($this, 'No tests implemented yet.');
  }






  /**
   * Get the install configs.
   */
  private function getInstallConfigs(): void {
    $finder = new Finder();
    try {
      $finder->files()
        ->in('./config')
        ->name(['*.yml', '*.yaml'])
        ->sortByName();
      if (!$finder->hasResults()) {
        throw new \RuntimeException(
          'No YAML files found in the specified directory.'
        );
      }

      foreach ($finder as $file) {
        $filePath = $file->getRealPath();
        $fileName = $file->getBasename('.yml');

        // Remove .yaml extension if present
        $fileName = str_replace('.yaml', '', $fileName);
        $fileName = str_replace('pantheon_advanced_page_cache.', '', $fileName);

        try {
          switch (substr($fileName, 0, 16)) {
            case 'settings':
              $this->cache_settings = Yaml::parseFile($filePath);
              break;
            case 'schema':
              $this->cache_schema = Yaml::parseFile($filePath);
              break;
            default:
              break;
          }
        }
        catch (\Exception $e) {
          throw new \RuntimeException(
            sprintf(
              'Error parsing YAML file %s: %s',
              $filePath,
              $e->getMessage()
            )
          );
        }
      }
    }
    catch (\Exception $e) {
      throw new \RuntimeException(
        sprintf(
          'Error accessing directory %s: %s',
          $directoryPath,
          $e->getMessage()
        )
      );
    }
  }


}
