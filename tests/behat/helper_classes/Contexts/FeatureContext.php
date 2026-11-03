<?php

namespace PantheonSystems\CDNBehatHelpers\Contexts;

use Behat\Behat\Context\Context;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use PantheonSystems\CDNBehatHelpers\AgeTracker;
use Drupal\DrupalExtension\Context\RawDrupalContext;

/**
 * Define application features from the specific context.
 *
 * @todo, this class should be abstracted into it's own Behat Extension that can be used to test WordPress, D7 & D8.
 */
final class FeatureContext extends RawDrupalContext implements Context {

  /**
   * Initializes context.
   * Every scenario gets its own context object.
   *
   * @param array $parameters
   *   Context parameters (set them in behat.yml)
   */
  public function __construct(array $parameters = []) {
    // Initialize your context here
  }

  /**
   * @var \Drupal\DrupalExtension\Context\MinkContext
   */
  private $minkContext;

  /**
   * @var \PantheonSystems\CDNBehatHelpers\AgeTracker;
   */
  private $ageTracker;

  /**
   * Users created during the scenario.
   *
   * @var string[]
   */
  private $createdUsers = [];

  /**
   * @BeforeScenario
   */
  public function gatherContexts(BeforeScenarioScope $scope) {
    $environment = $scope->getEnvironment();
    $this->minkContext = $environment->getContext('Drupal\DrupalExtension\Context\MinkContext');
    if ($token = getenv('BOT_BYPASS_TOKEN')) {
      $this->minkContext->getSession()->getDriver()->getClient()->setServerParameter('HTTP_X_PANTHEON_BOT_BYPASS', $token);
    }
  }

  /**
   * @AfterScenario
   */
  public function deleteCreatedUsers() {
    foreach ($this->createdUsers as $name) {
      $this->getDriver('drush')->drush('user:cancel', ['-y', '--reassign-content', escapeshellarg($name)]);
    }
    $this->createdUsers = [];
  }

  /**
   * @Given I am logged in as a user with the :role role
   */
  public function iAmLoggedInAsAUserWithTheRole($role) {
    $drush = $this->getDriver('drush');
    $name = 'behat_' . bin2hex(random_bytes(4));
    $drush->drush('user:create', [escapeshellarg($name)]);
    $this->createdUsers[] = $name;
    $drush->drush('user:role:add', [escapeshellarg($role), escapeshellarg($name)]);
    $base_url = rtrim($this->getMinkParameter('base_url'), '/');
    $output = $drush->drush('user:login', ['--name=' . escapeshellarg($name), '--uri=' . escapeshellarg($base_url)]);
    if (!preg_match('#https?://\S+/user/reset/\S+#', $output, $matches)) {
      throw new \Exception(sprintf('No login link in drush output: %s', $output));
    }
    $this->minkContext->visit($matches[0]);
  }

  /**
   * @When I click :link in the :rowText row
   */
  public function iClickInTheRow($link, $rowText) {
    foreach ($this->minkContext->getSession()->getPage()->findAll('css', 'tr') as $row) {
      if (str_contains($row->getText(), $rowText) && $link_element = $row->findLink($link)) {
        $link_element->click();
        return;
      }
    }
    throw new \Exception(sprintf('No row containing "%s" with a "%s" link', $rowText, $link));
  }

  /**
   * @Given there are some :type nodes
   */
  public function whenIGenerateSomeNodes($type, $number_of_nodes = 2) {
    $i = 0;
    while ($i < $number_of_nodes) {
      $this->whenIGenerateANode($type);
      $i++;
    }
  }

  /**
   * @When a generate a :type node
   */
  public function whenIGenerateANode($type) {
    // Create a node with an HTML form for faster process.
    // Significantly faster than make nodes over SSH/Drush.
    // Depends on a node type with no required fields beyond title.
    $random_node_title = "Random Node Title: " . rand();
    $this->minkContext->visit('node/add/' . $type);
    $this->minkContext->fillField('Title', $random_node_title);
    $this->minkContext->pressButton('Save');
    $this->minkContext->assertTextVisible($random_node_title);
    // @todo, remove this sleep if possible. Added because this faster node generation results in
    // The tests running faster than the CDN can clear cache.
    sleep(2);
    // It seems that sessions get set when adding nodes but are removed
    // on the next page load. So load another page before caching
    // behavior is set.
    $this->minkContext->visit('/');
  }

  /**
   * @Given :page is caching
   */
  public function pageIsCaching($page) {
    $age = $this->getAge($page);
    // A zero age doesn't necessarily mean the page is not caching.
    // A second request may show a higher age.
    if (!empty($age)) {
      return TRUE;
    }
    else {
      sleep(2);
      $age = $this->getAge($page);
      if (empty($age)) {
        throw new \Exception('not cached');
      }
      else {
        return TRUE;
      }
    }
  }

  /**
   * @Then :path has not been purged
   */
  public function assertPathAgeIncreased($path) {
    $age = $this->getAge($path);
    $ageTracker = $this->getAgeTracker();
    if (!$ageTracker->ageIncreasedBetweenLastTwoRequests($path)) {
      throw new \Exception('Cache age did not increase');
    }
  }

  /**
   * @Then :path has been purged
   */
  public function assertPathHasBeenPurged($path) {
    $age = $this->getAge($path);
    $ageTracker = $this->getAgeTracker();
    if (!$ageTracker->wasCacheClearedBetweenLastTwoRequests($path)) {
      throw new \Exception('Cache was not cleared between requests');
    }
  }

  protected function getAge($page) {
    $this->minkContext->visit($page);
    $this->getAgeTracker()->trackSessionHeaders($page, $this->minkContext->getSession());
    $age = $this->minkContext->getSession()->getResponseHeader('Age');
    return $age;
  }

  protected function getAgeTracker() {
    if (empty($this->ageTracker)) {
      $this->ageTracker = new AgeTracker();
    }
    return $this->ageTracker;
  }

  /**
   * @Given there are :number_of_nodes article nodes with a huge number of taxonomy terms each
   */
  public function thereAreArticleNodesWithAHugeNumberOfTaxonomyTermsEach($number_of_nodes) {
    $i = 0;
    while ($i < $number_of_nodes) {
      $this->whenIGenerateAnArticleWithLotsOfTerms();
      $i++;
    }
  }

  /**
   * @When a generate an article with lots of terms
   */
  public function whenIGenerateAnArticleWithLotsOfTerms() {
    $random_node_title = "Random Node Title: " . rand();
    $this->minkContext->visit('node/add/article');
    $this->minkContext->fillField('Title', $random_node_title);
    $this->minkContext->fillField('Tags', $this->generateRandomTaxonomyString());
    $this->minkContext->pressButton('Save');
    $this->minkContext->assertTextVisible($random_node_title);
  }

  /**
   * Generates a long string of tags used on node add form.
   */
  private function generateRandomTaxonomyString() {
    $letters = explode(' ', 'a b c d e f g h i j k l m n o p q r s t u v w x y z');
    $i = 0;
    $random_three_letter_combos = [];
    while ($i < 250) {
      $random_three_letter_combos[] = $letters[rand(0, 25)] . $letters[rand(0, 25)] . $letters[rand(0, 25)];
      $i++;
    }
    return implode(",", $random_three_letter_combos);
  }

}
