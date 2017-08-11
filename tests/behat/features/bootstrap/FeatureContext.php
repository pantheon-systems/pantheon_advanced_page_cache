<?php

use Behat\Behat\Context\Context;
use Behat\Behat\Context\SnippetAcceptingContext;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Drupal\DrupalExtension\Context\MinkContext;
use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\TableNode;
use Behat\Behat\Hook\Scope\AfterStepScope;
use PantheonSystems\CDNBehatHelpers\AgeTracker;
use Drupal\DrupalExtension\Context\RawDrupalContext;


/**
 * Define application features from the specific context.
 */
class FeatureContext extends RawDrupalContext implements Context, SnippetAcceptingContext {
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

  /** @var \Drupal\DrupalExtension\Context\MinkContext */
  private $minkContext;


  private $ageTracker;

  /** @BeforeScenario */
  public function gatherContexts(BeforeScenarioScope $scope)
  {
      $environment = $scope->getEnvironment();
      $this->minkContext = $environment->getContext('Drupal\DrupalExtension\Context\MinkContext');
  }

//
// Place your definition and hook methods here:
//
//  /**
//   * @Given I have done something with :stuff
//   */
//  public function iHaveDoneSomethingWith($stuff) {
//    doSomethingWith($stuff);
//  }
//


    /**
     * @Given I wait for the progress bar to finish
     */
    public function iWaitForTheProgressBarToFinish() {
      $this->iFollowMetaRefresh();
    }

    /**
     * @Given I follow meta refresh
     *
     * https://www.drupal.org/node/2011390
     */
    public function iFollowMetaRefresh() {
      while ($refresh = $this->getSession()->getPage()->find('css', 'meta[http-equiv="Refresh"]')) {
        $content = $refresh->getAttribute('content');
        $url = str_replace('0; URL=', '', $content);
        $this->getSession()->visit($url);
      }
    }


    /**
     * @Given I wait :seconds seconds
     */
    public function iWaitSeconds($seconds)
    {
        sleep($seconds);
    }


    /**
     * @AfterStep
     */
    public function afterStep(AfterStepScope $scope)
    {
        // Do nothing on steps that pass
        $result = $scope->getTestResult();
        if ($result->isPassed()) {
            return;
        }

        // Otherwise, dump the page contents.
        $session = $this->getSession();
        $page = $session->getPage();
        $html = $page->getContent();
        $html = static::trimHead($html);

        print "::::::::::::::::::::::::::::::::::::::::::::::::\n";
        print $html . "\n";
        print "::::::::::::::::::::::::::::::::::::::::::::::::\n";
    }

    /**
     * Remove everything in the '<head>' element except the
     * title, because it is long and uninteresting.
     */
    protected static function trimHead($html)
    {
        $html = preg_replace('#\<head\>.*\<title\>#sU', '<head><title>', $html);
        $html = preg_replace('#\</title\>.*\</head\>#sU', '</title></head>', $html);
        return $html;
    }

    /**
     * @Given :page is caching
     */
    public function pageIsCaching($page) {

        $age = $this->getAge($page);
        if (!empty($age)) {
            return TRUE;
        }
        else {
            sleep(2);
            $age = $this->getAge($page);
            if (empty($age)) {
                throw new \Exception('not cached');
            } else {
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
        if (!$ageTracker->AgeIncreasedBetweenLastTwoRequests($path)) {
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
        $this->getAgeTracker()->trackHeaders($page, $this->minkContext->getSession()->getResponseHeaders());
        $age = $this->minkContext->getSession()->getResponseHeader('Age');
        return $age;
    }

    protected function getAgeTracker() {
        if (empty($this->ageTracker)) {
            $this->ageTracker = new AgeTracker();
        }
        return $this->ageTracker;
    }
}
