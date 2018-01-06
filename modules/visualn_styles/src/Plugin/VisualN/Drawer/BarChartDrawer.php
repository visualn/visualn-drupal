<?php

/**
 * @file
 * Conatins BarChartDrawer based on Day/Hour Heatmap d3.js script http://bl.ocks.org/Caged/6476579
 *
 * @todo: check license
 */

namespace Drupal\visualn_styles\Plugin\VisualN\Drawer;

use Drupal\visualn\Plugin\VisualNDrawerBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\visualn\ResourceInterface;

/**
 * Provides a 'BarChart' VisualN drawer.
 *
 * @VisualNDrawer(
 *  id = "visualn_bar_chart",
 *  label = @Translation("Bar Chart"),
 * )
 */
class BarChartDrawer extends VisualNDrawerBase {

  /**
   * @inheritdoc
   */
  public function prepareBuild(array &$build, $vuid, ResourceInterface $resource) {
    // Attach drawer config to js settings
    parent::prepareBuild($build, $vuid, $resource);
    // @todo: $resource = parent::prepareBuild($build, $vuid, $resource); (?)

    // Attach visualn style libraries
    $build['#attached']['library'][] = 'visualn_styles/bar-chart';

    return $resource;
  }

  /**
   * @inheritdoc
   */
  public function defaultConfiguration() {
    $default_config = [
      'y_label' => 'Frequency',
      'y_axis_tick' => 'numeric', // "numeric"|"percent"
    ];
    return $default_config;
  }

  /**
   * @inheritdoc
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $configuration = $this->extractFormValues($form, $form_state);
    $configuration =  $configuration + $this->configuration;

    $form['y_label'] = [
      '#type' => 'textfield',
      '#title' => t('Y Axis label'),
      '#default_value' => $configuration['y_label'],
      '#size' => 10,
    ];
    $form['y_axis_tick'] = [
      '#type' => 'select',
      '#title' => t('Y Axis label'),
      '#default_value' => $configuration['y_axis_tick'],
      '#options' => [
        'numeric' => t('Numeric'),
        'percent' => t('Percent'),
      ],
    ];
    return $form;
  }

  /**
   * @inheritdoc
   */
  public function jsId() {
    return 'visualnBarChartDrawer';
  }

  /**
   * @inheritdoc
   */
  public function dataKeys() {
    $data_keys = [
      'letter',
      'frequency',
    ];
    return $data_keys;
  }

}
