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
    // @todo: remove ::getConfigurationForm() method
    $config_form = $this->getConfigurationForm();
    $form = $config_form + $form;

    return $form;
  }


  /**
   * @inheritdoc
   */
  public function prepareJSCofig(array &$drawer_config) {
  }

  /**
   * @inheritdoc
   */
  public function prepareBuild(array &$build, $vuid, array $options = []) {
    $drawer_config =  $this->configuration + $this->defaultConfiguration();
    $this->prepareJSCofig($drawer_config);
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
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    $this->configuration = $configuration;
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
