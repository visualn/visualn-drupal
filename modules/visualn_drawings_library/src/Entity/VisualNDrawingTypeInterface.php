<?php

namespace Drupal\visualn_drawings_library\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface for defining VisualN Drawing type entities.
 */
interface VisualNDrawingTypeInterface extends ConfigEntityInterface {

  /**
   * Get drawing fetcher field machine name default value for the entity type
   *
   * @todo: maybe rename the method
   */
  public function getDrawingFetcherField();

}
