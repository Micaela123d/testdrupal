<?php

namespace Drupal\user_geo_address\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provide a 'Google Address' Block.
 *
 * @Block(
 *   id = "user_geo_address_block",
 *   admin_label = @Translation("User Geo Address ")
 * )
 */
class UserGeoAddress extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {

    return [
      '#theme' => 'user_geo_address',
      '#attached' => [
        'library' => [
          'user_geo_address/address-details',
        ],
      ],
    ];
  }

}
