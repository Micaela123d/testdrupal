# Test Suite

## Table of contents

- Introduction
- Requirements
- Installation
- Configuration
- Maintainers

## Introduction

This module is a combination of 5 modules that together let a site admin test their Drupal installations codebase.

**TestSuite**: This the base module and must always be installed for the others to work. Run your own tests that do not require any additional packages. It uses a custom module that you can find in test_module folder. Copy it into the modules/custom folder, erase the .txt on the .info.yml file and enable it. There are example tests in the module.  
**List Packages**: Has moved to [Vulnerability Checker](https://www.drupal.org/project/vulnerability_checker)  
**PHPUnit**: Run Unit and Functional phpunit tests on modules and themes to check that the code runs as expected.  
**PHPcs**: Drupal modules have to go through PHPCs testing to insure they are up to Drupal Standards when submitting a module to drupal.org. This module lets you see if there are any of these violations in the code base.  
**PHPStan**: Is another standard to hold your code to ensure that the code base is error free and will run well in the drupal installation.

**PLEASE BE ADVISED THAT VERSIONS PRIOR TO 3.0.37-beta1 HAVE A POSSIBLE SECURITY RISK.** 3.0.37-beta1 fixes permissions on route by changing 'access site reports' to 'administer site configuration', adds route regex to filter parameters and adds validation on parameters passed to the getStatement and getFileStatment functions to fix the security vulnerability. **PLEASE BE ADVISED THAT 3.0.37-beta1 HAS A SECURITY FIX. PLEASE DO NOT USE PRIOR VERSIONS. THANKS.**

For a full description of the module, visit the
[project page](https://www.drupal.org/project/testsuite).

Submit bug reports and feature suggestions, or track changes in the
[issue queue](https://www.drupal.org/project/issues/testsuite).

## Requirements

A Drupal site installed via composer.
Installing Drupal with composer. [Installing Drupal](https://www.drupal.org/docs/develop/using-composer/manage-dependencies)  
A composer.json file will have a require-dev section with all the dev packages needed for phpunit testing. Depending on the Drupal version you are using the phpunit packages versions can vary too. If you need to get a list of the correct packages for your installation you can get them here. [Drupal Versions](https://github.com/drupal/drupal/blob/8.9.x/composer.json)

## Installation

[Installation](https://www.youtube.com/watch?v=ZZdaJ9Shje4)

## Install using composer.
1) install wikimedia/composer-merge-plugin
```
composer require wikimedia/composer-merge-plugin
```
This uses the [wikimedia/composer-merge-plugin](https://github.com/wikimedia/composer-merge-plugin). Read for more information on installation. Add ```modules/contrib/testsuite/composer.json``` or ```web/modules/contrib/testsuite/composer.json``` depending on your path.

In your composer.json in the root of your drupal installation add the following code block under extra after the installer-paths section. Depending on the installation you may have to omit the web/ directory.

```
"merge-plugin": {
   "include": [
        "web/modules/contrib/testsuite/composer.json"
    ],
   "replace": false,
   "ignore-duplicates": true
},
```

2) install drupal/testsuite
composer require drupal/testsuite

3) run composer update
This will download if not already downloaded all the dev packages needed for testsuite to work.

4) Go to Extend and install all the TestSuite modules.

5) If using PHPUnit got to Configuration -> Test Suite -> PHPUnit Config and click save.

6) Go to Reports -> Testsuite and Reports -> Vulnerability Report to build and work with TestSuite.

## Configuration

Configuration is located at Configuration -> Test Suite.

You need to at least click the save button at Configuration -> Test Suite -> PHPUnit Config to use PHPUnit testing.

## Maintainers

### Current maintainers

- [Trigve Hagen](https://www.drupal.org/u/trigve-hagen)
