<?php

namespace Drupal\visualn_views\Plugin\VisualN\DrawingFetcher;

use Drupal\visualn\Plugin\VisualNDrawingFetcherBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a 'VisualN Views drawing fetcher' VisualN drawing fetcher.
 *
 * @todo: implement fetcher logic
 *
 * @ingroup fetcher_plugins
 *
 * @VisualNDrawingFetcher(
 *  id = "visualn_views",
 *  label = @Translation("VisualN Views drawing fetcher")
 * )
 */
class ViewsDrawingFetcher extends VisualNDrawingFetcherBase {

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
