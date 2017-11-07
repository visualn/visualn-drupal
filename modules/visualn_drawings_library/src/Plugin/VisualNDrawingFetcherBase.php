<?php

namespace Drupal\visualn_drawings_library\Plugin;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\visualn_drawings_library\Entity\VisualNDrawing;

/**
 * Base class for VisualN Drawing Fetcher plugins.
 */
abstract class VisualNDrawingFetcherBase extends PluginBase implements VisualNDrawingFetcherInterface {

  protected $drawing_entity;

  protected $entity_type;

  protected $entity_bundle;

  /**
   * {@inheritdoc}
   *
   * @todo: review this method name, argments and usage
   */
  public function setEntityInfo($entity_type, $entity_bundle) {
    $this->entity_type = $entity_type;
    $this->entity_bundle = $entity_bundle;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setDrawingEntity(VisualNDrawing $entity) {
    $this->drawing_entity = $entity;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function fetchDrawing() {
    return ['#markup' => ''];
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
