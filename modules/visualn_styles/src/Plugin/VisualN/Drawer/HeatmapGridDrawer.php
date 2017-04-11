<?php

/**
 * @file
 * Conatins HeatmapGridDrawer based on Day/Hour Heatmap d3.js script http://bl.ocks.org/tjdecke/5558084
 */

namespace Drupal\visualn_styles\Plugin\VisualN\Drawer;

use Drupal\visualn\Plugin\VisualNDrawerBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a 'HeatmapGrid' VisualN drawer.
 *
 * @VisualNDrawer(
 *  id = "visualn_heatmap_grid",
 *  label = @Translation("HeatmapGrid"),
 * )
 */
class HeatmapGridDrawer extends VisualNDrawerBase {

  /**
   * @inheritdoc
   */
  public function prepareBuild(array &$build, array $options = []) {
    // Attach drawer config to js settings
    parent::prepareBuild($build, $options);
    // Attach visualn style libraries
    $build['#attached']['library'][] = 'visualn_styles/heatmap-grid';
  }

  /**
   * @inheritdoc
   */
  public function jsId() {
    return 'visualnHeatmapGridDrawer';
  }

  /**
   * @inheritdoc
   */
  public function dataKeys() {
    $data_keys = [
      'day',
      'hour',
      'value',
    ];
    return $data_keys;
  }

}
