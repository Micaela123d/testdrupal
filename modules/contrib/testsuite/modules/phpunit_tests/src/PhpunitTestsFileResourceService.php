<?php

namespace Drupal\phpunit_tests;

use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\testsuite\BaseFileService;

/**
 * PHPUnit Tests File Services.
 */
class PhpunitTestsFileResourceService extends BaseFileService {

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * The file system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * The path to the phpunit.xml.dist file.
   *
   * @var string
   */
  protected $phpunitXmlDistPath;

  /**
   * The path to the phpunit.xml file.
   *
   * @var string
   */
  protected $phpunitXmlPath;

  /**
   * The path to the core folder.
   *
   * @var string
   */
  protected $phpunitCore;

  /**
   * The base path.
   *
   * @var string
   */
  protected $simpleTestPath;

  /**
   * The browser output directory.
   *
   * @var string
   */
  public $browserOutputDirectory;

  /**
   * Initializer Service constructor.
   *
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger interface.
   * @param \Drupal\Core\File\FileSystemInterface $fileSystem
   *   The file system interface.
   */
  public function __construct(
    MessengerInterface $messenger,
    FileSystemInterface $fileSystem,
  ) {
    $this->messenger = $messenger;
    $this->fileSystem = $fileSystem;
    $this->phpunitCore = $fileSystem->realpath('core');
    $this->simpleTestPath = $fileSystem->realpath('sites') . DIRECTORY_SEPARATOR . 'simpletest';
    $this->phpunitXmlDistPath = $fileSystem->realpath('core') . DIRECTORY_SEPARATOR . 'phpunit.xml.dist';
    $this->phpunitXmlPath = $fileSystem->realpath('core') . DIRECTORY_SEPARATOR . 'phpunit.xml';
    $this->browserOutputDirectory = $fileSystem->realpath('sites') . DIRECTORY_SEPARATOR . 'simpletest' . DIRECTORY_SEPARATOR . 'browser_output';
  }

  /**
   * Checks to see if the browser_output folder is writable.
   *
   * @param string $outputDir
   *   The output directory.
   */
  private function ifBrowserOutputIsWritable($outputDir) {
    if (is_writable($outputDir)) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Creates the browser_output file if it does not exist.
   *
   * @param string $outputDir
   *   The output directory.
   */
  public function createBrowserOutputFolderIfNotExists($outputDir) {
    if ($this->simpleTestPath && !is_dir($this->simpleTestPath)) {
      mkdir($this->simpleTestPath, 0777);
      $this->messenger->addStatus('The directory was created.');
    }
    if (!is_dir($outputDir)) {
      mkdir($outputDir, 0777);
      $this->messenger->addStatus('The browser_output directory was created in sites\simpletest directory.');
    }
    if (!$this->ifBrowserOutputIsWritable($outputDir)) {
      $this->messenger->addStatus('The browser_output folder is not writeable.');
    }
  }

  /**
   * Rebuilds the phpunit.xml with config variables.
   *
   * @param array $data
   *   The data to populate rewrite parameters.
   */
  public function rebuildPhpUnitXml($data) {
    $content = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    $content .= "\n";
    $content .= '<!-- For how to customize PHPUnit configuration, see core/tests/README.md. -->' . "\n";
    $content .= '<!-- TODO set checkForUnintentionallyCoveredCode="true" once https://www.drupal.org/node/2626832 is resolved. -->' . "\n";
    $content .= '<!-- PHPUnit expects functional tests to be run with either a privileged user' . "\n";
    $content .= 'or your current system user. See core/tests/README.md and' . "\n";
    $content .= 'https://www.drupal.org/node/2116263 for details.' . "\n";
    $content .= '-->' . "\n";
    $content .= '<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"' . "\n";
    $content .= '        bootstrap="tests/bootstrap.php" colors="true"' . "\n";
    $content .= '        beStrictAboutTestsThatDoNotTestAnything="true"' . "\n";
    $content .= '        beStrictAboutOutputDuringTests="true"' . "\n";
    $content .= '        beStrictAboutChangesToGlobalState="true"' . "\n";
    $content .= '        failOnWarning="true"' . "\n";
    $content .= '        printerClass="\Drupal\Tests\Listeners\HtmlOutputPrinter"' . "\n";
    $content .= '        cacheResult="false"' . "\n";
    $content .= '        xsi:noNamespaceSchemaLocation="https://schema.phpunit.de/9.3/phpunit.xsd">' . "\n";
    $content .= '  <php>' . "\n";
    $content .= '    <!-- Set error reporting to E_ALL. -->' . "\n";
    $content .= '    <ini name="error_reporting" value="32767"/>' . "\n";
    $content .= '    <!-- Do not limit the amount of memory tests take to run. -->' . "\n";
    $content .= '    <ini name="memory_limit" value="-1"/>' . "\n";
    $content .= '    <!-- Example SIMPLETEST_BASE_URL value: http://localhost -->' . "\n";
    $content .= '    <env name="SIMPLETEST_BASE_URL" value="' . $data['simple_base_url'] . '/"/>' . "\n";
    $content .= '    <!-- Example SIMPLETEST_DB value: mysql://username:password@localhost/databasename#table_prefix -->' . "\n";
    $content .= '    <env name="SIMPLETEST_DB" value="mysql://' . $data['mysql_username'] . ':' . $data['mysql_password'] . '@' . $data['mysql_host'] . ':' . $data['mysql_port'] . '/' . $data['mysql_database_name'] . '"/>' . "\n";
    $content .= '    <!-- Example BROWSERTEST_OUTPUT_DIRECTORY value: /path/to/webroot/sites/simpletest/browser_output -->' . "\n";
    $content .= '    <env name="BROWSERTEST_OUTPUT_DIRECTORY" value="' . $data['browser_output_directory'] . '"/>' . "\n";
    $content .= '    <!-- By default, browser tests will output links that use the base URL set' . "\n";
    $content .= '    in SIMPLETEST_BASE_URL. However, if your SIMPLETEST_BASE_URL is an internal' . "\n";
    $content .= '    path (such as may be the case in a virtual or Docker-based environment),' . "\n";
    $content .= '    you can set the base URL used in the browser test output links to something' . "\n";
    $content .= '    reachable from your host machine here. This will allow you to follow them' . "\n";
    $content .= '    directly and view the output. -->' . "\n";
    $content .= '    <env name="BROWSERTEST_OUTPUT_BASE_URL" value="' . $data['browser_output_base_url'] . '"/>' . "\n";
    $content .= "\n";
    $content .= '    <!-- Deprecation testing is managed through Symfony\'s PHPUnit Bridge.' . "\n";
    $content .= '    The environment variable SYMFONY_DEPRECATIONS_HELPER is used to configure' . "\n";
    $content .= '    the behaviour of the deprecation tests.' . "\n";
    $content .= '    See https://symfony.com/doc/current/components/phpunit_bridge.html#configuration' . "\n";
    $content .= '    Drupal core\'s testing framework is setting this variable to its defaults.' . "\n";
    $content .= '    Projects with their own requirements need to manage this variable' . "\n";
    $content .= '    explicitly.' . "\n";
    $content .= '    -->' . "\n";
    $content .= '    <!-- To disable deprecation testing completely uncomment the next line. -->' . "\n";
    if ($data['disable_deprecation_testing'] == 1) {
      $content .= '    <env name="SYMFONY_DEPRECATIONS_HELPER" value="disabled"/>' . "\n";
    }
    else {
      $content .= '    <!-- <env name="SYMFONY_DEPRECATIONS_HELPER" value="disabled"/> -->' . "\n";
    }
    $content .= '    <!-- Deprecation errors can be selectively ignored by specifying a file of' . "\n";
    $content .= '    regular expression patterns for exclusion.' . "\n";
    $content .= '    See https://symfony.com/doc/current/components/phpunit_bridge.html#ignoring-deprecations' . "\n";
    $content .= '    Uncomment the line below to specify a custom deprecations ignore file.' . "\n";
    $content .= '    NOTE: it may be required to specify the full path to the file to run tests' . "\n";
    $content .= '    correctly.' . "\n";
    $content .= '    -->' . "\n";
    $content .= '    <!-- <env name="SYMFONY_DEPRECATIONS_HELPER" value="ignoreFile=.deprecation-ignore.txt"/> -->' . "\n";
    if ($data['disable_deprecation_testing'] == 2) {
      $content .= '    <env name="SYMFONY_DEPRECATIONS_HELPER" value="' . $data['configure_deprecation_helper'] . '"/>' . "\n";
    }
    $content .= "\n";
    $content .= '    <!-- Example for changing the driver class for mink tests MINK_DRIVER_CLASS value: \'Drupal\FunctionalJavascriptTests\DrupalSelenium2Driver\' -->' . "\n";
    $content .= '    <env name="MINK_DRIVER_CLASS" value=\'' . $data['mink_driver_class'] . '\'/>' . "\n";
    $content .= '    <!-- Example for changing the driver args to mink tests MINK_DRIVER_ARGS value: \'["http://127.0.0.1:8510"]\' -->' . "\n";
    $content .= '    <env name="MINK_DRIVER_ARGS" value=\'' . $data['mink_driver_args'] . '\'/>' . "\n";
    $content .= '    <!-- Example for changing the driver args to webdriver tests MINK_DRIVER_ARGS_WEBDRIVER value: \'["chrome", { "chromeOptions": { "w3c": false } }, "http://localhost:4444/wd/hub"]\' For using the Firefox browser, replace "chrome" with "firefox" -->' . "\n";
    $content .= '    <env name="MINK_DRIVER_ARGS_WEBDRIVER" value=\'' . $data['mink_driver_args_webdriver'] . '\'/>' . "\n";
    $content .= '  </php>' . "\n";
    $content .= '   <testsuites>' . "\n";
    $content .= '    <testsuite name="unit">' . "\n";
    $content .= '      <file>./tests/TestSuites/UnitTestSuite.php</file>' . "\n";
    $content .= '    </testsuite>' . "\n";
    $content .= '    <testsuite name="kernel">' . "\n";
    $content .= '      <file>./tests/TestSuites/KernelTestSuite.php</file>' . "\n";
    $content .= '    </testsuite>' . "\n";
    $content .= '    <testsuite name="functional">' . "\n";
    $content .= '      <file>./tests/TestSuites/FunctionalTestSuite.php</file>' . "\n";
    $content .= '    </testsuite>' . "\n";
    $content .= '    <testsuite name="functional-javascript">' . "\n";
    $content .= '      <file>./tests/TestSuites/FunctionalJavascriptTestSuite.php</file>' . "\n";
    $content .= '    </testsuite>' . "\n";
    $content .= '    <testsuite name="build">' . "\n";
    $content .= '      <file>./tests/TestSuites/BuildTestSuite.php</file>' . "\n";
    $content .= '    </testsuite>' . "\n";
    $content .= '  </testsuites>' . "\n";
    $content .= '  <listeners>' . "\n";
    $content .= '    <listener class="\Drupal\Tests\Listeners\DrupalListener">' . "\n";
    $content .= '    </listener>' . "\n";
    $content .= '  </listeners>' . "\n";
    $content .= '  <!-- Settings for coverage reports. -->' . "\n";
    $content .= '  <coverage>' . "\n";
    $content .= '    <include>' . "\n";
    $content .= '      <directory>./includes</directory>' . "\n";
    $content .= '      <directory>./lib</directory>' . "\n";
    $content .= '      <directory>./modules</directory>' . "\n";
    $content .= '      <directory>../modules</directory>' . "\n";
    $content .= '      <directory>../sites</directory>' . "\n";
    $content .= '    </include>' . "\n";
    $content .= '    <exclude>' . "\n";
    $content .= '      <directory>./modules/*/src/Tests</directory>' . "\n";
    $content .= '      <directory>./modules/*/tests</directory>' . "\n";
    $content .= '      <directory>../modules/*/src/Tests</directory>' . "\n";
    $content .= '      <directory>../modules/*/tests</directory>' . "\n";
    $content .= '      <directory>../modules/*/*/src/Tests</directory>' . "\n";
    $content .= '      <directory>../modules/*/*/tests</directory>' . "\n";
    $content .= '      <directory suffix=".api.php">./lib/**</directory>' . "\n";
    $content .= '      <directory suffix=".api.php">./modules/**</directory>' . "\n";
    $content .= '      <directory suffix=".api.php">../modules/**</directory>' . "\n";
    $content .= '    </exclude>' . "\n";
    $content .= '  </coverage>' . "\n";
    $content .= '</phpunit>' . "\n";

    try {
      $fp = fopen($this->phpunitXmlPath, "wb");
      if (!$fp) {
        throw new \Exception('Failed to open uploaded file. Possible permission on your file system need to be adjusted.');
      }
      fwrite($fp, $content);
      fclose($fp);
      $this->messenger->addStatus('The phpunit.xml was updated.');
    }
    catch (\Exception $e) {
      $this->messenger->addStatus('An error has occured updating phpunit.xml file. Its possible the permission on your file system need to be adjusted. Please check the logs for more information.');
    }
  }

}
