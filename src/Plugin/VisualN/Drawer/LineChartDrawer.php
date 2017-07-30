<?php

/**
 * @file
 * Conatins LineChartDrawer based on Line Chart d3.js script https://bl.ocks.org/mbostock/3883245
 */

namespace Drupal\visualn\Plugin\VisualN\Drawer;

use Drupal\visualn\Plugin\VisualNDrawerBase;
use Drupal\Core\Form\FormStateInterface;

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
  public function prepareBuild(array &$build, $vuid, array $options = []) {
    // Attach drawer config to js settings
    parent::prepareBuild($build, $vuid, $options);
    // Attach visualn style libraries
    $build['#attached']['library'][] = 'visualn/d3-line-chart';
  }

  /**
   * @inheritdoc
   */
  public function defaultConfiguration() {
    $default_config = [
      'color' => 'steelblue',
      'y_label' => 'Y Axis',
    ];
    return $default_config;
  }

  /**
   * @inheritdoc
   */
  public function prepareJSCofig(array &$drawer_config) {
    if (!empty($drawer_config['y_label'])) {
      $drawer_config['y_label'] = t($drawer_config['y_label']);
    }
  }

  /**
   * @inheritdoc
   */
  protected function getConfigurationForm(array $configuration = []) {
    // @todo: rename to this::defaultConfig() to get ConfigForm default values
    $configuration =  $configuration + $this->configuration + $this->defaultConfiguration();
    $form = [];
    $form['color'] = [
      '#type' => 'textfield',
      '#title' => t('Color'),
      '#default_value' => $configuration['color'],
      '#size' => 10,
    ];
    $form['y_label'] = [
      '#type' => 'textfield',
      '#title' => t('Y Axis label'),
      '#default_value' => $configuration['y_label'],
      '#size' => 10,
    ];
    return $form;
  }

  /**
   * @inheritdoc
   */
  public function extractConfigArrayValues(array $values, array $element_parents) {
    $values = parent::extractConfigArrayValues($values, $element_parents);
    $default_config = $this->defaultConfiguration();
    $drawer_config_values = [
      'color' => trim($values['color']),
      'y_label' => trim($values['y_label']),
    ];
    // do not override if value is empty
    // @todo: maybe also check if not equal to defualt config value
    // but this should be checked against not necessarily default config by the config that should be overridden (e.g. file formatter config)
    foreach ($drawer_config_values as $k => $v) {
      if (empty($drawer_config_values[$k])) {
        unset($drawer_config_values[$k]);
      }
    }
    return $drawer_config_values;
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
