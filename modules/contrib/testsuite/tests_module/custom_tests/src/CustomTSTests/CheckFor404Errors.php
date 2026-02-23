<?php

namespace Drupal\custom_tests\CustomTSTests;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\testsuite\TestInterface;

/**
 * Checks For 404 Errors.
 */
class CheckFor404Errors implements TestInterface {
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
  private $name = "Check For 404 Errors";

  /**
   * The description of the test.
   *
   * @var string
   */
  private $description = "Checks watchdog database for 404 errors.";

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
   * Tests that checks for 404 errors.
   */
  public function runTest() {
    $count = \Drupal::database()->select('watchdog', 'w')
      ->fields('w', ['wid'])
      ->condition('type', 'page not found')
      ->countQuery()
      ->execute()
      ->fetchField();
    if ($count == 0) {
      return $this->t('Test Passed');
    }
    else {
      return $this->t(
            'You have 404 errors. Please go to <a href="@page-not-found">Top page not found errors</a> to view them.', [
              '@page-not-found' => '/admin/reports/page-not-found',
            ]
        );
    }
  }

}
