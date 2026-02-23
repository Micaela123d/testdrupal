<?php

namespace Drupal\phpstan_tests;

use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\testsuite\BaseFileService;

/**
 * Test Suite Services.
 */
class PhpstanTestsFileResourceService extends BaseFileService {

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
   * The path to the phpstan.neon.dist file.
   *
   * @var string
   */
  protected $phpstanNeonDistPath;

  /**
   * The path to the phpstan.neon file.
   *
   * @var string
   */
  protected $phpstanNeonPath;

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
    $this->phpstanNeonDistPath = $fileSystem->realpath('core') . DIRECTORY_SEPARATOR . 'phpstan.neon.dist';
    $this->phpstanNeonPath = $fileSystem->realpath('core') . DIRECTORY_SEPARATOR . 'phpstan.neon';
  }

  /**
   * Rebuilds the phpstan.neon with config variables.
   *
   * @param array $data
   *   The data to populate rewrite parameters.
   */
  public function buildPhpStanNeon($data) {
    $content = '# Configuration file for PHPStan static code checking, see https://phpstan.org .' . "\n";
    $content .= '# PHPStan is triggered on Drupal CI in commit-code-check.sh.' . "\n";
    $content .= 'includes:' . "\n";
    $content .= '  - .phpstan-baseline.php' . "\n";
    $content .= '  - phar://phpstan.phar/conf/bleedingEdge.neon' . "\n";
    $content .= "\n";
    $content .= 'parameters:' . "\n";
    $content .= "\n";
    $content .= '  level: ' . $data['phpstan_level'] . "\n";
    $content .= '  errorFormat: json' . "\n";
    $content .= "\n";
    $content .= '  fileExtensions:' . "\n";
    $content .= '    - sh' . "\n";
    $content .= "\n";
    $content .= '  paths:' . "\n";
    $content .= '    - ' . $data['phpstan_path'] . "\n";
    $content .= "\n";
    $content .= '  excludePaths:' . "\n";
    $content .= '    # Skip sites directory.' . "\n";
    $content .= '    - ../sites' . "\n";
    $content .= '    # Skip test fixtures.' . "\n";
    $content .= '    - ../*/node_modules/*' . "\n";
    $content .= '    - */tests/fixtures/*.php' . "\n";
    $content .= '    - */tests/fixtures/*.php.gz' . "\n";
    $content .= '    # Skip Drupal 6 & 7 code.' . "\n";
    $content .= '    - scripts/dump-database-d?.sh' . "\n";
    $content .= '    - scripts/generate-d?-content.sh' . "\n";
    $content .= '    # Skip data files.' . "\n";
    $content .= '    - lib/Drupal/Component/Transliteration/data/*.php' . "\n";
    $content .= '    # Below extends on purpose a non existing class for testing.' . "\n";
    $content .= '    - modules/system/tests/modules/plugin_test/src/Plugin/plugin_test/fruit/ExtendingNonInstalledClass.php' . "\n";
    $content .= '    - modules/system/tests/modules/plugin_test/src/Plugin/plugin_test/custom_annotation/UsingNonInstalledTraitClass.php' . "\n";
    $content .= '    - modules/system/tests/modules/plugin_test/src/Plugin/plugin_test/custom_annotation/ExtendingNonInstalledClass.php' . "\n";
    $content .= "\n";
    $content .= '  reportUnmatchedIgnoredErrors: false' . "\n";
    $content .= '  ignoreErrors:' . "\n";
    $content .= '    # new static() is a best practice in Drupal, so we cannot fix that.' . "\n";
    $content .= '    - "#^Unsafe usage of new static#"' . "\n";
    $content .= "\n";
    $content .= '    # Ignore common errors for now.' . "\n";
    $content .= '    - "#Drupal calls should be avoided in classes, use dependency injection instead#"' . "\n";
    $content .= '    - "#^Plugin definitions cannot be altered.#"' . "\n";
    $content .= '    - "#^Class .* extends @internal class#"' . "\n";

    try {
      $fp = fopen($this->phpstanNeonPath, "wb");
      if (!$fp) {
        throw new \Exception('Failed to open uploaded file. Possible permission on your file system need to be adjusted.');
      }
      fwrite($fp, $content);
      fclose($fp);
      $this->messenger->addStatus('The phpstan.neon was updated.');
    }
    catch (\Exception $e) {
      $this->messenger->addStatus('An error has occured updating phpstan.neon file. Its possible the permission on your file system need to be adjusted. Please check the logs for more information.');
    }
  }

}
