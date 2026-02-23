<?php

namespace Drupal\testsuite;

use Drupal\Core\File\FileSystemInterface;

/**
 * Test Suite Base Service.
 */
class BaseFileService {

  /**
   * The file system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * Constructs a new BaseFileService.
   *
   * @param \Drupal\Core\File\FileSystemInterface $fileSystem
   *   The file system interface.
   */
  public function __construct(FileSystemInterface $fileSystem) {
    $this->fileSystem = $fileSystem;
  }

  /**
   * Gets the path to root.
   *
   * @return string
   *   The path to root.
   */
  protected function getRoot() {
    if (preg_match('/\bweb\b/', $this->fileSystem->realpath('core'))) {
      return dirname(dirname($this->fileSystem->realpath('core')));
    }
    else {
      return dirname($this->fileSystem->realpath('core'));
    }
  }

  /**
   * Gets the initial data from a phpunit statement.
   *
   * @param string $statement
   *   The statement used in Phpunit to call tests from the CMD.
   *
   * @return string
   *   The data pulled with shell_exec.
   */
  protected function getData($statement) {
    $old_path = getcwd();

    $root = $this->getRoot();

    chdir($root);
    $escaped_command = escapeshellcmd($statement);
    $results = shell_exec($escaped_command);
    chdir($old_path);
    return $results;
  }

  /**
   * Checks to see if the root folder is writable.
   */
  public function ifRootIsWritable() {
    if (is_writable(getcwd())) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Checks to see if the core folder is writable.
   */
  public function ifCoreIsWritable() {
    if (is_writable($this->fileSystem->realpath('core'))) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Checks to see if the path is windows.
   *
   * @return bool
   *   Returns true or false
   */
  public function isWindows() {
    return strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
  }

  /**
   * Builds a list of modules in an area.
   *
   * @param string $path
   *   The absolute path to where the modules are.
   *
   * @return array
   *   The array of modules.
   */
  public function getModulesByArea($path) {
    $modulesArray = [];
    if (is_dir($path)) {
      $scan = scandir($path);
      unset($scan[array_search('.', $scan, TRUE)]);
      unset($scan[array_search('..', $scan, TRUE)]);
      foreach ($scan as $module) {
        if (is_dir("$path/$module")) {
          $modulesArray[$module] = ucfirst(implode(" ", explode("_", $module)));
        }
      }
    }
    return $modulesArray;
  }

  /**
   * Builds a list of themes in an area.
   *
   * @param string $path
   *   The absolute path to where the themes are.
   *
   * @return array
   *   The array of themes.
   */
  public function getThemesByArea($path) {
    $themesArray = [];
    if (is_dir($path)) {
      $scan = scandir($path);
      unset($scan[array_search('.', $scan, TRUE)]);
      unset($scan[array_search('..', $scan, TRUE)]);
      foreach ($scan as $theme) {
        if (is_dir("$path/$theme")) {
          $themesArray[$theme] = ucfirst(implode(" ", explode("_", $theme)));
        }
      }
    }
    return $themesArray;
  }

}
