<?php

namespace Drupal\phpstan_tests;

use Drupal\Component\Serialization\Json;
use Drupal\Core\File\FileSystemInterface;
use Drupal\testsuite\BaseFileService;
use Drupal\testsuite\BaseTrait;

/**
 * Phpstan Tests Services.
 */
class PhpstanTestsResourceService extends BaseFileService {
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
   * PhpstanTestsResourceService constructor.
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
   * Debug function.
   *
   * @param mixed $content
   *   Data to be write to file.
   */
  public function createFileDebug($content) {
    $myfile = fopen("debugfile.txt", "a") or die("Unable to open file!");
    fwrite($myfile, $content);
    fclose($myfile);
  }

  /**
   * Returns the phpstan report array.
   *
   * @return array
   *   The phpstan report array.
   */
  public function getPhpStanReport() {
    $statement = 'php vendor' . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'phpstan.phar analyze -c ' . DRUPAL_ROOT;
    $statement .= DIRECTORY_SEPARATOR . 'core' . DIRECTORY_SEPARATOR . 'phpstan.neon';

    $reportResults = [];
    $results = $this->getData($statement);
    $result = $this->json->decode($results);

    if ($result['errors'] > 0 && empty($result['files'])) {
      foreach ($result['errors'] as $error) {
        $reportResults[] = [
          'file' => "Error",
          'line' => 1,
          'ignore' => 0,
          'error' => $error,
        ];
      }
    }
    else {
      foreach ($result['files'] as $file => $values) {
        if ($values["errors"] > 0) {
          foreach ($values["messages"] as $item) {
            $ignorable = $item["ignorable"] == TRUE ? "Yes" : "No";
            $message = "Message: " . $item["message"] . "\n";
            $message .= "Line: " . $item["line"] . "\n";
            $message .= "Ignorable: " . $ignorable . "\n";
            if (isset($item["tip"])) {
              $message .= "Tip: " . $item["tip"];
            }
            $reportResults[] = [
              'file' => $file,
              'line' => $item["line"],
              'ignore' => $item["ignorable"] == TRUE ? 1 : 0,
              'error' => $message,
            ];
          }
        }
      }
    }

    return $reportResults;
  }

  /**
   * Gets the array of files to create classes with.
   *
   * @param string $type
   *   The type. Module or theme.
   * @param string $area
   *   The area the module is located. Core, contrib or custom.
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
