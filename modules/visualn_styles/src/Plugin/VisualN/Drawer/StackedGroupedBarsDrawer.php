<?php

/**
 * @file
 * Conatins StackedGroupedBarsDrawer based on StackedGroupedBars d3.js script http://bl.ocks.org/mbostock/3943967 (GPLv3)
 */

namespace Drupal\visualn_styles\Plugin\VisualN\Drawer;

use Drupal\visualn\Plugin\VisualNDrawerBase;
use Drupal\Core\Form\FormStateInterface;

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
  public function prepareBuild(array &$build, $vuid, array $options = []) {
    // Attach drawer config to js settings
    parent::prepareBuild($build, $vuid, $options);
    // Attach visualn style libraries
    $build['#attached']['library'][] = 'visualn_styles/stacked-grouped-bars';
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
