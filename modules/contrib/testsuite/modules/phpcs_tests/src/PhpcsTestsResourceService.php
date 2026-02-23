<?php

namespace Drupal\phpcs_tests;

use Drupal\Component\Serialization\Json;
use Drupal\Core\File\FileSystemInterface;
use Drupal\testsuite\BaseFileService;
use Drupal\testsuite\BaseTrait;

/**
 * Test Suite Services.
 */
class PhpcsTestsResourceService extends BaseFileService {
  use BaseTrait;

  /**
   * The file system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * Json Serialization.
   *
   * @var \Drupal\Component\Serialization\Json
   */
  protected $json;

  /**
   * PhpcsTestsResourceService constructor.
   *
   * @param \Drupal\Core\File\FileSystemInterface $fileSystem
   *   The file system interface.
   * @param \Drupal\Component\Serialization\Json $json
   *   The file system interface.
   */
  public function __construct(
    FileSystemInterface $fileSystem,
    Json $json,
  ) {
    $this->fileSystem = $fileSystem;
    $this->json = $json;
  }

  /**
   * Returns the phpcs report array.
   *
   * @param string $type
   *   The type. Module or theme.
   * @param string $area
   *   The area the module or theme is located. Core, contrib or custom.
   * @param string $name
   *   The module or theme name.
   *
   * @return array
   *   The phpcs report array.
   */
  public function getPhpcsReport($type, $area, $name) {
    $reportResults = [];
    if (in_array($type, $this->types) && in_array($area, $this->areas) && preg_match('/^[a-z_]+$/', $name)) {
      $statement = 'vendor' . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'phpcs --standard=Drupal,DrupalPractice --ignore=vendor  ';
      $statement .= ' --extensions=php,inc,module,install,info,test,profile,theme --parallel=2 --report=json ';
      switch ($area) {
        case 'core':
          $statement .= $this->fileSystem->realpath('core') . DIRECTORY_SEPARATOR . $type . 's' . DIRECTORY_SEPARATOR . $name;
          break;

        default:
          $statement .= $this->fileSystem->realpath($type . 's') . DIRECTORY_SEPARATOR . $area . DIRECTORY_SEPARATOR . $name;
      }

      $results = $this->getData($statement);
      $result = $this->json->decode($results);
      foreach ($result['files'] as $file => $values) {
        if ($values["errors"] > 0 || $values["warnings"] > 0) {
          foreach ($values["messages"] as $item) {
            $fixable = $item["fixable"] == TRUE ? "Yes" : "No";
            $message = "Message: " . $item["message"] . "\n";
            $message .= "Source: " . $item["source"] . "\n";
            $message .= "Severity: " . $item["severity"] . "\n";
            $message .= "Fixable: " . $fixable . "\n";
            $message .= "Type: " . $item["type"] . "\n";
            $message .= "Line: " . $item["line"] . "\n";
            $message .= "Column: " . $item["column"];
            $reportResults[] = [
              'file' => $file,
              'line' => $item["line"],
              'type' => $item["type"],
              'severity' => $item["severity"],
              'error' => $message,
            ];
          }
        }
      }
    }
    else {
      $reportResults[] = [
        'file' => "Invalid Parameter",
        'line' => 1,
        'type' => "ERROR",
        'severity' => 5,
        'error' => "Invalid Parameter: Module name must contain only leters and underscores. No numbers. No spaces.",
      ];
    }

    return $reportResults;
  }

  /**
   * Gets the array of files to create classes with.
   *
   * @param string $type
   *   The type. Module or theme.
   * @param string $area
   *   The area the module or theme is located. Core, contrib or custom.
   *
   * @return array
   *   The array of modules.
   */
  public function getResource($type, $area) {
    $resource = [];
    switch ($area) {
      case 'core';
        if ($type == 'module') {
          $resource = $this->getModulesByArea($this->fileSystem->realpath('core') . DIRECTORY_SEPARATOR . 'modules');
        }
        else {
          $resource = $this->getThemesByArea($this->fileSystem->realpath('core') . DIRECTORY_SEPARATOR . 'themes');
        }
        break;

      case 'contrib';
        if ($type == 'module') {
          $resource = $this->getModulesByArea($this->fileSystem->realpath('modules') . DIRECTORY_SEPARATOR . 'contrib');
        }
        else {
          $resource = $this->getThemesByArea($this->fileSystem->realpath('themes') . DIRECTORY_SEPARATOR . 'contrib');
        }
        break;

      case 'custom';
        if ($type == 'module') {
          $resource = $this->getModulesByArea($this->fileSystem->realpath('modules') . DIRECTORY_SEPARATOR . 'custom');
        }
        else {
          $resource = $this->getThemesByArea($this->fileSystem->realpath('themes') . DIRECTORY_SEPARATOR . 'custom');
        }
        break;
    }

    return $resource;
  }

}
