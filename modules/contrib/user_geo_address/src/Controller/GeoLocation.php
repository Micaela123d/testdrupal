<?php

namespace Drupal\user_geo_address\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Class GeoLocation .
 *
 * To get address using Google Maps API
 * from latitude and longitude.
 */
class GeoLocation extends ControllerBase {

  /**
   * The location retrieval service.
   *
   * @var \Drupal\user_geo_address\Services\UserGeoClient
   */

  protected $locationService;

  /**
   * Class constructor.
   */
  public function __construct($locationService) {
    $this->locationService = $locationService;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
          $container->get('user_geo_client.getAddress')
      );
  }

  /**
   * Process the lat and long.
   *
   * @return string
   *   User address from request.
   */
  public function getUserLocation($latitude, $longitude) {
    if ($latitude && $latitude) {
      $getAddress = $this->locationService->userAddress($latitude, $longitude);
      $userlocation = new JsonResponse($getAddress->results[0]->formatted_address);
      return $userlocation;
    }
    else {
      return new JsonResponse([t("Location coordinates not found.")]);
    }
  }

}
