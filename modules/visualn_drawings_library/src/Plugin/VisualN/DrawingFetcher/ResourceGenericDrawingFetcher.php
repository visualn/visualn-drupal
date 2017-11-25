<?php

namespace Drupal\visualn_drawings_library\Plugin\VisualN\DrawingFetcher;

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
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Form\SubformStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\visualn\Helpers\VisualNFormsHelper;

/**
 * Provides a 'VisualN Resource generic drawing fetcher' VisualN drawing fetcher.
 *
 * @VisualNDrawingFetcher(
 *  id = "visualn_resource_generic",
 *  label = @Translation("VisualN Resource generic drawing fetcher"),
 *  needs_entity_info = FALSE,
 * )
 */
class ResourceGenericDrawingFetcher extends VisualNDrawingFetcherBase implements ContainerFactoryPluginInterface {

  // @todo: this is to avoid the error: "LogicException: The database connection is not serializable.
  // This probably means you are serializing an object that has an indirect reference to the database connection.
  // Adjust your code so that is not necessary. Alternatively, look at DependencySerializationTrait
  // as a temporary solution." when using from inside VisualNFetcherWidget
  use DependencySerializationTrait;

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
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->visualNStyleStorage = $visualn_style_storage;
    $this->visualNDrawerManager = $visualn_drawer_manager;
    $this->visualNManagerManager = $visualn_manager_manager;
    $this->visualNResourceFormatManager = $visualn_resource_format_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'resource_url' => '',
      'resource_format' => '',
      'visualn_style_id' => '',
      'drawer_config' => [],
      'drawer_fields' => [],
    ] + parent::defaultConfiguration();

 }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $visualn_style_id = $form_state->getValue('visualn_style_id');

    // @todo: how to check if the form is fresh
    // is null basically means that the form is fresh (maybe check the whole $form_state->getValues() to be sure?)
    // $visualn_style_id can be empty string (in case of default choice) or NULL in case of fresh form

    if (is_null($visualn_style_id)) {
      $visualn_style_id = $this->configuration['visualn_style_id'];
    }


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


    // Attach visualn style select
    $visualn_styles = visualn_style_options(FALSE);
    $description_link = Link::fromTextAndUrl(
      t('Configure VisualN Styles'),
      Url::fromRoute('entity.visualn_style.collection')
    );



    // @todo: so we can use #array_parents to create a unique wrapper or store it even in form_state->addBuildInfo()
    //    also keyed by #array_parents since there may be multiple fetcher plugins forms on a page (e.g. entity fields)
    //    or even store as a hidden element and get it from form_state->getValues()
    $ajax_wrapper_id = implode('-', array_merge($form['#array_parents'], ['visualn_style_id'])) .'-ajax-wrapper';


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
    $form['drawer_container']['#stored_configuration'] = $this->configuration;

    return $form;
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
  public function fetchDrawing() {
    // @todo: review the code here
    $drawing_markup = parent::fetchDrawing();

    $url = $this->configuration['resource_url'];
    $visualn_style_id = $this->configuration['visualn_style_id'];
    if (empty($visualn_style_id)) {
      return parent::fetchDrawing();
    }

    $build = [];

    // load style and get drawer manager from plugin definition
    $visualn_style = $this->visualNStyleStorage->load($visualn_style_id);
    $drawer_plugin = $visualn_style->getDrawerPlugin();
    $drawer_plugin_id = $drawer_plugin->getPluginId();
    $manager_plugin_id = $this->visualNDrawerManager->getDefinition($drawer_plugin_id)['manager'];

    // @todo: check if config is needed
    $manager_config = [];
    $manager_plugin = $this->visualNManagerManager->createInstance($manager_plugin_id, $manager_config);
    // @todo: pass options as part of $manager_config (?)
    $options = [
      'style_id' => $visualn_style_id,
      // @todo: unsupported operand types error
      // @todo: why can it be empty (not even an empty array)?
      //'drawer_config' =>  $this->configuration['drawer_config'] + $visualn_style->get('drawer'),
      'drawer_config' => ($this->configuration['drawer_config'] ?: []) + $drawer_plugin->getConfiguration(),
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

    // @todo: generate and set unique visualization (picture/canvas) id
    $vuid = \Drupal::service('uuid')->generate();
    // add selector for the drawing
    $html_selector = 'js-visualn-selector-block--' . substr($vuid, 0, 8);

    $build['#markup'] = "<div class='{$html_selector}'></div>";

    $options['html_selector'] = $html_selector;  // where to attach drawing selector

    // @todo: for different drawers there can be different managers
    $manager_plugin->prepareBuild($build, $vuid, $options);

    // @todo: attach drawer

    $drawing_markup = $build;

    return $drawing_markup;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    // @todo: extract and restructure data fields
    //    at the moment this is done on the validation level which is not correct,
    //    also it leaves an empty 'drawer_container' key in form_state->getValues()
    //    (though removes drawer_container_key)
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    // @todo: validate configuration form: resource_url
  }

}

