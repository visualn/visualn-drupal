<?php

/**
 * @file
 * Conatins HighchartsDrawer based for Highcharts.js library
 */

namespace Drupal\visualn_styles\Plugin\VisualN\Drawer;

use Drupal\visualn\Plugin\VisualNDrawerBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\visualn\ResourceInterface;

/**
 * Provides a 'Highcharts' VisualN drawer.
 *
 * @VisualNDrawer(
 *  id = "visualn_highcharts",
 *  label = @Translation("Highcharts"),
 * )
 */
class HighchartsDrawer extends VisualNDrawerBase {

  /**
   * @inheritdoc
   */
  public function prepareBuild(array &$build, $vuid, ResourceInterface $resource) {
    // Attach drawer config to js settings
    parent::prepareBuild($build, $vuid, $resource);
    // @todo: $resource = parent::prepareBuild($build, $vuid, $resource); (?)

    // Attach visualn style libraries
    $build['#attached']['library'][] = 'visualn_styles/highcharts';

    return $resource;
  }

  /**
   * @inheritdoc
   */
  public function defaultConfiguration() {
    $default_config = [
      'highcharts_setup' => '', // highcharts config in json format
      'data_keys' => '',
    ];
    return $default_config;
  }

  /**
   * @inheritdoc
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $configuration = $this->extractFormValues($form, $form_state);
    $configuration =  $configuration + $this->configuration;

    $form['highcharts_setup'] = [
      '#type' => 'textarea',
      '#title' => t('Highcharts setup'),
      '#default_value' => $configuration['highcharts_setup'],
    ];
    // @todo: this will need ajax to update drawer fields subform without reopening
    $form['data_keys'] = [
      '#type' => 'textfield',
      '#title' => t('Highcharts data keys'),
      '#default_value' => $configuration['data_keys'],
    ];
    return $form;
  }

  /**
   * @inheritdoc
   */
  public function jsId() {
    return 'visualnHighchartsDrawer';
  }

  /**
   * @inheritdoc
   */
  public function prepareJSConfig(array &$drawer_config) {
    $drawer_config['highcharts_setup'] = json_decode($drawer_config['highcharts_setup'], TRUE);

    // @see https://www.highcharts.com/docs/getting-started/your-first-chart
    /*
    $highcharts_setup = [
      'chart' => ['type' => 'bar'],
      'title' => ['text' => 'Fruit Consuption'],
      'xAxis' => ['categories' => ['Apples', 'Bananas', 'Oranges']],
      'yAxis' => ['title' => ['text' => 'Fruit Consuption']],
      'series' => [['name' => 'Jane', 'data' => [1, 0, 4]], ['name' => 'John', 'data' => [5, 7, 3]]],
    ];
    dsm(json_encode($highcharts_setup));
    */
  }

  /**
   * @inheritdoc
   */
  public function dataKeys() {
    $highcharts_keys = trim($this->configuration['data_keys']);
    if (!empty($highcharts_keys)) {
      $data_keys = explode(',', $highcharts_keys);
      // @todo: trim every key string
    }
    else {
      $data_keys = [];
    }

    return $data_keys;
  }

}
