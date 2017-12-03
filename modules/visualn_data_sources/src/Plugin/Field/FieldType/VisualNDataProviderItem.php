<?php

namespace Drupal\visualn_data_sources\Plugin\Field\FieldType;

use Drupal\Component\Utility\Random;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the 'visualn_data_provider' field type.
 *
 * @FieldType(
 *   id = "visualn_data_provider",
 *   label = @Translation("VisualN data provider"),
 *   description = @Translation("Stores info about VisualN Data Provider plugin configuration"),
 *   default_widget = "visualn_data_provider",
 *   default_formatter = "visualn_data_provider"
 * )
 */
class VisualNDataProviderItem extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultStorageSettings() {
    return [
      //'max_length' => 255,
      //'is_ascii' => FALSE,
      //'case_sensitive' => FALSE,
    ] + parent::defaultStorageSettings();
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    // Prevent early t() calls by using the TranslatableMarkup.
    $properties['data_provider_id'] = DataDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Data provider plugin'));
    // @todo: maybe there is a way to store config without serializing it
    // @todo: what is available length for the config?
    $properties['data_provider_config'] = DataDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Data provider config'));

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    $schema = [
      'columns' => [
      ],
    ];

    $schema['columns']['data_provider_id'] = [
      'description' => 'The ID of the data provider plugin used if overridden.',
      'type' => 'varchar_ascii',
      'length' => 255,
    ];
    // @todo: use data_provider_data if there should be not only data_provider (as it is done for visualn_data)
    $schema['columns']['data_provider_config'] = [
      'type' => 'text',
      'mysql_type' => 'blob',
      'description' => 'Serialized data provider config.',
    ];

    return $schema;
  }

  /**
   * {@inheritdoc}
   */
  /*public function getConstraints() {
    $constraints = parent::getConstraints();
    return $constraints;
  }*/


  /**
   * {@inheritdoc}
   */
  /*public function storageSettingsForm(array &$form, FormStateInterface $form_state, $has_data) {
    $elements = [];
    return $elements;
  }*/

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $value = $this->get('data_provider_id')->getValue();
    return $value === NULL || $value === '';
  }


  /**
   * {@inheritdoc}
   *
   * @todo: add to interface
   * @todo: maybe rename the method
   */
  // @todo: In a similar manner fetcher field for the drawing should return instantiated and configured
  //    fetcher plugin instead of drawing markup
  public function getDataProviderPlugin() {
    $data_provider_plugin = NULL;
    if (!$this->isEmpty()) {
      $data_provider_id = $this->get('data_provider_id')->getValue();
      $data_provider_config = $this->get('data_provider_config')->getValue();
      $data_provider_config = !empty($data_provider_config) ? unserialize($data_provider_config) : [];
      // @todo: instantiate at calss create
      $data_provider_plugin = \Drupal::service('plugin.manager.visualn.data_provider')
                          ->createInstance($data_provider_id, $data_provider_config);

      // Set reference to the entity since data provider plugin generally may need all entity fields.
      //$data_provider_plugin->setDataSetEntity($this->getEntity());
    }

    return $data_provider_plugin;
  }

}
