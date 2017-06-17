<?php

/**
 * @file
 * Conatins DashboardDrawer based on Dashboard d3.js script http://bl.ocks.org/NPashaP/96447623ef4d342ee09b
 */

namespace Drupal\visualn_styles\Plugin\VisualN\Drawer;

use Drupal\visualn\Plugin\VisualNDrawerBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a 'Dashboard' VisualN drawer.
 *
 * @VisualNDrawer(
 *  id = "visualn_dashboard",
 *  label = @Translation("Dashboard"),
 *  input = "visualn_basic_tree_input",
 * )
 */
class DashboardDrawer extends VisualNDrawerBase {


  /**
   * @inheritdoc
   */
  public function prepareBuild(array &$build, $vuid, array $options = []) {
    // Attach drawer config to js settings
    parent::prepareBuild($build, $vuid, $options);
    // Attach visualn style libraries
    $build['#attached']['library'][] = 'visualn_styles/dashboard';
  }

  /**
   * @inheritdoc
   */
  public function jsId() {
    return 'visualnDashboardDrawer';
  }

  /**
   * @inheritdoc
   */
  public function dataKeys() {
    $data_keys = [
      'State',
      'freq',
      'low',
      'mid',
      'high',
    ];
    return $data_keys;
  }

  /**
   * @inheritdoc
   */
  public function dataKeysStructure() {
    return [
      'State' => 'State',  // @todo: here can be an empty array "[]" which has the same sense
      'freq' => [
        'mapping' => 'freq',  // @todo: optional. can be omitted if coincides with key from dataKeys()
        'structure' => [
          'low' => ['mapping' => 'low', 'typeFunc' => 'parseInt'],
          'mid' => ['mapping' => 'mid', 'typeFunc' => 'parseInt'],
          'high' => ['mapping' => 'high', 'typeFunc' => 'parseInt'],
        ],
      ],
    ];
  }

}
