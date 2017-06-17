<?php

namespace Drupal\visualn\Plugin;

use Drupal\Component\Utility\NestedArray;
use Drupal\visualn\Entity\VisualNStyleInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Base class for VisualN Drawer plugins.
 */
abstract class VisualNDrawerBase extends VisualNPluginBase implements VisualNDrawerInterface {

  /**
   * @inheritdoc
   */
  public function prepareJSCofig(array &$drawer_config) {
  }

  /**
   * @inheritdoc
   */
  public function prepareBuild(array &$build, $vuid, array $options = []) {
    $drawer_config =  $this->configuration + $this->getDefaultConfig();
    $this->prepareJSCofig($drawer_config);
    $build['#attached']['drupalSettings']['visualn']['drawings'][$vuid]['drawer']['config'] = $drawer_config;

    $drawer_js_id = $this->jsId();  // defaults to plugin id if not overriden in drawer plugin class.
    $build['#attached']['drupalSettings']['visualn']['drawings'][$vuid]['drawer']['drawerId'] = $drawer_js_id;
    $build['#attached']['drupalSettings']['visualn']['handlerItems']['drawings'][$drawer_js_id][$vuid] = $vuid;  // @todo: this settings is just for reference
  }

  /**
   * @inheritdoc
   */
  public function getDefaultConfig() {
    return [];
  }

  /**
   * @inheritdoc
   */
  public function getConfigForm(array $configuration = []) {
    // @todo: pass form element $parents so that it could be used e.g. for elements 'states' visibility etc.
    return [];
  }

  /**
   * @inheritdoc
   */
  public function extractConfigFormValues(FormStateInterface $form_state, array $element_parents) {
    $drawer_config_values  = $form_state->getValue($element_parents);
    $drawer_config_values  = !empty($drawer_config_values) ? $drawer_config_values : [];
    $drawer_config_values = $this->extractConfigArrayValues($drawer_config_values, []);
    return $drawer_config_values;
  }

  /**
   * @inheritdoc
   *
   * @todo: maybe rename the method
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
