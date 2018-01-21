<?php

namespace Drupal\visualn_data_sources\Plugin\VisualN\ResourceProvider;

use Drupal\visualn_data_sources\Plugin\VisualNResourceProviderBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\NestedArray;
use Symfony\Component\HttpFoundation\Request;
//use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\visualn\Helpers\VisualNFormsHelper;
use Drupal\Core\Url;
use Drupal\visualn\Helpers\VisualN;

/**
 * Provides a 'VisualN Generated Resource Provider' VisualN resource provider.
 *
 * @VisualNResourceProvider(
 *  id = "visualn_generated_data",
 *  label = @Translation("VisualN Generated Resource Provider"),
 * )
 */
//class GeneratedResourceProvider extends VisualNResourceProviderBase implements ContainerFactoryPluginInterface {
class GeneratedResourceProvider extends VisualNResourceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'data_generator_id' => '',
      'data_generator_config' => [],
    ] + parent::defaultConfiguration();

 }

  // @todo: add to interface
  // @todo: maybe rename the method e.g. to attachResourceProviderData() or smth else
  public function prepareBuildByOptions(&$build, $vuid, $options) {
  }



  public function getResource() {
    $output_type = 'json_generic_attached';
    $data = [];
    if ($this->configuration['data_generator_id']) {
      $visualNDataGeneratorManager = \Drupal::service('plugin.manager.visualn.data_generator');
      $data_generator_id = $this->configuration['data_generator_id'];
      $data_generator_config = $this->configuration['data_generator_config'];
      $generator_plugin = $visualNDataGeneratorManager->createInstance($data_generator_id, $data_generator_config);
      $data = $generator_plugin->generateData();
    }

    $adapter_settings =  [
      'data' => $data,
    ];

    // @todo: load resource plugin
    $resource = VisualN::getResourceByOptions($output_type, $adapter_settings);

    return $resource;
  }



  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $visualNDataGeneratorManager = \Drupal::service('plugin.manager.visualn.data_generator');
    $definitions = $visualNDataGeneratorManager->getDefinitions();
    $data_generators = [];
    foreach ($definitions as $definition) {
      $data_generators[$definition['id']] = $definition['label'];
    }

    $ajax_wrapper_id = implode('-', array_merge($form['#array_parents'], ['data_generator_id'])) .'-ajax-wrapper';

    $form['data_generator_id'] = [
      '#type' => 'select',
      '#title' => t('Data Generator'),
      '#options' => $data_generators,
      '#default_value' => $this->configuration['data_generator_id'],
      '#required' => TRUE,
      '#empty_option' => t('- Select Data Generator -'),
      '#empty_value' => '',
      '#ajax' => [
        'callback' => [get_called_class(), 'ajaxCallbackDataGenerator'],
        'wrapper' => $ajax_wrapper_id,
      ],
    ];
    $form['generator_container'] = [
      '#prefix' => '<div id="' . $ajax_wrapper_id . '">',
      '#suffix' => '</div>',
      '#type' => 'container',
      '#process' => [[$this, 'processGeneratorContainerSubform']],
    ];
    $form['generator_container']['#stored_configuration'] = [
      'data_generator_id' => $this->configuration['data_generator_id'],
      'data_generator_config' => $this->configuration['data_generator_config'],
    ];

    return $form;
  }

  /**
   * Return data generator configuration form via ajax request at style change.
   * Should have a different name since ajaxCallback can be used by base class.
   */
  public static function ajaxCallbackDataGenerator(array $form, FormStateInterface $form_state, Request $request) {
    $triggering_element = $form_state->getTriggeringElement();
    $visualn_style_id = $form_state->getValue($form_state->getTriggeringElement()['#parents']);
    $triggering_element_parents = array_slice($triggering_element['#array_parents'], 0, -1);
    $element = NestedArray::getValue($form, $triggering_element_parents);

    return $element['generator_container'];
  }

  // @todo: this should be static since may not work on field settings form (see fetcher field widget for example)
  //public static function processGeneratorContainerSubform(array $element, FormStateInterface $form_state, $form) {
  public function processGeneratorContainerSubform(array $element, FormStateInterface $form_state, $form) {
    $stored_configuration = $element['#stored_configuration'];
    $configuration = [
      'data_generator_id' => $stored_configuration['data_generator_id'],
      'data_generator_config' => $stored_configuration['data_generator_config'],
    ];
    $element = VisualNFormsHelper::doProcessGeneratorContainerSubform($element, $form_state, $form, $configuration);
    return $element;
  }

}

