<?php

namespace Drupal\visualn_drawings_library\Plugin\VisualN\DrawingFetcher;

use Drupal\visualn_drawings_library\Plugin\VisualNDrawingFetcherBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a 'VisualN File field drawing fetcher' VisualN drawing fetcher.
 *
 * @VisualNDrawingFetcher(
 *  id = "visualn_file_field",
 *  label = @Translation("VisualN File field drawing fetcher"),
 *  context = {
 *    "entity_type" = @ContextDefinition("string", label = @Translation("Entity type")),
 *    "bundle" = @ContextDefinition("string", label = @Translation("Bundle")),
 *    "current_entity" = @ContextDefinition("any", label = @Translation("Current entity"))
 *  }
 * )
 */
class FileFieldDrawingFetcher extends VisualNDrawingFetcherBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'visualn_file_field' => '',
    ] + parent::defaultConfiguration();

 }

  /**
   * {@inheritdoc}
   */
  public function fetchDrawing() {
    $drawing_markup = parent::fetchDrawing();

    $current_entity = $this->getContextValue('current_entity');

    $visualn_file_field = $this->configuration['visualn_file_field'];
    if (empty($visualn_file_field) || !$current_entity->hasField($visualn_file_field)) {
      return $drawing_markup;
    }

    $field_instance = $current_entity->get($visualn_file_field);
    if (!$field_instance->isEmpty()) {
      $first_delta = $field_instance->first();

      // @todo: this is based on VisualNFormatterSettingsTrait; review
      $visualn_style_id = $first_delta->visualn_style_id;
      $visualn_data = !empty($first_delta->visualn_data) ? unserialize($first_delta->visualn_data) : [];
      if ($visualn_style_id) {
        $drawer_config = !empty($visualn_data['drawer_config']) ? $visualn_data['drawer_config'] : [];
        $drawer_fields = !empty($visualn_data['drawer_fields']) ? $visualn_data['drawer_fields'] : [];

        $visualn_style = \Drupal::service('entity_type.manager')->getStorage('visualn_style')->load($visualn_style_id);
        $drawer_plugin = $visualn_style->getDrawerPlugin();
        $drawer_plugin_id = $drawer_plugin->getPluginId();
        $manager_plugin_id = \Drupal::service('plugin.manager.visualn.drawer')->getDefinition($drawer_plugin_id)['manager'];

        // @todo: check if config is needed
        $manager_config = [];
        $manager_plugin = \Drupal::service('plugin.manager.visualn.manager')->createInstance($manager_plugin_id, $manager_config);

        $options = [
          'style_id' => $visualn_style_id,
          'drawer_config' => $drawer_config,
          'drawer_fields' => $drawer_fields,
          'adapter_settings' => [],
        ];
        $options['output_type'] = 'file_dsv';
        $vuid = \Drupal::service('uuid')->generate();
        $html_selector = 'js-visualn-selector-drawing-fetcher--' . substr($vuid, 0, 8);
        $options['html_selector'] = $html_selector;
        // @todo: this is a bit hackish, see GenericFileFormatter::viewElements
        $file = $first_delta->entity;

        $url = $file->url();
        $options['adapter_settings']['file_url'] = $url;
        $options['adapter_settings']['file_mimetype'] = $file->getMimeType();
        if (!empty($visualn_data['resource_format'])) {
          $resource_format_plugin_id = $visualn_data['resource_format'];
          $options['output_type'] = \Drupal::service('plugin.manager.visualn.resource_format')->getDefinition($resource_format_plugin_id)['output'];
        }

        $drawing_markup = [];
        $drawing_markup['#suffix'] = "<div class='{$html_selector}'></div>";
        $manager_plugin->prepareBuild($drawing_markup, $vuid, $options);


        // @todo: this doesn't take into consideration formatter settings if visualn style is the same,
        //    see VisualNFormatterSettingsTrait
      }

      //dsm($first_delta->getSettings());

      // @todo: get drawer id and configuration and render the drawing (if overridden)
      //    else get settings from formatter (if not raw)

      //$drawing_markup = $this->get($drawing_fetcher_field)->first()->buildDrawing();
      //$drawing_markup = $this->get($drawing_fetcher_field)->get(0)->buildDrawing();
    }

    return $drawing_markup;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {

    $entity_type = $this->getContextValue('entity_type');
    $bundle = $this->getContextValue('bundle');

    // @todo: this is a temporary solution
    if (empty($entity_type) || empty($bundle)) {
      // @todo: throw an error
      $form['markup'] = [
        '#markup' => t('Entity type or bundle context not set.'),
      ];

      return $form;
    }


    // @todo: here we don't give any direct access to the entity edited
    //    but we can find out whether the entity field has multiple or unlimited delta

    // select file field and maybe delta
    // @todo: maybe select also delta

    $options = ['' => t('- Select -')];
    // @todo: instantiate on create
    $entityManager = \Drupal::service('entity_field.manager');
    $bundle_fields = $entityManager->getFieldDefinitions($entity_type, $bundle);

    foreach ($bundle_fields as $field_name => $field_definition) {
      // filter out base fields
      if ($field_definition->getFieldStorageDefinition()->isBaseField() == TRUE) {
        continue;
      }

      // @todo: move field type into constant
      if ($field_definition->getType() == 'visualn_file') {
        $options[$field_name] = $field_definition->getLabel();
      }
    }

    $form['visualn_file_field'] = [
      '#type' => 'select',
      '#title' => t('VisualN File field'),
      '#options' => $options,
      // @todo: where to use getConfiguration and where $this->configuration (?)
      //    the same question for other plugin types
      '#default_value' => $this->configuration['visualn_file_field'],
      '#description' => t('Select the VisualN File field for the drawing source'),
    ];

    return $form;
  }

}

