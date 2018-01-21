<?php

/**
 * @file
 * Conatins LineChartDrawer based on Line Chart d3.js script https://bl.ocks.org/mbostock/3883245
 */

namespace Drupal\visualn\Plugin\VisualN\Drawer;

use Drupal\visualn\Plugin\VisualNDrawerBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\visualn\ResourceInterface;

/**
 * Provides a 'Line Chart' VisualN drawer.
 *
 * @VisualNDrawer(
 *  id = "visualn_line_chart",
 *  label = @Translation("Line Chart"),
 * )
 */
class LineChartDrawer extends VisualNDrawerBase {


  /**
   * @inheritdoc
   */
  public function prepareBuild(array &$build, $vuid, ResourceInterface $resource) {
    // Attach drawer config to js settings
    parent::prepareBuild($build, $vuid, $resource);
    // @todo: $resource = parent::prepareBuild($build, $vuid, $resource); (?)

    // Attach visualn style libraries
    $build['#attached']['library'][] = 'visualn/d3-line-chart';

    return $resource;
  }

  /**
   * @inheritdoc
   */
  public function defaultConfiguration() {
    $default_config = [
      // set steelblue as default color
      'color' => '#4682b4',
      'y_label' => 'Y Axis',
    ];
    return $default_config;
  }

  /**
   * @inheritdoc
   */
  public function prepareJSConfig(array &$drawer_config) {
    if (!empty($drawer_config['y_label'])) {
      $drawer_config['y_label'] = t($drawer_config['y_label']);
    }
  }

  /**
   * @inheritdoc
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $configuration = $this->extractFormValues($form, $form_state);
    $configuration =  $configuration + $this->configuration;

    $form['color'] = [
      '#type' => 'color',
      '#title' => t('Color'),
      '#default_value' => $configuration['color'],
      '#required' => TRUE,
    ];
    $form['y_label'] = [
      '#type' => 'textfield',
      '#title' => t('Y Axis label'),
      '#default_value' => $configuration['y_label'],
      '#required' => TRUE,
      '#size' => 10,
    ];
    return $form;
  }

  /**
   * @inheritdoc
   */
  public function extractFormValues($form, FormStateInterface $form_state) {
    $values = parent::extractFormValues($form, $form_state);

    if (!empty($values)) {
      // @todo: validation should not allow whitespace values and color should be also validated
      $new_values = [
        'color' => trim($values['color']),
        'y_label' => trim($values['y_label']),
      ];
      // actually there are no more values but leave it for exteding drawers
      $values = $new_values + $values;
    }

    return $values;
  }

  /**
   * @inheritdoc
   */
  public function jsId() {
    return 'visualnLineChartDrawer';
  }

  /**
   * @inheritdoc
   */
  public function dataKeys() {
    $data_keys = [
      'date',
      'close',
    ];
    return $data_keys;
  }

}
