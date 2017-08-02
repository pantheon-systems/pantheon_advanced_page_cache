<?php

use Behat\Behat\Context\Context;
use Behat\Behat\Context\SnippetAcceptingContext;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Drupal\DrupalExtension\Context\MinkContext;
use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\TableNode;
use Behat\Behat\Hook\Scope\AfterStepScope;

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


    /**
     * @Given the listing pages for page and article are cached
     */
    public function theListingPagesForPageAndArticleAreCached()
    {

        $this->minkContext->visit('custom-cache-tags/article');
        $age = $this->getAge();
        print_r($age);
        print_r("
        
        ");

        $this->minkContext->visit('custom-cache-tags/page');
        $age = $this->getAge();
        print_r($age);
        print_r("
        
        ");
        sleep(2);


        $this->minkContext->visit('custom-cache-tags/article');
        $age = $this->getAge();
        print_r($age);
        print_r("
        
        ");
        $this->minkContext->visit('custom-cache-tags/page');
        $age = $this->getAge();
        print_r($age);
        print_r("
        
        ");



//        throw new PendingException();
    }



    /**
     * @Given the article node listing was not purged.
     */
    public function theArticleNodeListingWasNotPurged()
    {

        $this->minkContext->visit('custom-cache-tags/article');
        $age = $this->getAge();
        throw new PendingException();
    }

    protected function getAge() {
        return $this->minkContext->getSession()->getResponseHeader('Age');
    }






}
