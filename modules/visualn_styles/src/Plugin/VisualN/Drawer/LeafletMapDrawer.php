<?php

/**
 * @file
 * Conatins LefletMapDrawer based on leaflet.js
 */

namespace Drupal\visualn_styles\Plugin\VisualN\Drawer;

use Drupal\visualn\Plugin\VisualNDrawerBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\visualn\ResourceInterface;

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
  public function prepareBuild(array &$build, $vuid, ResourceInterface $resource) {
    // Attach drawer config to js settings
    parent::prepareBuild($build, $vuid, $resource);
    // @todo: $resource = parent::prepareBuild($build, $vuid, $resource); (?)

    // Attach visualn style libraries
    $build['#attached']['library'][] = 'visualn_styles/leaflet-map';

    return $resource;
  }

  /**
   * @inheritdoc
   */
  public function defaultConfiguration() {
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
  public function extractFormValues($form, FormStateInterface $form_state) {
    $values = parent::extractFormValues($form, $form_state);

    if (!empty($values)) {
      // @todo: validation should not allow whitespace values and generally any non-geo values
      $new_values = [
        'center_lat' => trim($values['center_lat']),
        'center_lon' => trim($values['center_lon']),
      ];
      // attach 'calculate_center' flag value
      // also considers values possibly added by extending drawers
      $values = $new_values + $values;
    }

    return $values;
  }

  /**
   * @inheritdoc
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $configuration = $this->extractFormValues($form, $form_state);
    $configuration =  $configuration + $this->configuration;

    $form['center_lat'] = [
      '#type' => 'textfield',
      '#title' => t('Center latitude'),
      '#default_value' => $configuration['center_lat'],
      '#required' => TRUE,
      '#size' => 10,
    ];
    $form['center_lon'] = [
      '#type' => 'textfield',
      '#title' => t('Center longitude'),
      '#default_value' => $configuration['center_lon'],
      '#required' => TRUE,
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
