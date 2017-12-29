<?php

namespace Drupal\visualn_drawings_library\Plugin\VisualN\DrawingFetcher;

use Drupal\visualn_drawings_library\Plugin\GenericDrawingFetcherBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\visualn\Helpers\VisualN;

/**
 * Provides a 'Image field drawing fetcher' VisualN drawing fetcher.
 *
 * @VisualNDrawingFetcher(
 *  id = "visualn_image_field",
 *  label = @Translation("Image field drawing fetcher"),
 *  context = {
 *    "entity_type" = @ContextDefinition("string", label = @Translation("Entity type")),
 *    "bundle" = @ContextDefinition("string", label = @Translation("Bundle")),
 *    "current_entity" = @ContextDefinition("any", label = @Translation("Current entity"))
 *  }
 * )
 */
class ImageFieldDrawingFetcher extends GenericDrawingFetcherBase {
  // @todo: use standalone fetcher as base to be able to change visualn style for
  //    image field, if not selected try to get drawer config from formatter settigns
  // @todo: add 'current view mode' context' for the case when user doesn't select a visualn style
  //    or add 'default view mode' select into configuration form (or just leave visualn style required)

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'image_field' => '',
    ] + parent::defaultConfiguration();
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
      if ($field_definition->getType() == 'image') {
        $options[$field_name] = $field_definition->getLabel();
      }
    }

    // @todo: rename key to image_field_name
    $form['image_field'] = [
      '#type' => 'select',
      '#title' => t('VisualN File field'),
      '#options' => $options,
      // @todo: where to use getConfiguration and where $this->configuration (?)
      //    the same question for other plugin types
      '#default_value' => $this->configuration['image_field'],
      '#description' => t('Select the VisualN File field for the drawing source'),
    ];

    // Attach visualn style select box for the fetcher
    $form += parent::buildConfigurationForm($form, $form_state);

    // Disable "required" behaviour for visualn style - if not selected,
    // try to get drawer config from formatter settings
    //$form['visualn_style_id']['#required'] = FALSE;

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function fetchDrawing() {
    // @todo: review the code here
    $drawing_markup = parent::fetchDrawing();

    $visualn_style_id = $this->configuration['visualn_style_id'];
    if (empty($visualn_style_id)) {
      return $drawing_markup;
    }


    $current_entity = $this->getContextValue('current_entity');

    $field_name = $this->configuration['image_field'];
    //if (empty($field_name) || !$current_entity->hasField($field_name)) {
    if (empty($field_name) || !$current_entity->hasField($field_name) || $current_entity->get($field_name)->isEmpty()) {
      return $drawing_markup;
    }

    $field_instance = $current_entity->get($field_name);

    // @todo: get image files list


    // @todo: pass options as part of $manager_config (?)
    $options = [
      'style_id' => $visualn_style_id,
      // @todo: unsupported operand types error
      //    add default value into defaultConfiguration()
      'drawer_config' => ($this->configuration['drawer_config'] ?: []),
      'drawer_fields' => $this->configuration['drawer_fields'],
      'adapter_settings' => [],
    ];

    // @todo:
    //$options = $this->getManagerOptions();


    $options['output_type'] = 'json_generic_attached';

    $urls = [];
    //foreach($field_instance->referencedEntities() as $delta => $image_file) {
    foreach($field_instance->referencedEntities() as $delta => $file) {
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


    $options['adapter_settings']['data'] = $data;



    // Get drawing build
    $build = VisualN::makeBuild($options);

    $drawing_markup = $build;


    // @todo: much of the code is taken from VisualNImageFormatter, check for further changes

    return $drawing_markup;
  }


  // @todo: move into a method into the GenericDrawingFetcherBase class
  protected function getManagerOptions() {
  }

}
