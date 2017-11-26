<?php

namespace Drupal\visualn\Plugin;

use Drupal\Component\Utility\NestedArray;
use Drupal\visualn\Entity\VisualNStyleInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Base class for VisualN Drawer plugins.
 *
 * @see \Drupal\visualn\Plugin\VisualNDrawerInterface
 */
abstract class VisualNDrawerBase extends VisualNPluginBase implements VisualNDrawerInterface {

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->setConfiguration($configuration);
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    // Clean submitted values
    $drawer_config = $this->extractFormValues($form, $form_state);
    $form_state->setValues($drawer_config);
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    //The drawer_config from form_state should not change the plugin configuration,
    //it is only used to build the form according to that config.
    $drawer_config = $this->extractFormValues($form, $form_state);

    $form['markup'] = [
      '#markup' => '<div>' . t('No configuration provided for this drawer') . '</div>',
    ];

    return $form;
  }


  /**
   * @inheritdoc
   */
  public function prepareJSConfig(array &$drawer_config) {
  }

  /**
   * @inheritdoc
   */
  public function prepareBuild(array &$build, $vuid, array $options = []) {
    $drawer_config =  $this->configuration + $this->defaultConfiguration();
    $this->prepareJSConfig($drawer_config);
    $build['#attached']['drupalSettings']['visualn']['drawings'][$vuid]['drawer']['config'] = $drawer_config;

    $drawer_js_id = $this->jsId();  // defaults to plugin id if not overriden in drawer plugin class.
    $build['#attached']['drupalSettings']['visualn']['drawings'][$vuid]['drawer']['drawerId'] = $drawer_js_id;
    $build['#attached']['drupalSettings']['visualn']['handlerItems']['drawings'][$drawer_js_id][$vuid] = $vuid;  // @todo: this settings is just for reference

    $drawer_info = [];
    $drawer_info['data_keys_structure'] = $this->dataKeysStructure();
    // generally there will be only one element with "0" index but we keep it for consistency
    // with default workflow (see $chain array in DefaultManager class)
    $build['#visualn']['chain_info']['drawer'] = !empty($build['#visualn']['chain_info']['drawer']) ? $build['#visualn']['chain_info']['drawer'] : [];
    $build['#visualn']['chain_info']['drawer'][] = $drawer_info;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    return $this->configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    // @todo: use NestedArray::mergeDeep here. See BlockBase::setConfiguration for example.
    // @todo: also do the same for all other plugin types
    $this->configuration = $configuration + $this->defaultConfiguration();
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    return [];
  }


  /**
   * @inheritdoc
   */
  public function extractFormValues($form, FormStateInterface $form_state) {
    // Since it is supposed to be subform_state, get all the values without limiting the scope.
    return $form_state->getValues();
  }

  // @todo: remove legacy methods

  /**
   * @inheritdoc
   */
  public function dataKeys() {
    return [];
  }

  /**
   * @inheritdoc
   */
  public function dataKeysStructure() {
    return [];
  }

  // @todo: should it be d3.js or just a generic js object?
  //'input' =>$this->pluginDefinition['input'], // this input type represents a generic d3.js object with correctly mapped data keys

}
