<?php

namespace Drupal\testsuite;

use Drupal\Core\File\FileSystemInterface;

/**
 * Test Suite Services.
 */
class CustomTestsResourceService {

  /**
   * The file system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * CustomTestsResourceService constructor.
   *
   * @param \Drupal\Core\File\FileSystemInterface $fileSystem
   *   The file system interface.
   */
  public function __construct(
    FileSystemInterface $fileSystem,
  ) {
    $this->fileSystem = $fileSystem;
  }

  /**
   * Gets the array of files to create classes with.
   *
   * @param string $type
   *   Modules, tests or both.
   * @param string $testModule
   *   The module to get tests in.
   *
   * @return array
   *   The array of files to create classes with.
   */
  public function getResource($type, $testModule = NULL) {
    $resource = [];

    $path = $this->fileSystem->realpath('modules') . DIRECTORY_SEPARATOR . 'custom';
    if (is_dir($path)) {
      $scan = scandir($path);
      unset($scan[array_search('.', $scan, TRUE)]);
      unset($scan[array_search('..', $scan, TRUE)]);
      foreach ($scan as $module) {
        if (is_dir($path . DIRECTORY_SEPARATOR . $module)) {
          $testsPath = $path . DIRECTORY_SEPARATOR . $module . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'CustomTSTests' . DIRECTORY_SEPARATOR;
          if (is_dir($testsPath)) {
            $testScan = scandir($testsPath);
            switch ($type) {
              case 'modules':
                if (count($testScan) > 0) {
                  $name = explode("_", $module);
                  $resource[$module] = ucwords(implode(" ", $name));
                }
                break;

              case 'tests':
                if ($module == $testModule) {
                  // $resource = glob($testsPath . '*.php');
                  foreach (glob($testsPath . '*.php') as $file) {
                    $parts = explode(DIRECTORY_SEPARATOR, $file);
                    $name = explode(".", end($parts))[0];
                    $resource[$file] = $name;
                  }
                }
                break;

              case 'both':
                $resource[$module] = glob($testsPath . '*.php');
                break;
            }
          }
        }
      }
    }

    return $resource;
  }

}
