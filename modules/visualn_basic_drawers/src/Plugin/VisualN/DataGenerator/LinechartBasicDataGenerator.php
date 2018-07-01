<?php

namespace Drupal\visualn_basic_drawers\Plugin\VisualN\DataGenerator;

use Drupal\visualn\Plugin\VisualNDataGeneratorBase;
use Drupal\Core\Form\FormStateInterface;

// @todo: as a good practice, use the same machine name as for main compatible drawer
//   document in best practices

/**
 * Provides an 'Linechart Basic' VisualN data generator.
 *
 * @ingroup data_generator_plugins
 *
 * @VisualNDataGenerator(
 *  id = "visualn_linechart_basic",
 *  label = @Translation("Linechart Basic Data Generator"),
 *  compatible_drawers = {
 *    "visualn_linechart_basic"
 *  }
 * )
 */
class LinechartBasicDataGenerator extends VisualNDataGeneratorBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'number' => '5',
    ] + parent::defaultConfiguration();
 }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['number'] = [
      '#type' => 'number',
      '#title' => t('Number of points'),
      '#default_value' => $this->configuration['number'],
      '#min' => 1,
      '#max' => 15,
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function generateData() {
    $data = [];

    for ($i = 0; $i < $this->configuration['number']; $i++) {
      $data[] = [
        'x' => $i+1,
        'data1' => mt_rand(0, 9),
        'data2' => mt_rand(0, 9),
      ];
    }

    return $data;
  }

}
