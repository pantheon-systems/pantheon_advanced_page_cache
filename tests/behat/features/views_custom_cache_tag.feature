Feature: Views custom cache tags
In order to keep as many pages cached as possible when content is updated
As an administrator
I want to use granular cache tags for Views that reflect the type of content displayed by the View.

  @api
  Scenario: Normal expiration





#    Given I am logged in as a user with the "administrator" role
#
#    # Given that the views custom cache tag example module is enabled
#    And I run drush "pml --status=enabled --type=module"
#    And drush output should contain "views_custom_cache_tag"
#
#    # And there are nodes of type page and article
#    And I run drush "generate-content 20 --types=page,article"
#    And drush output should contain "Finished creating 20 nodes"
#    And drush output should contain "Generate process complete."
#
#
    And the listing pages for page and article are caching
 #   And I break


##    When I make nodes of type "page"
    And I run drush "generate-content 2 --types=page"
    And drush output should contain "Finished creating 2 nodes"
    And drush output should contain "Generate process complete."
##
    Then I see that the cache for the page node listing has been purged
    And the age increases again on subsequent requests to the page node listing
    And the article node listing was not purged.
