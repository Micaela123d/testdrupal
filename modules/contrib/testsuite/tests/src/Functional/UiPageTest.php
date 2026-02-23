<?php

namespace Drupal\Tests\testsuite\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests that the Test Suite pages are reachable.
 */
class UiPageTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['testsuite', 'phpunit_tests'];

  /**
   * Theme to enable.
   *
   * @var string
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests that the Test Suite config page works.
   */
  public function testReactionTestsuiteConfigPage() {
    $account = $this->drupalCreateUser(['administer site configuration']);
    $this->drupalLogin($account);

    $this->drupalGet("admin/config/testsuite/phpunit_tests");
    $this->assertSession()->statusCodeEquals(200);

    // Test that there is this text on the page.
    $this->assertSession()->pageTextContains('Setting related to creating the phpunit.xml file.');
  }

}
