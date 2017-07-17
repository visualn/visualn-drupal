<?php

namespace Drupal\visualn_resource\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldDefinitionInterface;
//use Drupal\Component\Utility\Html;
//use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
//use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\link\Plugin\Field\FieldFormatter\LinkFormatter;
use Drupal\Core\Path\PathValidatorInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\visualn\Plugin\VisualNDrawerManager;
use Drupal\visualn\Plugin\VisualNManagerManager;
use Drupal\visualn\Plugin\VisualNResourceFormatManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\visualn\Plugin\VisualNFormatterSettingsTrait;

/**
 * Plugin implementation of the 'visualn_resource' formatter.
 *
 * @FieldFormatter(
 *   id = "visualn_resource",
 *   label = @Translation("VisualN resource"),
 *   field_types = {
 *     "visualn_resource"
 *   }
 * )
 */
class VisualNResourceFormatter extends  LinkFormatter implements ContainerFactoryPluginInterface {

  // @todo: move formatter settings methods code into a trait

  use VisualNFormatterSettingsTrait;

  /**
   * The image style entity storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $visualNStyleStorage;

  /**
   * The visualn drawer manager service.
   *
   * @var \Drupal\visualn\Plugin\VisualNDrawerManager
   */
  protected $visualNDrawerManager;

  /**
   * The visualn manager manager service.
   *
   * @var \Drupal\visualn\Plugin\VisualNManagerManager
   */
  protected $visualNManagerManager;

  /**
   * The visualn resource format manager service.
   *
   * @var \Drupal\visualn\Plugin\VisualNResourceFormatManager
   */
  protected $visualNResourceFormatManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
    $plugin_id,
    $plugin_definition,
    $configuration['field_definition'],
    $configuration['settings'],
    $configuration['label'],
    $configuration['view_mode'],
    $configuration['third_party_settings'],
    $container->get('path.validator'),
    $container->get('entity_type.manager')->getStorage('visualn_style'),
    $container->get('plugin.manager.visualn.drawer'),
    $container->get('plugin.manager.visualn.manager'),
    $container->get('plugin.manager.visualn.resource_format')
    );
  }

  /**
   * Constructs a VisualNResourceFormatter object.
   *
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the formatter is associated.
   * @param array $settings
   *   The formatter settings.
   * @param string $label
   *   The formatter label display setting.
   * @param string $view_mode
   *   The view mode.
   * @param array $third_party_settings
   *   Any third party settings settings.
   * @param \Drupal\visualn\Plugin\VisualNDrawerManager $visualn_drawer_manager
   *   The visualn drawer manager service.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, PathValidatorInterface $path_validator, EntityStorageInterface $visualn_style_storage, VisualNDrawerManager $visualn_drawer_manager, VisualNManagerManager $visualn_manager_manager, VisualNResourceFormatManager $visualn_resource_format_manager) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings, $path_validator);
    $this->visualNStyleStorage = $visualn_style_storage;
    $this->visualNDrawerManager = $visualn_drawer_manager;
    $this->visualNManagerManager = $visualn_manager_manager;
    $this->visualNResourceFormatManager = $visualn_resource_format_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'show_resource_link' => 0,
    ] + self::visualnDefaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = $this->visualnSettingsForm($form, $form_state);
    $form['show_resource_link'] = [
      '#type' => 'checkbox',
      '#title' => t('Show resource link'),
      '#default_value' => $this->getSetting('show_resource_link'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = $this->visualnSettingsSummary();
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    // @todo: this is almost a copy from visualn_file formatter
    $elements = $this->visualnViewElements($items, $langcode);
    if ($this->getSetting('show_resource_link') == 0) {
      foreach ($elements as $delta => $element) {
        $elements[$delta]['#type'] = '#markup';
      }
    }
    return $elements;
  }

  public function visualnViewElementsOptionsAll($elements, array $options) {
    $options['output_type'] = 'file_dsv';  // @todo: for each delta output_type can be different (e.g. csv, tsv, json, xml)
    return $options;
  }

  // @todo: define $item class type (and also in the VisualNFomratterSettingsTrait.php)
  public function visualnViewElementsOptionsEach($element, array $options, $item) {
    $visualn_data = !empty($item->visualn_data) ? unserialize($item->visualn_data) : [];
    if (!empty($visualn_data['resource_format'])) {
      $resource_format_plugin_id = $visualn_data['resource_format'];
      $options['output_type'] = $this->visualNResourceFormatManager->getDefinition($resource_format_plugin_id)['output'];
    }
    else {
      // @todo: By default use DSV Generic Resource Format
      // @todo: load resource format plugin and get resource form by plugin id
      // @todo: for each delta output_type can be different (e.g. csv, tsv, json, xml)
      $options['output_type'] = 'file_dsv';

      // @todo: this should be detected dynamically depending on reousrce type, headers, file extension
      $options['adapter_settings']['file_mimetype'] = 'text/tab-separated-values';
    }

    // see LinkFormatter::viewElements
    if (!empty($settings['url_only']) && !empty($settings['url_plain'])) {
      $url = $element['#plain_text'];
    }
    else {
      $url = $element['#url']->toString();
    }
    //$file = $element['#file'];
    //$url = $file->url();
    $options['adapter_settings']['file_url'] = $url;
    //$options['adapter_settings']['file_mimetype'] = $file->getMimeType();

    return $options;
  }

}
