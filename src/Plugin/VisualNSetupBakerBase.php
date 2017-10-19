<?php

namespace Drupal\visualn\Plugin;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Base class for VisualN Setup Baker plugins.
 */
abstract class VisualNSetupBakerBase extends PluginBase implements VisualNSetupBakerInterface {

  /**
   * {@inheritdoc}
   */
  // @todo: should bakeSetup() allow optional arguments (?)
  public function bakeSetup() {
    // @todo: this is not a good practice to return configuration, at least for implementing instances.
    //    the method should return raw setup (discussible) for the drawer to use (?)
    //    also it should be alreade decoded to array (if JSON is used to store setup in baker config)
    return $this->getConfiguration();
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
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    return $form;
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
    $this->configuration = $configuration + $this->defaultConfiguration();
    return $this;
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
  public function calculateDependencies() {
    return [];
  }

}
