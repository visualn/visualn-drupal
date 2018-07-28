<?php

namespace Drupal\visualn\Plugin;

use Drupal\Component\Plugin\PluginBase;
use Drupal\visualn\ResourceInterface;

/**
 * Base class for VisualN plugins.
 */
abstract class VisualNPluginBase extends PluginBase implements VisualNPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->setConfiguration($configuration);
  }

  /**
   * @inheritdoc
   *
   * @todo: maybe remove this abstract method since already contained in the interface
   */
  //abstract public function prepareBuild(array &$build, $vuid, ResourceInterface $resource);

  /**
   * @inheritdoc
   */
  public function jsId() {
    return $this->getPluginId();
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


}
