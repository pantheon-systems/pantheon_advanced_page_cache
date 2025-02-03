<?php

use Robo\Tasks;
use Robo\Exception\TaskException;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Finder\Finder;

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

  public string $repository;
  public string $owner;
  public string $name;
  /**
   * @var \DateTime
   *   When this run started.
   */
  public DateTime $started;

  /**
   * configs for module installed by default.
   * @var array
   */
  public array $cache_settings;

  /**
   * configs for module installed by default.
   * @var array
   */
  public array $cache_schema;

  /**
   * @var string
   *   The name of the multidev environment being tested.
   */
  public string $testingMultidevName;

  /**
   * Class constructor.
   */
  public function __construct() {
    $this->started = new DateTime();
    require_once 'vendor/autoload.php';
    $this->getInstallConfigs();
    $this->repository = getenv('GITHUB_REPOSITORY');
    [$this->owner, $this->name] = explode('/', $this->repository);
    $this->testingMultidevName = 'test-' . $this->started->format('YmdHis');
  }

  /**
   * Run tests.
   */
  public function testFull(string $drupal_version = '10', string $site_id = NULL) {
    $this->output()->writeln('RoboFile constructor: ' . $this->started->format('Y-m-d H:i:s'));
    $this->output()->writeln('Running tests for Drupal ' . $drupal_version);
    $this->output()->writeln('Repository: ' . $this->repository);
    $this->output()->writeln('Owner: ' . $this->owner);
    $this->output()->writeln('Name: ' . $this->name);
    $this->output()->writeln('Site ID: ' . $site_id);
    $this->output()->writeln('Cache settings: ' . print_r($this->cache_settings, TRUE));
    $this->output()->writeln('Cache schema: ' . print_r($this->cache_schema, TRUE));

    if (empty($site_id)) {
      throw new TaskException($this, 'No testing Site ID provided.');
    }

    // Step 1: Create a new multidev environment.
    $this->newMultidev($site_id,);
    // Step 2: clone the site's codebase.
    $this->cloneSite($site_id);
    // Step 3: Check out the new multidev environment's Git branch
    $this->gitCheckout('test-' . $this->started->format('YmdHis'));
    // Step 4: Require the version of the site necessary for testing.

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

  /**
   * Get the site folder.
   *
   * @param string $site_name
   *   The machine name of the site.
   *
   * @return string
   *   The path to the site folder.
   */
  public function newMultidev(string $site_id, string $multidev_name) {
    $this->say('Creating new multidev environment for ' . $site_id . ' named ' . $multidev_name);
    $this->taskExec(self::$TERMINUS_EXE)
      ->arg('multidev:create')
      ->arg($site_id . '.dev')
      ->arg($multidev_name)
      ->run();
  }

  /**
   * Use terminus local:clone to get a copy of the remote site.
   *
   * @param string $site_name
   *   The machine name of the site to clone.
   *
   * @return \Robo\Result
   */
  public function cloneSite(string $site_name): Result {
    if (!is_dir($this->getSiteFolder($site_name))) {
      $toReturn = $this->taskExec(static::$TERMINUS_EXE)
        ->args('local:clone', $site_name)
        ->run();
      return $toReturn;
    }
    return ResultData::EXITCODE_OK;
  }

  /**
   * Composer require the Solr related modules.
   *
   * @param string $site_name
   *   The machine name of the site to require the Solr modules.
   * @param string $constraint
   *   The constraint to use for the search_api_pantheon module.
   */
  public function requireMod(
    string $site_name,
    string $constraint = '^8'
  ) {
    $site_folder = $this->getSiteFolder($site_name);
    chdir($site_folder);
    // Always test again latest version of search_api_solr.
    $this->taskExec('composer')
      ->args(
        'require',
        'drupal/search_api_solr:dev-4.x',
      )
      ->run();

    return ResultData::EXITCODE_OK;
  }

  protected function checkoutMultidevTestingBranch(string $branch_name) {
    $result = $this->taskExec('git')
      ->dir($this->getSiteFolder($site_name))
      ->args('checkout', $branch_name)
      ->run();
    if (!$result->wasSuccessful()) {
      throw new TaskException($this, 'Failed to checkout branch ' . $branch_name);
    }
  }

  /**
   * Return folder in local machine for given site name.
   *
   * @param string $site_name
   *   The machine name of the site to get the folder for.
   *
   * @return string
   *   Full path to the site folder.
   */
  protected function getSiteFolder(string $site_name) : string {
    return $_SERVER['HOME'] . '/pantheon-local-copies/' . $site_name;
  }

}
