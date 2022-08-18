# Deployment Guide

This document describes the workflow for deploying updates from GitHub to a published package.

## Merge Pull Requests

Merge the approved Pull Request(s) to the `main` branch.

An *approved* Pull Request is when a PR passes all tests **and** has been approved by **one or more** reviewers.

## Create a New Release

[Create a New Release](https://github.com/ConvertKit/convertkit-wordpress/releases/new), completing the following:

- Choose a tag: Click this button and enter the new version number (e.g. `1.0.1`)
- Release title: The version number (e.g. `1.0.1`)
- Describe this release: Enter a bullet point list of changes.  A good example and format:

```
* Added: API Class: `get_products()` function.
* Fix: API Class: Return array instead of WP_Error when no resources exist.
```

![New Release Screen](/.github/docs/new-release.png?raw=true)

## Publish the Release

When you're happy with the above, click `Publish Release`.

## Use the Published Release in ConvertKit Plugins

To use the published release in ConvertKit Plugins, **in the ConvertKit Plugin** edit the `composer.json` file to reflect the published version:

```
"require": {
    "convertkit/wordpress-libs": "~1.0.1"
},
```