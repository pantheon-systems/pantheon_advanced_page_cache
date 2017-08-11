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
//        print $html . "\n";
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


    public function pageIsCaching($page) {

        $age = $this->getAge($page);
        if (!empty($age)) {
            return TRUE;
        }
        else {
            sleep(3);
            $age = $this->getAge($page);
            if (empty($age)) {
                throw new \Exception('not cached');
            } else {
                return TRUE;
            }

        }
    }

    /**
     * @Given the listing pages for page and article are caching
     */
    public function theListingPagesForPageAndArticleAreCached()
    {
        $this->pageIsCaching('custom-cache-tags/article');
        $this->pageIsCaching('custom-cache-tags/page');



        print_r($this->getAgeTracker()->getTrackedHeaders('custom-cache-tags/article'));


    }



    /**
     * @Given the article node listing was not purged.
     */
    public function theArticleNodeListingWasNotPurged()
    {
        $path = 'custom-cache-tags/article';
        $this->assertPathAgeIncreased($path);
    }




    protected function assertPathAgeIncreased($path) {
        $age = $this->getAge($path);
        if (!$this->pathAgeIncreased($path)) {


            throw new \Exception('Cache age did not increase');
        }
        print_r($this->getAgeTracker());
    }



     protected function assertPathHasNotBeenPurged($path) {
         if ($this->pathHasBeenPurged($path)) {


             throw new \Exception('Cache was cleared between requests');
         }
         print_r($this->getAgeTracker());
     }

    protected function assertPathHasBeenPurged($path) {
        if (!$this->pathHasBeenPurged($path)) {
            throw new \Exception('Cache was not cleared between requests');
        }
    }

    /**
     * @Then I see that the cache for the page node listing has been purged
     */
    public function iSeeThatTheCacheForThePageNodeListingHasBeenPurged()
    {
        $path = 'custom-cache-tags/page';
        $this->assertPathHasBeenPurged($path);
    }

    protected function pathAgeIncreased($path)
    {
        $ageTracker = $this->getAgeTracker();
        return $ageTracker->AgeIncreasedBetweenLastTwoRequests($path);
    }


    protected function pathHasBeenPurged($path)
    {
        $age = $this->getAge($path);
        $ageTracker = $this->getAgeTracker();
        return $ageTracker->wasCacheClearedBetweenLastTwoRequests($path);
    }

    /**
     * @Then the age increases again on subsequent requests to the page node listing
     */
    public function theAgeIncreasesAgainOnSubsequentRequestsToThePageNodeListing()
    {
        sleep(2);
        $path = 'custom-cache-tags/page';
        $this->assertPathAgeIncreased($path);
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
