<?php

namespace Drupal\visualn_data_sources\Plugin\VisualN\DataGenerator;

use Drupal\visualn_data_sources\Plugin\VisualNDataGeneratorBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides an 'Leaflet Data Generator' VisualN data generator.
 *
 * @VisualNDataGenerator(
 *  id = "visualn_leaflet_random",
 *  label = @Translation("Leaflet Data Generator")
 * )
 */
class LeafletDataGenerator extends VisualNDataGeneratorBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'center_lat' => '51.8',
      'center_lon' => '104.8',
    ] + parent::defaultConfiguration();
 }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['center_lat'] = [
      '#type' => 'textfield',
      '#title' => t('Center latitude'),
      '#default_value' => $this->configuration['center_lat'],
      '#required' => TRUE,
    ];
    $form['center_lon'] = [
      '#type' => 'textfield',
      '#title' => t('Center longitude'),
      '#default_value' => $this->configuration['center_lon'],
      '#required' => TRUE,
    ];

    return $form;
  }

  // @todo: add to interface and to the base class as abstract method
  public function generateData() {
    $data = [];
    // @todo: if we want to send data as is, then there should be some adapter to transpose data
    //    or make existing adapter configurable
    //$data[] = ['title', 'lat', 'lon'];
    foreach (['first', 'second', 'third'] as $k => $title) {
      $data[] = [
        $title, $this->configuration['center_lat'] + mt_rand() / mt_getrandmax()*0.2 - 0.1,
        $this->configuration['center_lon'] + mt_rand() / mt_getrandmax()*0.2 - 0.1
      ];

    }

    $ready_data = [];
    foreach ($data as $k => $val) {
      $ready_data[] = [
        'title' => $val[0],
        'lat' => $val[1],
        'lon' => $val[2],
      ];
    }
    $data = $ready_data;

    return $data;
  }

}
