<?php

/**
 * @file
 * Conatins HighchartsBarDrawer based for Highcharts.js library
 */

namespace Drupal\visualn_styles\Plugin\VisualN\Drawer;

use Drupal\visualn\Plugin\VisualNDrawerBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a 'HighchartsBar' VisualN drawer.
 *
 * @VisualNDrawer(
 *  id = "visualn_highcharts_bar",
 *  label = @Translation("Highcharts Bar"),
 * )
 */
class HighchartsBarDrawer extends HighchartsDrawer {

  /**
   * @inheritdoc
   */
  public function defaultConfiguration() {
    $default_config = [
      'title' => 'Chart title',
      'x_axis_categories' => 'Category1,Category2,Category3',
      'y_axis_title' => 'Y Axis Title',
    ];
    return $default_config;
  }

  /**
   * @inheritdoc
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $configuration = $this->extractFormValues($form, $form_state);
    $configuration =  $configuration + $this->configuration;

    $form['title'] = [
      '#type' => 'textfield',
      '#title' => t('Chart title'),
      '#default_value' => $configuration['title'],
      '#size' => 10,
    ];
    $form['x_axis_categories'] = [
      '#type' => 'textfield',
      '#title' => t('Categories'),
      '#default_value' => $configuration['x_axis_categories'],
      '#size' => 10,
    ];
    $form['y_axis_title'] = [
      '#type' => 'textfield',
      '#title' => t('Y Axis label'),
      '#default_value' => $configuration['y_axis_title'],
      '#size' => 10,
    ];

    return $form;
  }

  /**
   * @inheritdoc
   */
  public function prepareJSConfig(array &$drawer_config) {
    //$drawer_config['highcharts_setup'] = json_decode($drawer_config['highcharts_setup'], TRUE);

    $default_config = [
      'title' => 'Chart title',
      'x_axis_categories' => 'Category1,Category2,Category3',
      'y_axis_title' => 'Y Axis Title',
    ];
    // @see https://www.highcharts.com/docs/getting-started/your-first-chart
    $highcharts_setup = [
      'chart' => ['type' => 'bar'],
      //'title' => ['text' => 'Fruit Consuption'],
      'title' => ['text' => $drawer_config['title']],
      //'xAxis' => ['categories' => ['Apples', 'Bananas', 'Oranges']],
      'xAxis' => ['categories' => explode(',', $drawer_config['x_axis_categories'])],
      //'yAxis' => ['title' => ['text' => 'Fruit Consuption']],
      'yAxis' => ['title' => ['text' => $drawer_config['y_axis_title']]],
      'series' => [['name' => 'Jane', 'data' => [1, 0, 4]], ['name' => 'John', 'data' => [5, 7, 3]]],
    ];
    $drawer_config['highcharts_setup'] = $highcharts_setup;
  }

  /**
   * @inheritdoc
   */
  public function dataKeys() {
    $data_keys = [
      'name',
      'data',
    ];

    return $data_keys;
  }

}
