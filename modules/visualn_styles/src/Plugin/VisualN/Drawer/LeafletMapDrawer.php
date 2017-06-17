<?php

/**
 * @file
 * Conatins LefletMapDrawer based on leaflet.js
 */

namespace Drupal\visualn_styles\Plugin\VisualN\Drawer;

use Drupal\visualn\Plugin\VisualNDrawerBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a 'LefletMap' VisualN drawer.
 *
 * @VisualNDrawer(
 *  id = "visualn_leaflet_map",
 *  label = @Translation("LefletMap"),
 * )
 */
class LeafletMapDrawer extends VisualNDrawerBase {

  /**
   * @inheritdoc
   */
  public function prepareBuild(array &$build, $vuid, array $options = []) {
    // Attach drawer config to js settings
    parent::prepareBuild($build, $vuid, $options);
    // Attach visualn style libraries
    $build['#attached']['library'][] = 'visualn_styles/leaflet-map';
  }

  /**
   * @inheritdoc
   */
  public function getDefaultConfig() {
    $default_config = [
      'center_lat' => -27.11667,
      'center_lon' => -109.35000,
      'calculate_center' => 1,
    ];
    return $default_config;
  }

  /**
   * @inheritdoc
   */
  public function extractConfigArrayValues(array $values, array $element_parents) {
    $values = parent::extractConfigArrayValues($values, $element_parents);
    $default_config = $this->getDefaultConfig();  // @todo: remove if not used
    $drawer_config_values = [
      'center_lat' => trim($values['center_lat']),
      'center_lon' => trim($values['center_lon']),
    ];

    // do not override if value is empty
    // @todo: maybe also check if not equal to defualt config value
    foreach ($drawer_config_values as $k => $v) {
      if (empty($drawer_config_values[$k])) {
      //if (empty($drawer_config_values[$k]) && $drawer_config_values[$k] != $default_config[$k]) {
        unset($drawer_config_values[$k]);
      }
    }

    // @todo: what to do with checkbox? here we don't unset in the upper cycle checkbox value even if empty
    unset($values['center_lat']);
    unset($values['center_lon']);
    $drawer_config_values += $values;
    return $drawer_config_values;
  }

  /**
   * @inheritdoc
   */
  public function getConfigForm(array $configuration = []) {
    $configuration =  $configuration + $this->configuration + $this->getDefaultConfig();
    $form = [];
    $form['center_lat'] = [
      '#type' => 'textfield',
      '#title' => t('Center latitude'),
      '#default_value' => $configuration['center_lat'],
      '#size' => 10,
    ];
    $form['center_lon'] = [
      '#type' => 'textfield',
      '#title' => t('Center longitude'),
      '#default_value' => $configuration['center_lon'],
      '#size' => 10,
    ];
    $form['calculate_center'] = [
      '#type' => 'checkbox',
      '#title' => t('Calculate center'),
      '#default_value' => $configuration['calculate_center'],
    ];
    return $form;
  }

  /**
   * @inheritdoc
   */
  public function jsId() {
    return 'visualnLefletMapDrawer';
  }

  /**
   * @inheritdoc
   */
  public function dataKeys() {
    $data_keys = [
      'title',
      'lon',
      'lat',
    ];
    return $data_keys;
  }

}
