<?php

/**
 * @file
 * Post update functions for Simple Gmap module.
 */

use Drupal\Core\Config\Entity\ConfigEntityUpdater;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\simple_gmap\SimpleGmapUpdater;

/**
 * Add the alt_text key to simple_gmap setting to field formatter instances.
 */
function simple_gmap_post_update_add_alt_text(?array &$sandbox = NULL): void {
  $config_updater = \Drupal::classResolver(SimpleGmapUpdater::class);
  assert($config_updater instanceof SimpleGmapUpdater);
  \Drupal::classResolver(ConfigEntityUpdater::class)->update($sandbox, 'entity_view_display', function (EntityViewDisplayInterface $view_display) use ($config_updater): bool {
    return $config_updater->updateFieldFormatterAltText($view_display);
  });
}
