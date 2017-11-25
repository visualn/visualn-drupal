<?php

/**
 * @file
 * Conatins DrawerGenericBoilerplate class
 */

namespace Drupal\visualn\DrawerBoilerplate;

use Drupal\visualn\Plugin\VisualNDrawerBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Boilerplate class for VisualN Drawer plugins.
 *
 * @see \Drupal\visualn\Plugin\VisualNDrawerBase
 * @see \Drupal\visualn\Plugin\VisualNDrawerInterface
 */
abstract class DrawerGenericBoilerplate extends VisualNDrawerBase {

  /**
   * @inheritdoc
   */
/*
  public function prepareBuild(array &$build, $vuid, array $options = []) {
    // Attach drawer config to js settings
    parent::prepareBuild($build, $vuid, $options);
    // Attach visualn style libraries
    $build['#attached']['library'][] = 'module_name/drawer-library';
  }
*/

  /**
   * @inheritdoc
   */
  public function defaultConfiguration() {
    $default_config = [
      'drawer_setup' => '',
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

    $form['drawer_setup'] = [
      '#type' => 'textarea',
      '#title' => t('Drawer setup'),
      '#default_value' => $configuration['drawer_setup'],
      '#description' => t('Drawer setup in JSON format'),
    ];
    // @todo: this will need ajax to update drawer fields subform without reopening
    $form['data_keys'] = [
      '#type' => 'textfield',
      '#title' => t('Drawer data keys'),
      '#default_value' => $configuration['data_keys'],
    ];
    return $form;
  }

  /**
   * @inheritdoc
   */
/*
  public function jsId() {
    return 'module_nameClassName';
  }
*/

  /**
   * @inheritdoc
   */
  public function prepareJSConfig(array &$drawer_config) {
    $drawer_config['drawer_setup'] = json_decode($drawer_config['drawer_setup'], TRUE);
  }

  /**
   * @inheritdoc
   */
  public function dataKeys() {
    $data_keys_str = trim($this->configuration['data_keys']);
    if (!empty($data_keys_str)) {
      $data_keys = explode(',', $data_keys_str);
      // @todo: trim every key string
    }
    else {
      $data_keys = [];
    }

    return $data_keys;
  }

}
