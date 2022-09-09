# Development Guide

This document describes the high level workflow used when working on the ConvertKit WordPress Libraries.

You're free to use your preferred IDE and Git client.

## Prerequisites

If you haven't yet set up your local development environment with the ConvertKit WordPress Libraries repository installed, refer to the [Setup Guide](SETUP.md).

## Create a Branch

In your Git client / command line, create a new branch:
- If this is for a new feature that does not have a GitHub Issue number, enter a short descriptive name for the branch, relative to what you're working on
- If this is for a feature/bug that has a GitHub Issue number, enter issue-XXX, replacing XXX with the GitHub issue number

Once done, make sure you've switched to your new branch, and begin making the necessary code additions/changes/deletions.

## Using this Branch in a ConvertKit Plugin

To reflect the changes you're making to this library in a ConvertKit Plugin, edit the `composer.json` file in that Plugin (not this repository),
changing `{branch_name}` to the branch name you created in the `Create a Branch` step above.
```
"require": {
    "convertkit/convertkit-wordpress-libraries": "dev-{branch_name}"
},
```

For example, if our branch name is `products-api`, the `composer.json` file in the ConvertKit Plugin (not this repository) would read:
```
"require": {
    "convertkit/convertkit-wordpress-libraries": "dev-products-api"
},
```

The `dev-` prefix tells Composer that we want to use a specific branch of this repository in our ConvertKit Plugin. [Read more](https://getcomposer.org/doc/articles/versions.md#vcs-tags-and-branches).

## Coding Standards

Code must follow [WordPress Coding standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/), which is checked
when running tests (more on this below).

## Security and Sanitization

When [outputting data](https://developer.wordpress.org/plugins/security/securing-output/), escape it using WordPress' escaping functions such as `esc_html()`, `esc_attr__()`, `wp_kses()`, `wp_kses_post()`.

When reading [user input](https://developer.wordpress.org/plugins/security/securing-input/), sanitize it using WordPress' sanitization functions such as `sanitize_text_field()`, `sanitize_textarea_field()`.

When writing to the database, prepare database queries using ``$wpdb->prepare()``

Never trust user input. Sanitize it.

Make use of [WordPress nonces](https://codex.wordpress.org/WordPress_Nonces) for saving form submitted data.

Coding standards will catch any sanitization, escaping or database queries that aren't prepared.

## Committing Work

Remember to commit your changes to your branch relatively frequently, with a meaningful, short summary that explains what the change(s) do.
This helps anyone looking at the commit history in the future to find what they might be looking for.

If it's a particularly large commit, be sure to include more information in the commit's description.

## Next Steps

Once you've finished your feature or issue, you must write/amend tests for it.  Refer to the [Testing Guide](TESTING.md) for a detailed walkthrough
on how to write a test.