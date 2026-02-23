<?php

namespace Drupal\phpunit_tests;

use Drupal\Core\File\FileSystemInterface;
use Drupal\testsuite\BaseFileService;
use Drupal\testsuite\BaseTrait;

/**
 * Phpunit Tests Services.
 */
class PhpunitTestsResourceService extends BaseFileService {
  use BaseTrait;

  /**
   * The file system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * PhpunitTestsResourceService constructor.
   *
   * @param \Drupal\Core\File\FileSystemInterface $fileSystem
   *   The file system interface.
   */
  public function __construct(FileSystemInterface $fileSystem) {
    $this->fileSystem = $fileSystem;
  }

  /**
   * Returns the phpunit tests report results.
   *
   * @param string $area
   *   The area the module is located. Core, contrib or custom.
   * @param string $module
   *   The module name.
   * @param string $directory
   *   The test directory. Unit, Functional etc.
   * @param string $test
   *   The absolute path the tests.
   *
   * @return array
   *   The phpunit tests report results.
   */
  public function getPhpunitStatement($area, $module, $directory, $test = NULL) {
    if (in_array($area, $this->areas) && preg_match('/^[a-z_]+$/', $module) && in_array($directory, $this->directories) && ($test == NULL || preg_match('/^[A-Za-z]+$/', $test))) {
      $statement = 'vendor' . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'phpunit --debug -c ';
      if (preg_match('/\bweb\b/', $this->fileSystem->realpath('core'))) {
        $statement .= 'web' . DIRECTORY_SEPARATOR . 'core web' . DIRECTORY_SEPARATOR;
      }
      else {
        $statement .= 'core ';
      }
      switch ($area) {
        case 'core':
          $statement .= 'core' . DIRECTORY_SEPARATOR . 'modules' . DIRECTORY_SEPARATOR . $module . DIRECTORY_SEPARATOR;
          break;

        default:
          $statement .= 'modules' . DIRECTORY_SEPARATOR . $area . DIRECTORY_SEPARATOR . $module . DIRECTORY_SEPARATOR;
      }
      $statement .= 'tests' . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . $directory;
      if ($test != NULL) {
        $statement .= DIRECTORY_SEPARATOR . $test . '.php';
      }
      return $this->getData($statement);
    }
    else {
      return [];
    }

  }

  /**
   * Gets the array of files to create classes with.
   *
   * @param string $caller
   *   The page type. Menu, form, report etc.
   * @param string $area
   *   The area the module is located. Core, contrib or custom.
   * @param string $module
   *   The module name.
   * @param string $directory
   *   The test directory. Unit, Functional etc.
   *
   * @return array
   *   The array of files to create classes with.
   */
  public function getResource($caller, $area, $module, $directory) {
    $resource = [];
    if ($caller == 'module') {
      switch ($area) {
        case 'core';
          $resource = $this->getModulesByTestDirectory($this->fileSystem->realpath('core') . DIRECTORY_SEPARATOR . 'modules', $directory);
          break;

        case 'contrib';
          $resource = $this->getModulesByTestDirectory($this->fileSystem->realpath('modules') . DIRECTORY_SEPARATOR . 'contrib', $directory);
          break;

        case 'custom';
          $resource = $this->getModulesByTestDirectory($this->fileSystem->realpath('modules') . DIRECTORY_SEPARATOR . 'custom', $directory);
          break;
      }
    }
    elseif ($caller == 'test') {
      $testpath = DIRECTORY_SEPARATOR . $module . DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . $directory;
      switch ($area) {
        case 'core';
          $resource = $this->getTests($this->fileSystem->realpath('core') . DIRECTORY_SEPARATOR . 'modules' . $testpath);
          break;

        case 'contrib';
          $resource = $this->getTests($this->fileSystem->realpath('modules') . DIRECTORY_SEPARATOR . 'contrib' . $testpath);
          break;

        case 'custom';
          $resource = $this->getTests($this->fileSystem->realpath('modules') . DIRECTORY_SEPARATOR . 'custom' . $testpath);
          break;
      }
    }

    return $resource;
  }

  /**
   * Builds a list of modules that have the directory in the tests section.
   *
   * @param string $path
   *   The absolute path to where the modules are.
   * @param string $testDirectory
   *   The test directory.
   *
   * @return array
   *   The array of modules.
   */
  private function getModulesByTestDirectory($path, $testDirectory) {
    $modulesArray = [];
    if (is_dir($path)) {
      $scan = scandir($path);
      unset($scan[array_search('.', $scan, TRUE)]);
      unset($scan[array_search('..', $scan, TRUE)]);
      foreach ($scan as $module) {
        if (is_dir("$path/$module")) {
          $testsPath = "$path/$module/tests/src/";
          if (is_dir($testsPath)) {
            $testsScan = scandir($testsPath);
            unset($testsScan[array_search('.', $testsScan, TRUE)]);
            unset($testsScan[array_search('..', $testsScan, TRUE)]);
            foreach ($testsScan as $directory) {
              if ($directory == $testDirectory && is_dir("$testsPath/$testDirectory") && count(glob("$testsPath/$testDirectory" . DIRECTORY_SEPARATOR . '*.php')) > 0) {
                $modulesArray[$module] = ucfirst(implode(" ", explode("_", $module)));
              }
            }
          }
        }
      }
    }
    return $modulesArray;
  }

  /**
   * Builds the test list.
   *
   * @param string $path
   *   The absolute path to where the tests are.
   *
   * @return array
   *   The array of tests.
   */
  private function getTests($path) {
    $testsArray = [];
    if (is_dir($path)) {
      $globedFiles = glob($path . DIRECTORY_SEPARATOR . '*.php');
      foreach ($globedFiles as $file) {
        $pathParts = explode(DIRECTORY_SEPARATOR, $file);
        $endPath = end($pathParts);
        $test = explode(".", $endPath);
        $testsArray[$test[0]] = $test[0];
      }
    }
    return $testsArray;
  }

}
