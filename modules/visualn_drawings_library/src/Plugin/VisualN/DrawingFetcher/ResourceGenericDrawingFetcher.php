<?php

namespace Drupal\visualn_drawings_library\Plugin\VisualN\DrawingFetcher;

use Drupal\visualn_drawings_library\Plugin\GenericDrawingFetcherBase;
use Drupal\visualn_drawings_library\Plugin\VisualNDrawingFetcherBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformState;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\Component\Utility\NestedArray;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\visualn\Plugin\VisualNDrawerManager;
use Drupal\visualn\Plugin\VisualNManagerManager;
use Drupal\visualn\Plugin\VisualNResourceFormatManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
//use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Form\SubformStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\visualn\Helpers\VisualNFormsHelper;
use Drupal\visualn\Helpers\VisualN;

/**
 * Provides a 'VisualN Resource generic drawing fetcher' VisualN drawing fetcher.
 *
 * @VisualNDrawingFetcher(
 *  id = "visualn_resource_generic",
 *  label = @Translation("VisualN Resource generic drawing fetcher")
 * )
 */
//class ResourceGenericDrawingFetcher extends GenericDrawingFetcherBase implements ContainerFactoryPluginInterface {
class ResourceGenericDrawingFetcher extends GenericDrawingFetcherBase {

  // @todo: this is to avoid the error: "LogicException: The database connection is not serializable.
  // This probably means you are serializing an object that has an indirect reference to the database connection.
  // Adjust your code so that is not necessary. Alternatively, look at DependencySerializationTrait
  // as a temporary solution." when using from inside VisualNFetcherWidget
  //use DependencySerializationTrait;


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
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')->getStorage('visualn_style'),
      $container->get('plugin.manager.visualn.drawer'),
      $container->get('plugin.manager.visualn.manager'),
      $container->get('plugin.manager.visualn.resource_format')
    );
  }

  /**
   * Constructs a VisualNFormatter object.
   *
   * @param array $configuration
   *   The plugin configuration, i.e. an array with configuration values keyed
   *   by configuration option name. The special key 'context' may be used to
   *   initialize the defined contexts by setting it to an array of context
   *   values keyed by context names.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition
   * @param \Drupal\Core\Entity\EntityStorageInterface $visualn_style_storage
   *   The visualn style entity storage service.
   * @param \Drupal\visualn\Plugin\VisualNDrawerManager $visualn_drawer_manager
   *   The visualn drawer manager service.
   * @param \Drupal\visualn\Plugin\VisualNManagerManager $visualn_manager_manager
   *   The visualn manager manager service.
   * @param \Drupal\visualn\Plugin\VisualNResourceFormatManager $visualn_resource_format_manager
   *   The visualn resource format manager service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityStorageInterface $visualn_style_storage, VisualNDrawerManager $visualn_drawer_manager, VisualNManagerManager $visualn_manager_manager, VisualNResourceFormatManager $visualn_resource_format_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $visualn_style_storage, $visualn_drawer_manager, $visualn_manager_manager);

    $this->visualNResourceFormatManager = $visualn_resource_format_manager;
  }


  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'resource_url' => '',
      'resource_format' => '',
      // these settings are provided by GenericDrawingFetcherBase abstract class
      //'visualn_style_id' => '',
      //'drawer_config' => [],
      //'drawer_fields' => [],
    ] + parent::defaultConfiguration();

 }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {

    // @todo: validate the url
    $form['resource_url'] = [
      '#type' => 'textfield',
      '#title' => t('Resource Url'),
      '#description' => t('Resource URL to use as data source for the drawing'),
      '#default_value' => $this->configuration['resource_url'],
      '#maxlength' => 256,
      '#size' => 64,
      '#required' => TRUE,
    ];

    // Get resource formats plugins list for the resource formats select.
    $resource_formats = [];
    $definitions = $this->visualNResourceFormatManager->getDefinitions();
    foreach ($definitions as $definition) {
      $resource_formats[$definition['id']] = $definition['label'];
    }

    $form['resource_format'] = [
      '#type' => 'select',
      '#title' => t('Resource format'),
      '#description' => t('The format of the data source'),
      '#default_value' => $this->configuration['resource_format'],
      '#options' => $resource_formats,
      '#empty_value' => '',
      '#empty_option' => t('- Select resource format -'),
    ];

    // Attach visualn style select box for the fetcher
    $form += parent::buildConfigurationForm($form, $form_state);

    return $form;
  }


  /**
   * {@inheritdoc}
   */
  public function fetchDrawing() {
    // @todo: review the code here
    $drawing_markup = parent::fetchDrawing();

    $url = $this->configuration['resource_url'];
    $visualn_style_id = $this->configuration['visualn_style_id'];
    if (empty($visualn_style_id)) {
      return parent::fetchDrawing();
    }


    // @todo: pass options as part of $manager_config (?)
    $options = [
      'style_id' => $visualn_style_id,
      // @todo: unsupported operand types error
      //    add default value into defaultConfiguration()
      'drawer_config' => ($this->configuration['drawer_config'] ?: []),
      'drawer_fields' => $this->configuration['drawer_fields'],
      'adapter_settings' => [],
    ];

    if (!empty($this->configuration['resource_format'])) {
      $resource_format_plugin_id = $this->configuration['resource_format'];
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

    $options['adapter_settings']['file_url'] = $this->configuration['resource_url'];


    // Get drawing build
    $build = VisualN::makeBuild($options);

    $drawing_markup = $build;

    return $drawing_markup;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    // @todo: validate configuration form: resource_url
    parent::validateConfigurationForm($form, $form_state);
  }

}

