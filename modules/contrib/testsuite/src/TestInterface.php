<?php

namespace Drupal\testsuite;

/**
 * Provides an interface defining a testsuite entity.
 */
interface TestInterface {

  /**
   * Gets the module name.
   *
   * @return string
   *   The module name.
   */
  public function getModuleName();

  /**
   * Gets the test name.
   *
   * @return string
   *   If the test succeeded or failed.
   */
  public function getName();

  /**
   * Gets the test description.
   *
   * @return string
   *   If the test succeeded or failed.
   */
  public function getDescription();

  /**
   * Runs the test and returns if the test succeeded or failed.
   *
   * @return string
   *   User defined string.
   */
  public function runTest();

}
