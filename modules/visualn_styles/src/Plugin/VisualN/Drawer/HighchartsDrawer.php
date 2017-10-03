<?php

/**
 * @file
 * Conatins HighchartsDrawer based for Highcharts.js library
 */

namespace Drupal\visualn_styles\Plugin\VisualN\Drawer;

use Drupal\visualn\Plugin\VisualNDrawerBase;
use Drupal\Core\Form\FormStateInterface;

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
  public function prepareBuild(array &$build, $vuid, array $options = []) {
    // Attach drawer config to js settings
    parent::prepareBuild($build, $vuid, $options);
    // Attach visualn style libraries
    $build['#attached']['library'][] = 'visualn_styles/highcharts';
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
  protected function getConfigurationForm(array $configuration = []) {
    $configuration =  $configuration + $this->configuration + $this->defaultConfiguration();
    $form = [];
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
  public function prepareJSCofig(array &$drawer_config) {
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
