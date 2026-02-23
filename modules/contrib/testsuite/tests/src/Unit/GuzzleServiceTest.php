<?php

namespace Drupal\Tests\testsuite\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\testsuite\GuzzleService;

/**
 * Guzzle Service Test.
 */
class GuzzleServiceTest extends UnitTestCase {

  /**
   * Guzzle Service.
   *
   * @var \Drupal\testsuite\GuzzleService
   */
  protected $guzzleService;

  /**
   * The mocked wrapped kernel.
   *
   * @var \GuzzleHttp\ClientInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $httpClient;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->httpClient = $this->createMock('GuzzleHttp\ClientInterface');
    $this->guzzleService = new GuzzleService($this->httpClient);
  }

  /**
   * Tests that the Test Suite GuzzleService works.
   */
  public function testService() {
    $this->assertContainsOnlyInstancesOf(GuzzleService::class, [$this->guzzleService]);
  }

}
