<?php

use Behat\Behat\Context\Context;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use PantheonSystems\CDNBehatHelpers\AgeTracker;
use Drupal\DrupalExtension\Context\RawDrupalContext;

/**
 * Define application features from the specific context.
 */
class FeatureContext extends RawDrupalContext implements Context
{

    /**
    * Initializes context.
    * Every scenario gets its own context object.
    *
    * @param array $parameters
    *   Context parameters (set them in behat.yml)
    */
    public function __construct(array $parameters = [])
    {
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

    /**
     * @Given :page is caching
     */
    public function pageIsCaching($page)
    {

        $age = $this->getAge($page);
        if (!empty($age)) {
            return true;
        } else {
            sleep(2);
            $age = $this->getAge($page);
            if (empty($age)) {
                throw new \Exception('not cached');
            } else {
                return true;
            }
        }
    }

    /**
     * @Then :path has not been purged
     */
    public function assertPathAgeIncreased($path)
    {
        $age = $this->getAge($path);
        $ageTracker = $this->getAgeTracker();
        if (!$ageTracker->ageIncreasedBetweenLastTwoRequests($path)) {
            throw new \Exception('Cache age did not increase');
        }
    }

    /**
     * @Then :path has been purged
     */
    public function assertPathHasBeenPurged($path)
    {
        $age = $this->getAge($path);
        $ageTracker = $this->getAgeTracker();
        if (!$ageTracker->wasCacheClearedBetweenLastTwoRequests($path)) {
            throw new \Exception('Cache was not cleared between requests');
        }
    }

    protected function getAge($page)
    {
        $this->minkContext->visit($page);
        $this->getAgeTracker()->trackHeaders($page, $this->minkContext->getSession()->getResponseHeaders());
        $age = $this->minkContext->getSession()->getResponseHeader('Age');
        return $age;
    }

    protected function getAgeTracker()
    {
        if (empty($this->ageTracker)) {
            $this->ageTracker = new AgeTracker();
        }
        return $this->ageTracker;
    }
}
