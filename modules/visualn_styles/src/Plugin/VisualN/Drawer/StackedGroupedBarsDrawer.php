<?php

/**
 * @file
 * Conatins StackedGroupedBarsDrawer based on StackedGroupedBars d3.js script http://bl.ocks.org/mbostock/3943967 (GPLv3)
 */

namespace Drupal\visualn_styles\Plugin\VisualN\Drawer;

use Drupal\visualn\Plugin\VisualNDrawerBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\visualn\ResourceInterface;

/**
 * Provides a 'Dashboard' VisualN drawer.
 *
 * @VisualNDrawer(
 *  id = "visualn_stacked_grouped_bars",
 *  label = @Translation("Stacked to Grouped Bars"),
 * )
 */
class StackedGroupedBarsDrawer extends VisualNDrawerBase {


  /**
   * @inheritdoc
   */
  public function prepareBuild(array &$build, $vuid, ResourceInterface $resource) {
    // Attach drawer config to js settings
    parent::prepareBuild($build, $vuid, $resource);
    // @todo: $resource = parent::prepareBuild($build, $vuid, $resource); (?)

    // Attach visualn style libraries
    $build['#attached']['library'][] = 'visualn_styles/stacked-grouped-bars';

    return $resource;
  }

  /**
   * @inheritdoc
   */
  public function jsId() {
    return 'visualnStackedGroupedBarsDrawer';
  }

  /**
   * @inheritdoc
   */
  public function dataKeys() {
    $data_keys = [
      'first',
      'second',
      'third',
      'fourth',
    ];
    return $data_keys;
  }

}
