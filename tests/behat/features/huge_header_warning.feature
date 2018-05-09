Feature: Huge Header Warning
In order to understand the caching behavior of my site
As an administrator
I need a notification when my header is truncated.

  Background:

    When I run drush "en -y pantheon_advanced_page_cache"
    # @todo, enable front page view
    And drush output should contain "pantheon_advanced_page_cache is already enabled."

  @api
  Scenario: Warning message.
    Given there are some "article" nodes with a huge number of taxonomy terms each
    When I visit "/frontpage"
    And I visit the report page
    And I click the link in the row for pantheon_advanced_page_cache
    Then I see the warning message
    And I see "/frontpage" in the referrer row

  Scenario: Warning message.
    Given there are some "article" nodes with a huge number of taxonomy terms each
    And I have purged the dblog
    When I visit "/frontpage"
    And I visit the reports page
    Then I don't see pantheon_advanced_page_cache
