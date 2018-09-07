<?php

namespace Drupal\visualn_resource\Plugin\VisualN\DrawingFetcher;

use Drupal\visualn\Plugin\VisualNDrawingFetcherBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a 'Url field reader' VisualN drawing fetcher.
 *
 * @todo: implement fetcher logic
 *
 * @ingroup fetcher_plugins
 *
 * @VisualNDrawingFetcher(
 *  id = "visualn_url_field_reader",
 *  label = @Translation("Url field reader (*not working*)"),
 *  context = {
 *    "entity_type" = @ContextDefinition("string", label = @Translation("Entity type")),
 *    "bundle" = @ContextDefinition("string", label = @Translation("Bundle")),
 *    "current_entity" = @ContextDefinition("any", label = @Translation("Current entity"))
 *  }
 * )
 */
class UrlFieldReaderDrawingFetcher extends VisualNDrawingFetcherBase {

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
