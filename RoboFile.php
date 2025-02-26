<?php

use CzProject\GitPhp\Git;
use Robo\Exception\TaskException;
use Robo\Result;
use Robo\ResultData;
use Robo\Tasks;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Yaml;

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
   * @var string
   *   The current repository
   */
  protected string $repository;

  /**
   * @var string
   *   Repository owner.
   */
  protected string $owner;

  /**
   * @var string
   *   Repository name.
   */
  protected string $name;

  /**
   * @var \DateTime
   *   When this run started.
   */
  protected DateTime $started;

  /**
   * configs for module installed by default.
   *
   * @var array
   */
  protected array $cache_settings;

  /**
   * configs for module installed by default.
   *
   * @var array
   */
  protected array $cache_schema;

  /**
   * @var string
   *   The name of the site being tested.
   */
  protected string $testingSiteName;

  /**
   * @var string
   *   The name of the branch being tested.
   */
  protected string $testModuleConstraint;

  protected string $localCloneDirectory;

  /**
   * Class constructor.
   */
  public function __construct() {
    $this->started = new DateTime();
    require_once 'vendor/autoload.php';
    $this->getInstallConfigs();
    $this->repository = getenv('GITHUB_REPOSITORY');
    [$this->owner, $this->name] = explode('/', $this->repository);
    $this->testModuleConstraint = $this->getCurrentConstraint();
  }

  /**
   * Clean up yo mess.
   */
  public function __destruct() {
    $this->deleteSites();
  }

  /**
   * Run tests.
   */
  public function testFull(
    string $drupal_version = '10',
    ) {
    $this->testingSiteName = sprintf(
      'papc-%s-d%d',
      $this->getShortRef(),
      $drupal_version
    );
    $this->output()->writeln('🧾');
    $this->output()->writeln(str_repeat("=", 80));
    $this->output()->writeln(
      'RoboFile constructor: ' . $this->started->format('Y-m-d H:i:s')
    );
    $this->output()->writeln('Who ami I: ' . $this->whoami());
    $this->output()->writeln('Running tests for Drupal ' . $drupal_version);
    $this->output()->writeln('Repository: ' . $this->repository);
    $this->output()->writeln('Owner: ' . $this->owner);
    $this->output()->writeln('Name: ' . $this->name);
    $this->output()->writeln('Site ID: ' . $this->testingSiteName);
    $this->output()->writeln(
      'Module constraint: ' . $this->testModuleConstraint
    );
    $this->output()->writeln('Drupal Version: ' . $drupal_version);
    $this->output()->writeln('Home: ' . $this->getHomeDir());
    $this->output()->writeln(
      'Cache settings: ' . print_r($this->cache_settings, TRUE)
    );
    $this->output()->writeln(
      'Cache schema: ' . print_r($this->cache_schema, TRUE)
    );
    $this->output()->writeln(str_repeat("=", 80));
    $options = ['drupal_version' => $drupal_version];
    $org = getenv('TERMINUS_ORG');
    if ($org) {
      $options['org'] = $org;
    }
    // Step 1: Create a new site for testing.
    $this->createSite(
      $this->testingSiteName,
      $options,
    );
    // Step 1b. Update known_hosts file with the git host and port.
    $this->updateKnownHosts($this->testingSiteName);
    // Step 2: Install the site.
    $this->siteInstall($this->testingSiteName);
    // Step 3: Set the connection mode to git.
    $this->setConnectionGit($this->testingSiteName);
    // Step 4: Clone the site locally.
    $this->cloneSite($this->testingSiteName);
    // Step 5: Require the module.
    $this->allowPlugins($this->testingSiteName, $drupal_version);
    $this->requireMod($this->testingSiteName, $this->testModuleConstraint);
    // Step 6: Check in and push the changes.
    $this->checkinAndPush($this->testingSiteName);
    // Step 7: Enable the module.
    $this->moduleEnable($this->testingSiteName);
    // throw new TaskException($this, 'No tests implemented yet.');
  }

  /**
   * Add allow plugins section to composer.
   *
   * @param string $site_name
   *   The machine name of the site to add the allow plugins section to.
   * @param int $drupal_version
   *   The major version of Drupal to use.
   */
  public function allowPlugins(string $site_name): int {
    $plugins = [
      'drupal/core-project-message',
      'phpstan/extension-installer',
      'mglaman/composer-drupal-lenient',
    ];

    $site_folder = $this->getLocalCloneDir($site_name);
    if (!is_dir($site_folder)) {
      throw new TaskException($this, 'Site folder not found: ' . $site_folder);
    }
    chdir($site_folder);

    foreach ($plugins as $plugin_name) {
      $result = $this->taskExec('composer')
        ->args(
          'config',
          '--no-interaction',
          'allow-plugins.' . $plugin_name,
          'true'
        )
        ->run();
      if (!$result->wasSuccessful()) {
        throw new TaskException(
          $this,
          'Error adding allow-plugins section plugin: ' . $plugin_name
        );
      }
    }
    return ResultData::EXITCODE_OK;
  }

  /**
   * Use terminus local:clone to get a copy of the remote site.
   *
   * @param string $site_name
   *   The machine name of the site to clone.
   *
   * @return \Robo\Result
   */
  public function cloneSite(string $site_name): int {
    if (!is_dir($this->getLocalCloneDir($site_name))) {
      $result = $this->taskExec(static::$TERMINUS_EXE)
        ->args('local:clone', $site_name)
        ->run();
      if (!$result->wasSuccessful()) {
        throw new TaskException($this, 'Error cloning site');
      }
      $lcd = $result->getMessage();
      if (empty($lcd)) {
        $this->output()->writeln(
          'Local clone directory not set. Setting default value.'
        );
        $lcd = $this->getHomeDir() . DIRECTORY_SEPARATOR . $site_name;
      }
      $this->localCloneDirectory = trim($lcd);
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
    string $constraint = '^2-dev'
  ) {
    $site_folder = $this->getLocalCloneDir($site_name);
    chdir($site_folder);
    $this->taskExec('composer')
      ->args(
        'require',
        $this->repository,
        $constraint,
      )
      ->run();
    return ResultData::EXITCODE_OK;
  }

  /**
   * Create site in Pantheon if it doesn't exist. Return site info.
   *
   * @param string $site_name
   *   The machine name of the site to create.
   *
   * @return \Robo\Result
   */
  public function createSite(
    string $site_name,
    array $options = ['org' => NULL]
  ) {
    $HOME = $this->getHomeDir();
    $site_info = $this->siteInfo($site_name);
    if (empty($site_info)) {
      $toReturn = $this->taskExec(static::$TERMINUS_EXE)
        ->args(
          'site:create',
          $site_name,
          $site_name,
          sprintf(
            'drupal-%d-composer-managed',
            $options['drupal_version']
          )
        );
      if (!empty($options['org'])) {
        $toReturn->option('org', $options['org']);
      }
      $toReturn->run();
      $this->waitForWorkflow($site_name);
      $site_info = $this->siteInfo($site_name);
      // Write to $HOME/.robo-sites-created to delete them later.
      exec("echo $site_name >> $HOME/.robo-sites-created");
    }
    return $site_info;
  }

  /**
   * Wait for the given workflow to finish.
   *
   * @param string $site_name
   */
  public function waitForWorkflow(string $site_name, string $env = 'dev') {
    $this->output()->write('Checking workflow status', TRUE);

    exec(
      "terminus workflow:info:status --format=json $site_name.$env",
      $info
    );
    $info = json_decode(join("", $info), TRUE);
    if (empty($info) || json_last_error() !== JSON_ERROR_NONE) {
      throw new TaskException($this, 'Error getting workflow information');
    }

    $this->output()->write($info['workflow'], TRUE);

    // Wait for workflow to finish only if it hasn't already.
    // This prevents the workflow:wait command from unnecessarily
    // running for 260 seconds when there's no workflow in progress.
    if ($info['status'] !== 'succeeded') {
      $this->output()->write('Waiting for platform', TRUE);
      exec(
        "terminus workflow:wait --max=260 $site_name.$env",
        $finished,
        $status
      );
    }

    if ($this->output()->isVerbose()) {
      Kint::dump(get_defined_vars());
    }
    $this->output()->writeln('');
  }

  /**
   * Set environment connection mode to git or sftp.
   *
   * @param string $site_name
   *   The machine name of the site to set the connection mode.
   * @param string $env
   *   The environment to set the connection mode.
   * @param string $connection
   *   The connection mode to set (git/sftp).
   */
  public function setConnectionGit(
    string $site_name,
    string $env = 'dev',
    string $connection = 'git'
  ) {
    $this->taskExec('terminus')
      ->args('connection:set', $site_name . '.' . $env, $connection)
      ->run();
  }

  /**
   * Add all changes to the git repository, commit and push.
   *
   * @param string $site_name
   *   The machine name of the site to commit and push.
   * @param string $commit_msg
   *   The commit message to use.
   */
  public function checkinAndPush(
    string $site_name,
    string $commit_msg = 'Changes committed from demo script.'
  ) {
    $site_folder = $this->getLocalCloneDir($site_name);
    chdir($site_folder);
    try {
      $git = new Git();
      $repo = $git->open($site_folder);
      if ($repo->hasChanges()) {
        $repo->addAllChanges();
        $repo->commit($commit_msg);
      }
      $result = $this->taskExec('git push origin master')
        ->run();
      if ($result instanceof Result && !$result->wasSuccessful()) {
        Kint::dump($result);
        throw new Exception("error occurred");
      }
    }
    catch (Exception $e) {
      $this->output()->write($e->getMessage());
      return ResultData::EXITCODE_ERROR;
    }
    catch (Throwable $t) {
      $this->output()->write($t->getMessage());
      return ResultData::EXITCODE_ERROR;
    }
    $this->waitForWorkflow($site_name);
    return ResultData::EXITCODE_OK;
  }

  /**
   * Install the Drupal site in Pantheon.
   *
   * @param string $site_name
   *   The machine name of the site to install.
   * @param string $env
   *   The environment to install the site in.
   * @param string $profile
   *   The Drupal profile to use during site installation.
   */
  public function siteInstall(
    string $site_name,
    string $env = 'dev',
    string $profile = 'demo_umami'
  ) {
    $this->dieOnError(
      $this->taskExec(static::$TERMINUS_EXE)
        ->args(
          'drush',
          $site_name . '.' . $env,
          '--',
          'site:install',
          $profile,
          '-y'
        )
        ->options([
          'account-name' => 'admin',
          'site-name'    => $site_name,
          'locale'       => 'en',
        ])
        ->run(),
      'Error installing site.'
    );
    $this->waitForWorkflow($site_name);
    return ResultData::EXITCODE_OK;
  }

  /**
   * Enable solr modules in given Pantheon site.
   *
   * @param string $site_name
   *   The machine name of the site to enable solr modules.
   * @param string $env
   *   The environment to enable the modules in.
   */
  public function moduleEnable(string $site_name, string $env = 'dev') {
    $this->dieOnError(
      $this->taskExec(static::$TERMINUS_EXE)
        ->args(
          'drush',
          $site_name . '.' . $env,
          'cr'
        )
        ->run(),
      'Error clearing cache'
    );
    $this->waitForWorkflow($site_name);
    $this->dieOnError(
      $this->taskExec(static::$TERMINUS_EXE)
        ->args(
          'drush',
          $site_name . '.' . $env,
          '--',
          'pm-enable',
          '--yes',
          $this->name,
        )
        ->run(),
      'Error enabling module'
    );

    $this->dieOnError(
      $this->taskExec(static::$TERMINUS_EXE)
        ->args(
          'drush',
          $site_name . '.' . $env,
          'cr'
        )
        ->run(),
      'Error clearing cache after enabling the module.'
    );
  }

  /**
   * Delete sites created in this test run.
   */
  public function deleteSites() {
    $HOME = $this->getHomeDir();
    $file_contents = file_get_contents($HOME . '/.robo-sites-created');
    $filenames = explode("\n", $file_contents);
    foreach ($filenames as $site_name) {
      if ($site_name) {
        $this->output()->writeln("Deleting site $site_name.");
        $this->taskExec(static::$TERMINUS_EXE)
          ->args('site:delete', '-y', $site_name)
          ->run();
      }
    }
  }

  public function getShortRef(): string {
    return trim(
      $this->taskExec('git rev-parse --short HEAD')
        ->run()->getMessage()
    );
  }

  public function dieOnError(Result $result, string $message) {
    if (!$result->wasSuccessful()) {
      $this->output()->write($message);
      throw new TaskException($this, $message);
    }
  }

  /**
   * Update the known_hosts file with the git host and port.
   *
   * @param string $site_name
   *   The machine name of the site to update the known_hosts file for.
   */
  public function updateKnownHosts(string $site_name) {
    $HOME = $this->getHomeDir();
    if (!is_dir(sprintf("%s/.ssh", $HOME))) {
      mkdir(sprintf("%s/.ssh", $HOME));
    }
    $touched = touch(sprintf("%s/.ssh/known_hosts", $HOME));
    if (!$touched) {
      $this->output()->writeln('Failed to create known_hosts file');
      throw new TaskException(
        $this, 'Failed to create known_hosts file: ' .
        sprintf("%s/.ssh/known_hosts", $HOME)
      );
    }

    $this->output()->writeln('Getting the Site Repo: HOME: ' . $HOME);
    // get the git host and port from terminus
    $res = $this->taskExec(static::$TERMINUS_EXE)->args(
      'connection:info',
      $site_name . '.dev',
      '--fields=git_host,git_port',
      '--format=json'
    )->run();
    if (!$res->wasSuccessful()) {
      $this->output()->writeln('Failed to retrieve git host and port');
      throw new TaskException($this, 'Failed to retrieve git host and port');
    }

    // decode the json response
    $gitInfo = json_decode($res->getMessage(), TRUE);
    if (json_last_error() !== JSON_ERROR_NONE) {
      $this->output()->writeln('Failed to decode json response');
      throw new TaskException(
        $this,
        'Failed to decode json response' . json_last_error_msg()
      );
    }
    $this->output()->writeln(
      'Retrieved git host and port' .
      print_r($gitInfo, TRUE)
    );

    // check if the git host and port were retrieved
    if (!isset($gitInfo['git_host']) || !isset($gitInfo['git_port'])) {
      $this->output()->writeln('Failed to retrieve git host and port');
      throw new TaskException($this, 'Failed to retrieve git host and port');
    }
    // Does the known_hosts file exist?
    $this->output()->writeln('Adding the git host to known hosts file');
    $res = $this->taskExec('ssh-keyscan')->args(
      '-p',
      $gitInfo['git_port'],
      $gitInfo['git_host']
    )->run();
    $this->output()->writeln($res->getMessage());
    if (!$res->wasSuccessful()) {
      $this->output()->writeln('Failed to add git host to known hosts file');
      throw new TaskException(
        $this,
        'Failed to add git host to known hosts file'
      );
    }
    // append the git host to the known_hosts file
    $bytesWritten = file_put_contents(
      sprintf("%s/.ssh/known_hosts", $HOME),
      $res->getMessage(),
      FILE_APPEND,
    );
    if ($bytesWritten === FALSE) {
      $this->output()->writeln('Failed to write to known hosts file');
      throw new TaskException($this, 'Failed to write to known hosts file');
    }
  }

  /**
   * Get the current terminus user.
   */
  public function whoami(): string {
    $toReturn = $this->taskExec(static::$TERMINUS_EXE)
      ->args('auth:whoami')->run();
    if (!$toReturn->wasSuccessful()) {
      $this->output()->writeln("whoami: " . $toReturn->getMessage());
      throw new TaskException($this, 'Error getting whoami');
    }
    return trim($toReturn->getMessage());
  }

  /**
   * Get the home directory. If it's not set, use the workspace default folder.
   */
  #[Pure]
  public function getHomeDir(): string {
    return getenv('HOME') ?? '/home/runner';
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
  protected function getLocalCloneDir($site_name): string {
    if (empty($this->localCloneDirectory)) {
      $this->writeLn('Local clone directory not set. Setting default value.');
      $this->localCloneDirectory = $this->getHomeDir(
        ) . DIRECTORY_SEPARATOR . $site_name;
    }
    return $this->localCloneDirectory;
  }

  /**
   * Get information about the given site.
   *
   * @param string $site_name
   *   The machine name of the site to get information about.
   *
   * @return mixed|null
   */
  protected function siteInfo(string $site_name) {
    try {
      exec(
        static::$TERMINUS_EXE . ' site:info --format=json ' . $site_name,
        $output,
        $status
      );
      if (!empty($output)) {
        $result = json_decode(
          join("", $output),
          TRUE,
          512,
          JSON_THROW_ON_ERROR
        );
        return $result;
      }
    }
    catch (Exception $e) {
    }
    catch (Throwable $t) {
    }
    return NULL;
  }

  /**
   * Get current composer constraint depending on whether we're on a tag, a
   * branch or a PR.
   */
  protected function getCurrentConstraint(): string {
    $branch = trim(shell_exec('git rev-parse --abbrev-ref HEAD'));
    if ($branch !== 'HEAD') {
      return "{$branch}-dev";
    }
    else {
      $tag = shell_exec(
        'git describe --exact-match --tags $(git log -n1 --pretty=\'%h\')'
      );
      if ($tag) {
        return trim($tag);
      }
      else {
        // Maybe we are on a PR.
        $branch = $_SERVER['GITHUB_HEAD_REF'];
        $branch_parts = explode('/', $branch);
        $branch = end($branch_parts);
        if ($branch) {
          return "{$branch}-dev";
        }
      }
    }
    // Final fallback, return "^2";
    return '^2';
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
        throw new RuntimeException(
          'No YAML files found in the specified directory.'
        );
      }

      foreach ($finder as $file) {
        $filePath = $file->getRealPath();
        $fileName = $file->getBasename('.yml');

        // Remove .yaml extension if present
        $fileName = str_replace('.yaml', '', $fileName);
        $fileName = str_replace(
          'pantheon_advanced_page_cache.',
          '',
          $fileName
        );

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
        catch (Exception $e) {
          throw new RuntimeException(
            sprintf(
              'Error parsing YAML file %s: %s',
              $filePath,
              $e->getMessage()
            )
          );
        }
      }
    }
    catch (Exception $e) {
      throw new RuntimeException(
        sprintf(
          'Error accessing directory %s: %s',
          $directoryPath,
          $e->getMessage()
        )
      );
    }
  }

  /**
   * Takes the output from a workflow:info:status command and converts it into
   * a human-readable and easily parseable array.
   *
   * @param array $info
   *   Raw output from 'terminus workflow:info:status'
   *
   * @return array An array of workflow status info.
   */
  private function cleanUpInfo(array $info): array {
    // Clean up the workflow status data and assign values to an array so
    // it's easier to check.
    foreach ($info as $line => $value) {
      $ln = array_values(array_filter(explode("  ", trim($value))));

      // Skip lines with only one value. This filters out the ASCII dividers
      // output by the command.
      if (count($ln) > 1) {
        if (in_array($ln[0], ['Started At', 'Finished At'])) {
          $ln[0] = trim(str_replace('At', '', $ln[0]));
          // Convert times to unix timestamps for easier use later.
          $ln[1] = strtotime($ln[1]);
        }

        $info[str_replace(' ', '-', strtolower($ln[0]))] = trim($ln[1]);
      }

      // Remove the processed line.
      unset($info[$line]);
    }

    return $info;
  }

}
