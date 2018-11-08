Feature: Generate Deadlocks
# When two instances of this scenario are run simulanteously across different terminal windows it will trigger at deadlock.

  @api @deadlock
  Scenario: Warning message.
    Given I run drush "en -y pantheon_advanced_page_cache_test pantheon_advanced_page_cache"
    Given I am logged in as a user with the "administrator" role
    And there are 10 article nodes with a huge number of taxonomy terms each
    And there are 10 article nodes with a huge number of taxonomy terms each
    And there are 10 article nodes with a huge number of taxonomy terms each
    And there are 10 article nodes with a huge number of taxonomy terms each
    And there are 10 article nodes with a huge number of taxonomy terms each
    And there are 10 article nodes with a huge number of taxonomy terms each
    And there are 10 article nodes with a huge number of taxonomy terms each
    And there are 10 article nodes with a huge number of taxonomy terms each
    And there are 10 article nodes with a huge number of taxonomy terms each
    And there are 10 article nodes with a huge number of taxonomy terms each
    And there are 10 article nodes with a huge number of taxonomy terms each
    And there are 10 article nodes with a huge number of taxonomy terms each
    And there are 10 article nodes with a huge number of taxonomy terms each
    And there are 10 article nodes with a huge number of taxonomy terms each
    And there are 10 article nodes with a huge number of taxonomy terms each
    And there are 10 article nodes with a huge number of taxonomy terms each
    And there are 10 article nodes with a huge number of taxonomy terms each
    And there are 10 article nodes with a huge number of taxonomy terms each
    And there are 10 article nodes with a huge number of taxonomy terms each
    And there are 10 article nodes with a huge number of taxonomy terms each
    And there are 10 article nodes with a huge number of taxonomy terms each
    And there are 10 article nodes with a huge number of taxonomy terms each
    And there are 10 article nodes with a huge number of taxonomy terms each
    And there are 10 article nodes with a huge number of taxonomy terms each
    And there are 10 article nodes with a huge number of taxonomy terms each
    And there are 10 article nodes with a huge number of taxonomy terms each
    And there are 10 article nodes with a huge number of taxonomy terms each
    And there are 10 article nodes with a huge number of taxonomy terms each
    And there are 10 article nodes with a huge number of taxonomy terms each
    And there are 10 article nodes with a huge number of taxonomy terms each
    And there are 10 article nodes with a huge number of taxonomy terms each
    And there are 10 article nodes with a huge number of taxonomy terms each
    And there are 10 article nodes with a huge number of taxonomy terms each
    And there are 10 article nodes with a huge number of taxonomy terms each
    And there are 10 article nodes with a huge number of taxonomy terms each
    And there are 10 article nodes with a huge number of taxonomy terms each
    And there are 10 article nodes with a huge number of taxonomy terms each
    And there are 10 article nodes with a huge number of taxonomy terms each
    And there are 10 article nodes with a huge number of taxonomy terms each
    And there are 10 article nodes with a huge number of taxonomy terms each
    And there are 10 article nodes with a huge number of taxonomy terms each
    And there are 10 article nodes with a huge number of taxonomy terms each
    And there are 10 article nodes with a huge number of taxonomy terms each
    And there are 10 article nodes with a huge number of taxonomy terms each
    And there are 10 article nodes with a huge number of taxonomy terms each
