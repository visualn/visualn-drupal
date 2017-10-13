<?php

namespace Drupal\visualn\Plugin;

use Drupal\visualn\Plugin\VisualNDrawerBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Plugin\PluginBase;

/**
 * Base class for VisualN Wrapper Drawer plugins.
 *
 * We extend here PluginBase only (not VisualNDrawerBase) because all VisualN plugin specific
 * methods must be delegated to the subdrawer base drawer.
 *
 * @see \Drupal\visualn\Plugin\VisualNDrawerWrapperInterface
 */
// @todo: !IMPORTANT: drawer should always be actualized with every new change to the interface and base class
//    since it must include all the methods and wrapper around them to delegate to the subdrawer_base_drawer object.
abstract class VisualNDrawerWrapperBase extends PluginBase implements VisualNDrawerInterface {

  // Contains a reference to the base drawer object.
  public $subdrawer_base_drawer;

  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $subdrawer_base_drawer_id = $configuration['base_drawer_id'];
    $subdrawer_base_drawer_config = $configuration['base_drawer_config'];
    $this->subdrawer_base_drawer = \Drupal::service('plugin.manager.visualn.drawer')
                                  ->createInstance($subdrawer_base_drawer_id, $subdrawer_base_drawer_config);
  }

  /**
   * @inheritdoc
   *
   * @todo: add new comments here and for other docblocks regarding the role of the drawer wrapper
   */
  public function prepareBuild(array &$build, $vuid, array $options = []) {
    $this->subdrawer_base_drawer->prepareBuild($build, $vuid, $options);
  }

  /**
   * @inheritdoc
   */
  public function prepareJSCofig(array &$drawer_config) {
    $this->subdrawer_base_drawer->prepareJSCofig($drawer_config);
  }

  /**
   * @inheritdoc
   */
  // @todo: such drawers must be made protected (as it is here) to work correctly for subdrawers
  //    because buildConfigurationForm only should be overridden and accessed
  //    from outside
  protected function getConfigurationForm(array $configuration = []) {
    return $this->subdrawer_base_drawer->getConfigurationForm($configuration);
  }

  /**
   * @inheritdoc
   */
  public function extractConfigArrayValues(array $values, array $element_parents) {
    return $this->subdrawer_base_drawer->extractConfigArrayValues($values, $element_parents);
  }

  /**
   * @inheritdoc
   */
  public function jsId() {
    return $this->subdrawer_base_drawer->jsId();
  }

  /**
   * @inheritdoc
   */
  public function dataKeys() {
    return $this->subdrawer_base_drawer->dataKeys();
  }

  /**
   * @inheritdoc
   */
  public function dataKeysStructure() {
    return $this->subdrawer_base_drawer->dataKeysStructure();
  }





  // @todo: indicate interfaces that groups of methods belong here

  /**
   * @inheritdoc
   */
  public function defaultConfiguration() {
    return $this->subdrawer_base_drawer->defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    return $this->subdrawer_base_drawer->getConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    $this->subdrawer_base_drawer->setConfiguration($configuration);
    // return $this for chaining methods, otherwide base drawer instance would be returned
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    return $this->subdrawer_base_drawer->calculateDependencies();
  }




  // @todo: most of these should be moved to a base class

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->subdrawer_base_drawer->validateConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->subdrawer_base_drawer->submitConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    return $this->subdrawer_base_drawer->buildConfigurationForm($form, $form_state);
  }

}
