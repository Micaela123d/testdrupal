<?php

namespace Drupal\simple_gmap;

use Drupal\Core\Entity\Display\EntityViewDisplayInterface;

/**
 * Provides a BC layer for modules providing old configurations.
 *
 * @internal
 */
class SimpleGmapUpdater {

  /**
   * Processes oembed type fields.
   *
   * @param \Drupal\Core\Entity\Display\EntityViewDisplayInterface $view_display
   *   The view display.
   *
   * @return bool
   *   Whether the display was updated.
   */
  public function updateFieldFormatterAltText(EntityViewDisplayInterface $view_display): bool {
    $changed = FALSE;

    foreach ($view_display->getComponents() as $field => $component) {
      if (array_key_exists('type', $component)
        && ($component['type'] === 'simple_gmap')
        && !array_key_exists('alt_text', $component['settings'])) {
        $component['settings']['alt_text'] = 'Map location: %address';
        $view_display->setComponent($field, $component);
        $changed = TRUE;
      }
    }
    return $changed;
  }

}
