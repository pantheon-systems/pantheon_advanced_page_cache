Feature: Node cache tags
In order to serve fresh content
As an administrator
I want caches for node pages to clear immediately after nodes are saved.

  @api
  Scenario: Normal expiration
  #    Given a node
  #    When I clear all caches on the site
  #    That node's page cache age will be near zero
  #    And the age will increase until reach the max age for the page.
  #    And then the age will reset to near zero.

    Given I am logged in as a user with the "administrator" role
    When I visit "node/add/article"
    And I fill in "Title" with "Test article title"
    And I press the "Save and publish" button
    When I visit "admin/content"
    Then I should see the text "Test article"
