<?php

namespace Drupal\visualn_data_sources\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformState;
use Drupal\Component\Utility\NestedArray;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Render\Element;
use Drupal\visualn\Helpers\VisualNFormsHelper;

/**
 * Plugin implementation of the 'visualn_data_provider' widget.
 *
 * @FieldWidget(
 *   id = "visualn_data_provider",
 *   label = @Translation("VisualN data provider"),
 *   field_types = {
 *     "visualn_data_provider"
 *   }
 * )
 */
class VisualNDataProviderWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      //'data_provider_id' => '',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = [];

    // Actually default data provider is already set at field default value configuration level.
    /*$elements['data_provider_id'] = [
      '#type' => 'select',
      '#title' => t('Data provider plugin id'),
      '#options' => $options,
      '#description' => t('Default data provider plugin.'),
    ];*/

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];

    /*if (!empty($this->getSetting('data_provider_id'))) {
      // @todo: get label for the data provider plugin
      $summary[] = t('Data provider: @data_provider_plugin_label',
        ['@data_provider_plugin_label' => $this->getSetting('data_provider_id')]);
    }*/

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element['#type'] = 'fieldset';
    $item = $items[$delta];
    $data_provider_config = !empty($item->data_provider_config) ? unserialize($item->data_provider_config) : [];


    // Get data providers plugins list
    $definitions = \Drupal::service('plugin.manager.visualn.data_provider')->getDefinitions();
    $data_providers = [];
    foreach ($definitions as $definition) {
      $data_providers[$definition['id']] = $definition['label'];
    }

    // @todo: how to check if the form is fresh

    $field_name = $this->fieldDefinition->getName();
    $ajax_wrapper_id = $field_name . '-' . $delta . '-data_provider-config-ajax-wrapper';

    // select data provider plugin
    $element['data_provider_id'] = [
      '#type' => 'select',
      '#title' => t('Data provider plugin'),
      '#description' => t('The data provider for the drawing'),
      '#default_value' => $item->data_provider_id,
      '#options' => $data_providers,
      '#required' => TRUE,
      '#empty_value' => '',
      '#empty_option' => t('- Select data provider -'),
      '#ajax' => [
        'callback' => [get_called_class(), 'ajaxCallbackDataProvider'],
        'wrapper' => $ajax_wrapper_id,
      ],
    ];
    $element['provider_container'] = [
      '#prefix' => '<div id="' . $ajax_wrapper_id . '">',
      '#suffix' => '</div>',
      '#type' => 'container',
      //'#process' => [[get_called_class(), 'processProviderContainerSubform']],
      '#process' => [[$this, 'processProviderContainerSubform']],
    ];

    $stored_configuration = [
      'data_provider_id' => $item->data_provider_id,
      'data_provider_config' => $data_provider_config,
    ];
    $element['provider_container']['#stored_configuration'] = $stored_configuration;

    // @todo: Also set keys for #entity_type and #bundle (see fetcher widget). Maybe set as context.



    // @todo: Set entity type and bundle for the fetcher_plugin since it may need the list of all its fields.

    // @todo: We can't pass the current reference to the entity because it doesn't always exist,
    //    e.g. when setting default value for the field in field settings.
    // @todo: maybe pass entityType config entity
    $entity_type = $this->fieldDefinition->get('entity_type');
    $bundle = $this->fieldDefinition->get('bundle');

    // @todo: maybe we can get this data in the #process callback directly from the $item object
    $element['provider_container']['#entity_type'] = $entity_type;
    $element['provider_container']['#bundle'] = $bundle;


    return $element;
  }

  /**
   * Return data provider configuration form via ajax request at style change.
   * Should have a different name since ajaxCallback can be used by base class.
   */
  public static function ajaxCallbackDataProvider(array $form, FormStateInterface $form_state, Request $request) {
    $triggering_element = $form_state->getTriggeringElement();
    $visualn_style_id = $form_state->getValue($form_state->getTriggeringElement()['#parents']);
    $triggering_element_parents = array_slice($triggering_element['#array_parents'], 0, -1);
    $element = NestedArray::getValue($form, $triggering_element_parents);

    return $element['provider_container'];
  }


  // @todo: this is a copy-paste from DataProviderGenericDrawingFetcher class
  // @todo: this should be static since may not work on field settings form (see fetcher field widget for example)
  //public static function processDrawerContainerSubform(array $element, FormStateInterface $form_state, $form) {
  public function processProviderContainerSubform(array $element, FormStateInterface $form_state, $form) {
    // @todo: explicitly set #stored_configuration and other keys (#entity_type and #bundle) here
    $element = VisualNFormsHelper::doProcessProviderContainerSubform($element, $form_state, $form);
    return $element;
  }


  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    // serialize data_provider_config
    foreach ($values as &$value) {
      $data_provider_config = !empty($value['data_provider_config']) ? $value['data_provider_config'] : [];
      $value['data_provider_config'] = serialize($data_provider_config);
    }
    return $values;
  }

}
