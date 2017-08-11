Feature: Views custom cache tags
In order to keep as many pages cached as possible when content is updated
As an administrator
I want to use granular cache tags for Views that reflect the type of content displayed by the View.


  Background:
    When I run drush "pml --status=enabled --type=module"
    Then drush output should contain "views_custom_cache_tag"

    When I run drush "generate-content 20 --types=page,article"
    Then drush output should contain "Finished creating 20 nodes"
    And drush output should contain "Generate process complete."

  @api
  Scenario: Node type-based expiration

    Given "custom-cache-tags/page" is caching
    And "custom-cache-tags/article" is caching

    # When I make a new page node
    When I run drush "generate-content 1 --types=page"
    # It might not be worthwhile to check the output of Drush.
    And drush output should contain "Finished creating 2 nodes"
    And drush output should contain "Generate process complete."

    # Then the page listing is cleared the article is not
    Then "custom-cache-tags/page" has been purged
    And "custom-cache-tags/page" is caching
    And "custom-cache-tags/article" has not been purged