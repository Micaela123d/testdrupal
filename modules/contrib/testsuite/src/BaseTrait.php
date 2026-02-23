<?php

namespace Drupal\testsuite;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Render\Markup;

/**
 * Test Suite Base Trait.
 */
trait BaseTrait {

  /**
   * The list of regex expressions.
   *
   * Semver_version
   * https://semver.org/#is-there-a-suggested-regular-expression-regex-to-check-a-semver-string.
   *
   * @var array
   */
  protected $regex = [
    'string' => '/^[a-zA-Z0-9_\-]+$/',
    'string_space' => '/^[a-zA-Z0-9_\-\s]+$/',
    'name_dot_name' => '/^[a-zA-Z0-9_\-\.]+(\.[a-zA-Z0-9_\-\.]+)?$/',
    'name_forward_slash_name' => '/^[a-zA-Z0-9_\-]+\/[a-zA-Z0-9_\-]+$/',
    'css_file_name' => '/^[a-zA-Z0-9_\-\.]+(\.css)?$/',
    'js_file_name' => '/^[a-zA-Z0-9_\-\.]+(\.js)?$/',
    'number_single' => '/^[0-9]{1}$/',
    'numbers' => '/^[0-9]+$/',
    'http_check' => '/^https?/',
    'php_file_path' => '/^[^\.]+\.php$/',
    'semver_version' => '/^(0|[1-9]\d*)\.(0|[1-9]\d*)\.(0|[1-9]\d*)(?:-((?:0|[1-9]\d*|\d*[a-zA-Z-][0-9a-zA-Z-]*)(?:\.(?:0|[1-9]\d*|\d*[a-zA-Z-][0-9a-zA-Z-]*))*))?(?:\+([0-9a-zA-Z-]+(?:\.[0-9a-zA-Z-]+)*))?$/',
  ];

  /**
   * The list of types.
   *
   * @var array
   */
  protected $types = [
    'module',
    'theme',
  ];

  /**
   * The list of package types.
   *
   * @var array
   */
  protected $packageTypes = [
    'composer',
    'package',
    'other',
  ];

  /**
   * The list of areas represent the areas where modules are.
   *
   * @var array
   */
  protected $areas = [
    'core',
    'contrib',
    'custom',
  ];

  /**
   * The list of directories represent the directories where phpunit tests are.
   *
   * @var array
   */
  protected $directories = [
    'Unit',
    'Kernel',
    'Functional',
    'FunctionalJavascript',
  ];

  /**
   * The array of databases.
   *
   * @var array
   */
  protected $databases = [
    'lp_core_library' => 0,
    'lp_installed_package' => 1,
    'lp_installed_library' => 2,
    'lp_installed_module' => 3,
    'lp_module_library' => 4,
  ];

  /**
   * Formats the message.
   *
   * @param string $message
   *   The error report message.
   *
   * @return string|bool
   *   The message formated or FALSE.
   */
  protected function formatMessage($message) {
    if ($message != NULL) {
      $newMessage = str_replace("\n\n\n", "<br />", $message);
      $newMessage = str_replace("\n\n", "<br />", $newMessage);
      $newMessage = str_replace("\n", "<br />", $newMessage);
      return Xss::filterAdmin($newMessage);
    }
    else {
      return FALSE;
    }
  }

  /**
   * Takes the full file path and returns the file name.
   *
   * @param string $filePath
   *   The file we are reporting on.
   *
   * @return string
   *   The filename.
   */
  protected function getFileName($filePath) {
    if (preg_match('/\([^)]*\)/', $filePath)) {
      $fileParts = preg_split('/\s\(/', $filePath);
      $filePath = $fileParts[0];
    }
    $filePart = explode(DIRECTORY_SEPARATOR, $filePath);
    if (isset($fileParts[1])) {
      return end($filePart) . " (" . $fileParts[1];
    }
    else {
      return end($filePart);
    }

  }

  /**
   * Get the default admin theme.
   *
   * @return string
   *   The theme name.
   */
  protected function getTheme() {
    $admin_theme = \Drupal::config('system.theme')->get('admin');
    return \Drupal::service('theme_handler')->getName($admin_theme);
  }

  /**
   * Displays an Emoji representing results of a vulnerability check.
   *
   * @param int $ifVulnerability
   *   The type of emogi to display.
   *
   * @return markup
   *   Markup to be rendered.
   */
  protected function getEmoji($ifVulnerability) {
    switch ($ifVulnerability) {
      case 0:
        return '<span class="testsuite-vulnerability-emoji" title="Vulnerabilities detected. Please go to the details page to view a vulnerability report.">❗</span>';

      case 1:
        return '<span class="testsuite-vulnerability-emoji" title="The version could not be identified through composer.json or package.json. There are vulnerabilities with a version or versions of this package. Please double check the version in the top of the css or js files and compare with the version specified in the vulnerability report. Without a specifing the version in the json file I cannot perfectly realize the version with this software.">❓</span>';

      case 2:
        return '<span class="testsuite-vulnerability-emoji" title="The checker relies on either a composer.json or package.json file for information on a package. A reliable vulnerability check could not be made. If this is a css or js file internal files are files that do not get code from an exterior source like a CDN. They are usually safe unless there is a vunerability report on the module itself. Exteral files will matter more and I will try to check them for vulnerabilities.">❔</span>';

      case 3:
        return '<span class="testsuite-vulnerability-emoji" title="No vulnerabilities">✔️</span>';

      case 4:
        return '<span class="testsuite-vulnerability-emoji" title="Internal scripts are not tested. They are a component of either Drupal, a module or a theme.">❎</span>';

      default:
        return '<span class="testsuite-vulnerability-emoji" title="The version could not be identified through composer.json or package.json. There are vulnerabilities with a version or versions of this package. Please double check the version in the top of the css or js files and compare with the version specified in the vulnerability report. Without a specifing the version in the json file I cannot perfectly realize the version with this software.">❓</span>';
    }
  }

  /**
   * Displays the legend at the top of the overview page.
   *
   * @param int $description
   *   The description of the overview page.
   *
   * @return markup
   *   Markup to be rendered.
   */
  protected function createLegend($description = "") {
    $legend = "";
    $title = "";
    for ($i = 0; $i <= 4; $i++) {
      switch ($i) {
        case 0:
          $title = "The API succeded and a vulnerability was found matching the package version.";
          break;

        case 1:
          $title = "The API succeded, there are vulnerabilities on the package but the installed package does not have a version to check against.";
          break;

        case 2:
          $title = "The API call failed and I could not check the package at all.";
          break;

        case 3:
          $title = "The API succeeded and no vulnerabilities were found.";
          break;

        case 4:
          $title = "Internal script. Since the script is internal the parent package will be ran instead.";
          break;
      }
      $legend .= Markup::create($this->getEmoji($i)) . " " . $title . "<br />";
    }

    $build['inner_dashboard'] = [
      '#type' => 'container',
    ];

    $build['inner_dashboard']['legend_dashboard'] = [
      '#type' => 'container',
      '#children' => [
        [
          '#type' => 'markup',
          '#markup' => '<p>' . $legend . '<br />' . $description . '</p>',
        ],
      ],
    ];

    return $build;
  }

}
