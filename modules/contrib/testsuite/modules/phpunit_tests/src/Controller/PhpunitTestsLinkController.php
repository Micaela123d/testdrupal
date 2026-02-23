<?php

namespace Drupal\phpunit_tests\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\phpunit_tests\PhpunitTestsRepositoryService;
use Drupal\phpunit_tests\PhpunitTestsResourceService;
use Drupal\testsuite\BaseTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Returns responses for test reports routes.
 */
class PhpunitTestsLinkController extends ControllerBase {
  use BaseTrait;

  /**
   * The resourse service.
   *
   * @var \Drupal\phpunit_tests\PhpunitTestsResourceService
   */
  protected $phpunitTestsResourceService;

  /**
   * The resourse service.
   *
   * @var \Drupal\phpunit_tests\PhpunitTestsRepositoryService
   */
  protected $phpunitTestsRepositoryService;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
          $container->get('phpunit_tests.load_resource.service'),
          $container->get('phpunit_tests.repository.service')
      );
  }

  /**
   * Constructs a PhpunitTestsController object.
   *
   * @param \Drupal\phpunit_tests\PhpunitTestsResourceService $phpunitTestsResourceService
   *   A database connection.
   * @param \Drupal\phpunit_tests\PhpunitTestsRepositoryService $phpunitTestsRepositoryService
   *   A database connection.
   */
  public function __construct(
    PhpunitTestsResourceService $phpunitTestsResourceService,
    PhpunitTestsRepositoryService $phpunitTestsRepositoryService,
  ) {
    $this->phpunitTestsResourceService = $phpunitTestsResourceService;
    $this->phpunitTestsRepositoryService = $phpunitTestsRepositoryService;
  }

  /**
   * Landing page for custom tests.
   *
   * @return string
   *   A json string.
   */
  public function runPhpunitTest($area, $module, $directory, $test = "") {
    if (in_array($area, $this->areas) && preg_match('/^[a-z_]+$/', $module) && in_array($directory, $this->directories) && ($test == NULL || preg_match('/^[A-Za-z]+$/', $test))) {
      $results = $this->phpunitTestsResourceService->getPhpunitStatement($area, $module, $directory, $test);
      if ($results != NULL) {
        if ($this->phpunitTestsRepositoryService->createLog($area, $module, $directory, $test, $results)) {
          return new JsonResponse([
            'success' => TRUE,
            'results' => $results,
          ]);
        }
        else {
          return new JsonResponse([
            'success' => FALSE,
            'results' => 'Failed to save to database.',
          ]);
        }
      }
      else {
        return new JsonResponse([
          'success' => FALSE,
          'results' => 'No results.',
        ]);
      }
    }
    else {
      return new JsonResponse([
        'success' => FALSE,
        'results' => 'Invalid parameters.',
      ]);
    }
  }

}
