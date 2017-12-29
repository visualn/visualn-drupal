<?php

namespace Drupal\visualn_file\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\NestedArray;
use Symfony\Component\HttpFoundation\Request;
use Drupal\image\Plugin\Field\FieldFormatter\ImageFormatter;
use Drupal\Core\Render\Element;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\visualn\Helpers\VisualNFormsHelper;
use Drupal\visualn\Helpers\VisualN;

/**
 * Plugin implementation of the 'visualn_image' formatter.
 *
 * @FieldFormatter(
 *   id = "visualn_image",
 *   label = @Translation("VisualN image"),
 *   field_types = {
 *     "image"
 *   },
 *   quickedit = {
 *     "editor" = "image"
 *   }
 * )
 */
//class VisualNImageFormatter extends FormatterBase {
class VisualNImageFormatter extends ImageFormatter {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'visualn_style_id' => '',
      'drawer_config' => [],
      'drawer_fields' => [],
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);

    $visualn_styles = visualn_style_options(FALSE);
    $description_link = Link::fromTextAndUrl(
      $this->t('Configure VisualN Styles'),
      Url::fromRoute('entity.visualn_style.collection')
    );

    $visualn_style_id = $this->getSetting('visualn_style_id');

    // @todo: choose a more explicit formatter
    $ajax_wrapper_id = 'visualn-image-formatter-drawer-config-form-ajax-wrapper';
    $form['visualn_style_id'] = [
      '#type' => 'select',
      '#title' => t('VisualN style'),
      '#options' => $visualn_styles,
      '#default_value' => $visualn_style_id,
      '#description' => t('Default style for the data to render.'),
      // @todo: add permission check for current user
      '#description' => $description_link->toRenderable() + [
        //'#access' => $this->currentUser->hasPermission('administer visualn styles')
        '#access' => TRUE
      ],
      '#ajax' => [
        'callback' => [get_called_class(), 'ajaxCallback'],
        'wrapper' => $ajax_wrapper_id,
      ],
      '#required' => TRUE,
      '#empty_value' => '',
      '#empty_option' => t('- Select visualization style -'),
    ];
    $form['drawer_container'] = [
      '#prefix' => '<div id="' . $ajax_wrapper_id . '">',
      '#suffix' => '</div>',
      '#type' => 'container',
      //'#process' => [[get_called_class(), 'processDrawerContainerSubform']],
      '#process' => [[$this, 'processDrawerContainerSubform']],
    ];
    //$form['drawer_container']['#stored_configuration'] = $this->getSetting('drawer_config');
    // @todo: basically just this->getSettings() can be passed
    $form['drawer_container']['#stored_configuration'] = [
      'visualn_style_id' => $this->getSetting('visualn_style_id'),
      'drawer_config' => $this->getSetting('drawer_config'),
      'drawer_fields' => $this->getSetting('drawer_fields'),
    ];

    return $form;
    //return $form + parent::settingsForm($form, $form_state);
  }


  /**
   * Return drawer configuration form via ajax request at style change
   */
  public static function ajaxCallback(array $form, FormStateInterface $form_state, Request $request) {
    $triggering_element = $form_state->getTriggeringElement();
    $visualn_style_id = $form_state->getValue($form_state->getTriggeringElement()['#parents']);
    $triggering_element_parents = array_slice($triggering_element['#array_parents'], 0, -1);
    $element = NestedArray::getValue($form, $triggering_element_parents);

    return $element['drawer_container'];
  }


  // @todo: this should be static since may not work on field settings form (see fetcher field widget for example)
  //public static function processDrawerContainerSubform(array $element, FormStateInterface $form_state, $form) {
  public function processDrawerContainerSubform(array $element, FormStateInterface $form_state, $form) {
    $stored_configuration = $element['#stored_configuration'];
    $configuration = [
      'visualn_style_id' => $stored_configuration['visualn_style_id'],
      'drawer_config' => $stored_configuration['drawer_config'],
      'drawer_fields' => $stored_configuration['drawer_fields'],
    ];

    $element = VisualNFormsHelper::processDrawerContainerSubform($element, $form_state, $form, $configuration);

    return $element;
  }



  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    return parent::settingsSummary();
    $summary = [];
    // Implement settings summary.

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = parent::viewElements($items, $langcode);

    $visualn_style_id = $this->getSetting('visualn_style_id');
    if (empty($visualn_style_id)) {
      return $elements;
    }

    $image_items = $elements;

    // wrap elements into a div so that the initial image contents could be hidden by the formatter handler script
    $fuid = substr(\Drupal::service('uuid')->generate(), 0, 4);
    $image_items_wrapper_id = 'visualn-image-formatter-html-selector--' . $fuid;

    $image_items = [
      '#prefix' => '<div id="' . $image_items_wrapper_id . '">',
      '#suffix' => '</div>',
      '#attached' => [
        'library' => [
          'visualn_file/visualn-image-formatter-handler'
        ],
        'drupalSettings' => [
          'visualnFile' => ['imageFormatterItemsWrapperId' => [$fuid => $image_items_wrapper_id]],
        ],
      ],
    ] + $image_items;

    // keep original image items for fallback bahaviour in case of disabled javascript
    $elements = [
      '#image_items' => $image_items,
    ];


    $urls = [];

    // @see ImageFormatter::viewElements()
    // @todo: try to get urls list from $elements
    //$deltas = Element::children($elements);

    $files = $this->getEntitiesToView($items, $langcode);
    foreach ($files as $delta => $file) {
      $image_uri = $file->getFileUri();
      // @todo: see the note in ImageFormatter::viewElements() relating a bug
      //$url = Url::fromUri(file_create_url($image_uri));
      $url = file_create_url($image_uri);
      $urls[$delta] = $url;
    }

    // @todo: here $data is attached to the drupal settings by the adapter
    //    though a router could be also used instead of this with a generic resource adapter
    $data = [
      'urls' => $urls,
    ];
    $data = [];
    foreach ($urls as $url) {
      $data[] = ['url' => $url];
    }

    // @todo: prepare output type and output interface, attach manager build

    // @todo: prepare Resource object
    // @todo: maybe just create a resource provider plugin of a certain type that accepts
    //    data in json format as part of its configuration or even context
    $resource = [
      'output_type' => 'json_generic_attached',
      'output_interface' => [
        'data' => $data,
      ],
    ];


    $options = [
      'style_id' => $visualn_style_id,
      'drawer_config' => $this->getSetting('drawer_config'),
      'drawer_fields' => $this->getSetting('drawer_fields'),
      'adapter_settings' => [],
      'output_type' => $resource['output_type'],
    ];
    $options['adapter_settings']['data'] = $resource['output_interface']['data'];

    // Get drawing build
    $build = VisualN::makeBuild($options);

    // @todo: html_selector should be connected inside '.field__items' in order
    //    to be able to use quick edit feature


    // field template seems to ignore anything added to the $elements and renders only items (see field.html.twig)



    $elements['#theme'] = 'visualn_image_formatter';
    $elements = [
      '#visualn_drawing_build' => $build,
    ] + $elements;

    return $elements;
  }

}
