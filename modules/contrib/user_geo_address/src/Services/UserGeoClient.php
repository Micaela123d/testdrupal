<?php

namespace Drupal\user_geo_address\Services;

use Drupal\Core\Config\ConfigFactoryInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

/**
 * Class UserGeoClient for getting geolocation.
 */
class UserGeoClient {

  /**
   * The Gmap ApiKey.
   *
   * @var string
   */
  protected $gmapApiKey;

  /**
   * Drupal\Core\Config\ConfigFactory definition.
   *
   * @var Drupal\Core\Config\ConfigFactory
   */
  protected $config;


  /**
   * Guzzle Http Client.
   *
   * @var GuzzleHttp\Client
   */
  protected $httpClient;

  /**
   * The Constant gmapApiUrl.
   *
   * @var string
   */
  protected $gmapApiUrl = 'maps.googleapis.com/maps/api/geocode/json';

  /**
   * Constructs a new GoogleMapsService object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config
   *   A config factory for retrieving required config objects.
   * @param GuzzleHttp\Client $httpClient
   *   The http client object.
   */
  public function __construct(ConfigFactoryInterface $config, Client $httpClient) {
    $this->config = $config;
    $this->httpClient = $httpClient;
    $this->gmapApiKey = $this->setGmapApiKey();
  }

  /**
   * Set the module related Gmap API Key.
   *
   * @return string
   *   The ApiKey
   */
  protected function setGmapApiKey() {
    return $this->config->get('user_geo_address.apiconfiguration')->get('google_api_key');
  }

  /**
   * Get the localized Gmap API Library.
   *
   * @param float $lat
   *   The latitute value.
   * @param float $long
   *   The long value.
   *
   * @return string
   *   The current address of user.
   */
  public function userAddress($lat, $long) {
    try {
      $web_protocol = 'https://';
      $url = $web_protocol . $this->gmapApiUrl . '?latlng=' . trim($lat) . ',' . trim($long) . '&key=' . $this->gmapApiKey;
      $response = $this->httpClient->post(
            $url, [
              'verify' => TRUE,
              'headers' => [
                'Content-type' => 'application/x-www-form-urlencoded',
              ],
            ]
        )->getBody()->getContents();
    }
    catch (GuzzleException $exception) {
      $logger = \Drupal::logger('HTTP Client error');
      $logger->error($exception->getMessage());
      return FALSE;
    }
    return (json_decode($response));
  }

}
