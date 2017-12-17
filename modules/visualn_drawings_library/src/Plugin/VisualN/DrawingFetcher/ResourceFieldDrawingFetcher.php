<?php

namespace Drupal\visualn_drawings_library\Plugin\VisualN\DrawingFetcher;

use Drupal\visualn_drawings_library\Plugin\VisualNDrawingFetcherBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a 'VisualN Resource field drawing fetcher' VisualN drawing fetcher.
 *
 * @VisualNDrawingFetcher(
 *  id = "visualn_resource_field",
 *  label = @Translation("VisualN Resource field drawing fetcher"),
 *  context = {
 *    "entity_type" = @ContextDefinition("string", label = @Translation("Entity type")),
 *    "bundle" = @ContextDefinition("string", label = @Translation("Bundle")),
 *    "current_entity" = @ContextDefinition("any", label = @Translation("Current entity"))
 *  }
 * )
 */
class ResourceFieldDrawingFetcher extends VisualNDrawingFetcherBase {

  /**
   * {@inheritdoc}
   */
  public function fetchDrawing() {
    $drawing_markup = parent::fetchDrawing();

    return $drawing_markup;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    return $form;
  }

}
