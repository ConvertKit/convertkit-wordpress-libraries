# Testing Guide

This document describes how to:
- create and run tests for your development work,
- ensure code meets PHP and WordPress Coding Standards, for best practices and security,
- ensure code passes static analysis, to catch potential errors that tests might miss

If you're new to creating and running tests, this guide will walk you through how to do this.

For those more experienced with creating and running tests, our tests are written in PHP using [wp-browser](https://wpbrowser.wptestkit.dev/) 
and [Codeception](https://codeception.com/docs/01-Introduction).

## Prerequisites

If you haven't yet set up your local development environment with the ConvertKit WordPress Library repository installed, refer to the [Setup Guide](SETUP.md).

If you haven't yet created a branch and made any code changes to the Library, refer to the [Development Guide](DEVELOPMENT.md)

## Write (or modify) a test

If you're new to testing, or testing within WordPress, there's a detailed Codeception guide in the main ConvertKit Plugin.

For the WordPress Libraries, you'll most likely only need a WordPress Unit Test.

## Writing a WordPress Unit Test

WordPress Unit tests provide testing of Plugin specific functions and/or classes, typically to assert that they perform as expected
by a developer.  This is primarily useful for testing our API class, and confirming that any Plugin registered filters return
the correct data.

To create a new WordPress Unit Test, at the command line in the Plugin's folder, enter the following command, replacing `APITest`
with a meaningful name of what the test will perform:

```bash
php vendor/bin/codecept generate:wpunit wpunit APITest
```

This will create a PHP test file in the `tests/wpunit` directory called `APITest.php`

```php
<?php

class APITest extends \Codeception\TestCase\WPTestCase
{
    /**
     * @var \WpunitTester
     */
    protected $tester;
    
    public function setUp(): void
    {
        // Before...
        parent::setUp();

        // Your set up methods here.
    }

    public function tearDown(): void
    {
        // Your tear down methods here.

        // Then...
        parent::tearDown();
    }

    // Tests
    public function test_it_works()
    {
        $post = static::factory()->post->create_and_get();
        
        $this->assertInstanceOf(\WP_Post::class, $post);
    }
}
```

Helpers can be used for WordPress Unit Tests, similar to how they can be used for acceptance tests.
To register your own helper function, add it to the `tests/_support/Helper/Wpunit.php` file.

## Run Tests

Once you have written your code and test(s), run the tests to make sure there are no errors.

If ChromeDriver isn't running, open a new Terminal window and enter the following command:

```bash
chromedriver --url-base=/wd/hub
```

To run the tests, enter the following commands in a separate Terminal window:

```bash
vendor/bin/codecept build
vendor/bin/codecept run wpunit
```

If a test fails, you can inspect the output and screenshot at `tests/_output`.

Any errors should be corrected by making applicable code or test changes.

## Run PHP CodeSniffer

[PHP_CodeSniffer](https://github.com/squizlabs/PHP_CodeSniffer) checks that all Plugin code meets the 
[WordPress Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/).

In the Plugin's directory, run the following command to run PHP_CodeSniffer, which will check the code meets WordPress' Coding Standards:

```bash
vendor/bin/phpcs ./ -v -s
```

`-v` produces verbose output, and `-s` specifies the precise rule that failed:
![Coding Standards Screenshot](/.github/docs/coding-standards-error.png?raw=true)

Any errors should be corrected by either:
- making applicable code changes
- (Experimental) running `vendor/bin/phpcbf ./ -v` to automatically fix coding standards

Need to change the PHP or WordPress coding standard rules applied?  Either:
- ignore a rule in the affected code, by adding `phpcs:ignore {rule}`, where {rule} is the given rule that failed in the above output.
- edit the [phpcs.xml](phpcs.xml) file.

**Rules should be ignored with caution**, particularly when sanitizing and escaping data.

## Run PHPStan

[PHPStan](https://phpstan.org) performs static analysis on the Plugin's PHP code.  This ensures:

- DocBlocks declarations are valid and uniform
- DocBlocks declarations for WordPress `do_action()` and `apply_filters()` calls are valid
- Typehinting variables and return types declared in DocBlocks are correctly cast
- Any unused functions are detected
- Unnecessary checks / code is highlighted for possible removal
- Conditions that do not evaluate can be fixed/removed as necessary

In the Plugin's directory, run the following command to run PHPStan:

```bash
vendor/bin/phpstan --memory-limit=1G
```

Any errors should be corrected by making applicable code changes.

False positives [can be excluded by configuring](https://phpstan.org/user-guide/ignoring-errors) the `phpstan.neon` file.

## Next Steps

Once your test(s) are written and successfully run locally, submit your branch via a new [Pull Request](https://github.com/ConvertKit/convertkit-wordpress/compare).

It's best to create a Pull Request in draft mode, as this will trigger all tests to run as a GitHub Action, allowing you to
double check all tests pass.

If the PR tests fail, you can make code changes as necessary, pushing to the same branch.  This will trigger the tests to run again.

If the PR tests pass, you can publish the PR, assigning some reviewers.