# Contributing

Pull requests are accepted on the [GitHub pantheon_advanced_page_cache project](https://github.com/pantheon-systems/pantheon_advanced_page_cache).

### Making a new release

This is an internal process for maintainers.

First, tag the release:

```
$ git tag <x.y.z>
$ git push origin <x.y.z>
```

When you push the tag to GitHub, automation will mirror the tag on the [drupal.org pantheon_advanced_page_cache project](https://www.drupal.org/project/pantheon_advanced_page_cache).

Next, create a new release on GitHub.

- Visit the [new release page on GitHub](https://github.com/pantheon-systems/pantheon_advanced_page_cache/releases/new).
- Select the tag you just pushed.
- Click on the "generate release notes" button. Edit to suit.
- Ensure that "set as pre-release" is NOT checked, and "set as the latest release" is checked. These are the defaults.
- Click on "Publish release".

Note that automation does not create a release on drupal.org, so the process must be repeated again.

- Visit the [create release page on drupal.org](https://www.drupal.org/node/add/project-release/2832253)
- Select the release version that you just pushed, and click "next"
- Convert release notes from [markdown to html](https://markdowntohtml.com/), fixing up to suit.
- Check any that apply from the list of release types, "security update", "bug fixes", and/or "new features" and click "save".
- If you created a new minor release, visit the [administer releases](https://www.drupal.org/node/2832253/edit/releases) page and make it the recommended release, un-checking the previous minor release so that it is no longer supported or recommended. (Do not remove the checks on the 7.x-1.* release.)
