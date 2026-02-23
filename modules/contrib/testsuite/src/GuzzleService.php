<?php

namespace Drupal\testsuite;

use GuzzleHttp\ClientInterface;

/**
 * Test Suite Services.
 */
class GuzzleService {

  /**
   * Client Interface.
   *
   * @var \Guzzle\ClientInterface
   */
  protected $httpClient;

  /**
   * GuzzleService constructor.
   *
   * @param \GuzzleHttp\ClientInterface $httpClient
   *   The guzzle client.
   */
  public function __construct(ClientInterface $httpClient) {
    $this->httpClient = $httpClient;
  }

  /**
   * Gets the guzzle service.
   *
   * @return array
   *   The guzzle service.
   */
  public function getService() {
    return $this->httpClient;
  }

}
