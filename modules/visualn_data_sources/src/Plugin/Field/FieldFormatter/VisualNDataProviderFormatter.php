<?php

namespace Drupal\visualn_data_sources\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'visualn_data_provider' formatter.
 *
 * @FieldFormatter(
 *   id = "visualn_data_provider",
 *   label = @Translation("VisualN data provider"),
 *   field_types = {
 *     "visualn_data_provider"
 *   }
 * )
 */
class VisualNDataProviderFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      // Implement default settings.
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    return [
      // Implement settings form.
    ] + parent::settingsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    // Implement settings summary.

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    foreach ($items as $delta => $item) {
      $elements[$delta] = ['#markup' => $this->viewValue($item)];
    }

    return $elements;
  }

  /**
   * Generate the output appropriate for one field item.
   *
   * @param \Drupal\Core\Field\FieldItemInterface $item
   *   One field item.
   *
   * @return string
   *   The textual output generated.
   */
  protected function viewValue(FieldItemInterface $item) {
    $output = '';
    $data_provider_plugin = $item->getDataProviderPlugin();
    if (!is_null($data_provider_plugin)) {
      //$output = $data_provider_plugin->label();
      $output = print_r($data_provider_plugin->getConfiguration(), 1);
    }

    return '<pre>' . $output . '</pre>';

    //dsm($data_provider_plugin->getOutputType());
    //dsm($data_provider_plugin->getOutputInterface());
  }

}
