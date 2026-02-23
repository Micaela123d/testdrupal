<?php

namespace Drupal\custom_tests\CustomTSTests;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\testsuite\TestInterface;

/**
 * Checks For 404 Errors.
 */
class CheckForLibrariesModule implements TestInterface {
  use StringTranslationTrait;

  /**
   * The module name.
   *
   * @var string
   */
  private $moduleName = "Custom Tests";

  /**
   * The name of the test.
   *
   * @var string
   */
  private $name = "Library Checker";

  /**
   * The description of the test.
   *
   * @var string
   */
  private $description = "Checks if the Libraries module is installed.";

  /**
   * Getter for $moduleName.
   *
   * @return string
   *   The module name.
   */
  public function getModuleName() {
    return $this->moduleName;
  }

  /**
   * Getter for $name.
   *
   * @return string
   *   The name of the test.
   */
  public function getName() {
    return $this->name;
  }

  /**
   * Getter for $description.
   *
   * @return string
   *   The description of the test.
   */
  public function getDescription() {
    return $this->description;
  }

  /**
   * Checks if the Libraries module is installed.
   */
  public function runTest() {
    $moduleHandler = \Drupal::service('module_handler');
    if ($moduleHandler->moduleExists('libraries')) {
      return $this->t('Libraries is installed.');
    }
    return $this->t('Libraries is not installed.');
  }

}
