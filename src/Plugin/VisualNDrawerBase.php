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
    $config_values = $form_state->getValues();
    $config_values = $this->extractConfigArrayValues($config_values, []);

    $form_state->setValues($config_values);
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    // @todo: when all issues with form_state for subform are solved, this should also check $form_state->getValues()
    //    for drawer config

    // @todo: remove ::getConfigurationForm() method
    $config_form = $this->getConfigurationForm();
    $form = $config_form + $form;

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
   * Get Drawer configuration form array.
   *
   * @param array $configuration
   *
   * @return array $form
   */
  protected function getConfigurationForm(array $configuration = []) {
    // @todo: pass form element $parents so that it could be used e.g. for elements 'states' visibility etc.
    return [];
  }


  /**
   * @inheritdoc
   */
  public function extractFormValues($form, FormStateInterface $form_state) {
    // Since it is supposed to be subform_state, get all the values without limiting the scope.
    return $form_state->getValues();
  }

  /**
   * @inheritdoc
   *
   * @todo: maybe rename the method
   * @todo: maybe make static
   */
  public function extractConfigArrayValues(array $values, array $array_parents) {
    $values = NestedArray::getValue($values, $array_parents);
    return !empty($values) ? $values : [];
  }

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
