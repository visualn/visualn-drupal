<?php

/**
 * @file
 * Conatins SlickDrawer based for Slick.js library
 */

namespace Drupal\visualn_styles\Plugin\VisualN\Drawer;

use Drupal\visualn\Plugin\VisualNDrawerBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a 'SlickGalleryDrawer' VisualN drawer.
 *
 * @VisualNDrawer(
 *  id = "visualn_slick_gallery",
 *  label = @Translation("Slick Gallery"),
 * )
 */
class SlickGalleryDrawer extends VisualNDrawerBase {

  /**
   * @inheritdoc
   */
  public function prepareBuild(array &$build, $vuid, array $options = []) {
    // Attach drawer config to js settings
    parent::prepareBuild($build, $vuid, $options);
    // Attach visualn style libraries
    $build['#attached']['library'][] = 'visualn_styles/slick-gallery';
  }

  /**
   * @inheritdoc
   */
  public function defaultConfiguration() {
    $default_config = [
      'drawer_setup' => '', // slick config in json format
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
    // @todo: rename the key to drawer_setup to make it more generic
    $form['drawer_setup'] = [
      '#type' => 'textarea',
      '#title' => t('Slick setup'),
      '#default_value' => $configuration['drawer_setup'],
    ];
    // @todo: this will need ajax to update drawer fields subform without reopening
    $form['data_keys'] = [
      '#type' => 'textfield',
      '#title' => t('Slick data keys'),
      '#default_value' => $configuration['data_keys'],
    ];
    return $form;
  }

  /**
   * @inheritdoc
   */
  public function jsId() {
    return 'visualnSlickGalleryDrawer';
  }

  /**
   * @inheritdoc
   */
  public function prepareJSConfig(array &$drawer_config) {
    $drawer_config['slick_setup'] = json_decode($drawer_config['drawer_setup'], TRUE);
  }

  /**
   * @inheritdoc
   */
  public function dataKeys() {
    $slick_keys = trim($this->configuration['data_keys']);
    if (!empty($slick_keys)) {
      $data_keys = explode(',', $slick_keys);
      // @todo: trim every key string
    }
    else {
      $data_keys = [];
    }

    return $data_keys;
  }

}
