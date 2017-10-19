<?php

/**
 * @file
 * Conatins HighchartsWSSDrawer based for Highcharts.js library
 */

namespace Drupal\visualn_styles\Plugin\VisualN\Drawer;

use Drupal\visualn\Plugin\VisualNDrawerBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a 'HighchartsWSS' VisualN drawer.
 *
 * @VisualNDrawer(
 *  id = "visualn_highcharts_wss",
 *  label = @Translation("Highcharts With Setup Select"),
 * )
 */
class HighchartsWSSDrawer extends HighchartsDrawer {

  /**
   * @inheritdoc
   */
  public function defaultConfiguration() {
    $default_config = [
      'drawer_setup_id' => '',
    ];
    return $default_config;
  }

  /**
   * @inheritdoc
   */
  protected function getConfigurationForm(array $configuration = []) {
    $configuration =  $configuration + $this->configuration + $this->defaultConfiguration();
    $form = [];
    // The id of the VisualNSetup config entity
    $form['drawer_setup_id'] = [
      '#type' => 'select',
      '#title' => t('Drawer Setup'),
      '#options' => visualn_setup_options(FALSE),
      '#default_value' => $configuration['drawer_setup_id'],
      '#required' => TRUE,
    ];
    return $form;
  }

  /**
   * @inheritdoc
   */
  public function prepareJSCofig(array &$drawer_config) {

    // @todo: this can be a added to the DrawerBase class to be used across all WSS drawers (or to a trait)
    $visualn_setup_id = $drawer_config['drawer_setup_id'];
    // load setup entity
    $visualn_setup = \Drupal::service('entity_type.manager')->getStorage('visualn_setup')->load($visualn_setup_id);
    $setup_baker = $visualn_setup->getSetupBakerPlugin();

    // get setup from drawer setup entity
    // we expect the setup to be already json_decoded (actually an array)
    // as in $drawer_config['highcharts_setup'] = json_decode($drawer_config['highcharts_setup'], TRUE);
    $highcharts_setup = $setup_baker->bakeSetup();


    // set highcharts_setup key to send settings to js (the key is used in the base class)
    $drawer_config['highcharts_setup'] = $highcharts_setup;
  }

  /**
   * @inheritdoc
   */
  /*
  public function dataKeys() {
    $data_keys = [
      'name',
      'data',
    ];

    return $data_keys;
  }
  */

}
