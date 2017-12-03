<?php

namespace Drupal\visualn_data_sources\Entity;

use Drupal\views\EntityViewsData;

/**
 * Provides Views data for VisualN Data Set entities.
 */
class VisualNDataSetViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    // Additional information for Views integration, such as table joins, can be
    // put here.

    return $data;
  }

}
