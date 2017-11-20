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

  //public static function processDrawerContainerSubform(array $element, FormStateInterface $form_state, $form) {
  public function processDrawerContainerSubform(array $element, FormStateInterface $form_state, $form) {
    // @todo: how to check if the form is fresh
    // is null basically means that the form is fresh (maybe check the whole $form_state->getValues() to be sure?)
    $style_element_parents = array_slice($element['#parents'], 0, -1);
    // since the function if called as a #process callback and the visualn_style_id select was already processed
    // and the values were mapped then it is enough to get form_state value for it and no need to check
    // configuration value (see FormBuilder::processForm() and FormBuilder::doBuildForm())
    // and no need in "is_null($visualn_style_id) then set value from config" check
    $visualn_style_id = $form_state->getValue(array_merge($style_element_parents, ['visualn_style_id']));
    // Here we intentioanlly leave fallback value blank (e.g. NULL) so that it could be checked and initialized
    // from visualn style drawer config later on.
    // @todo: Actually drawer_config shouldn't be taken from form_state here but instead in the
    //  drawer_plugin::buildConfigurationForm() itself,
    //  but this is not possible at the moment since drawers currently don't use
    //  $form_state to get actual values in buildConfigurationForm() but instead use initial
    //  configuration.
    // @todo: Also see comment for the validateDrawerContainerSubForm() callback method.
    $drawer_config = $form_state->getValue(array_merge($style_element_parents, ['drawer_config']));
    // Since drawer_fields is always an empty array for a drawer plugin, it is ok to set it to an empty array here.
    // In contrast, if null, drawer_config should be taken from the visualn style plugin configuraion.
    $drawer_fields = $form_state->getValue(array_merge($style_element_parents, ['drawer_fields']), []);

    // @todo: but here we should also check triggering element because if style is equal to the one in
    //    configuration doesn't necessarily mean that values for drawer_config and drawer_fields should
    //    be taken from the configuration since this may also happen at form submit with
    //    validation with errors in which case form_state values should be used for configs
    if (!is_null($visualn_style_id) && $visualn_style_id == $this->configuration['visualn_style_id']) {
      if (!empty($form_state->getTriggeringElement())) {
        $triggering_element = $form_state->getTriggeringElement();
        if ($triggering_element['#array_parents'] === array_merge($style_element_parents, ['visualn_style_id'])) {
          $drawer_config = $this->configuration['drawer_config'];
          $drawer_fields = $this->configuration['drawer_fields'];
        }
        else {
          // Basically this means to use visualn style stored configuration, since we load drawer plugin
          // not directly but by using $visualn_style->getDrawerPlugin() which applies visulan style stored
          // drawer configuration by default. Then it can be overridden by $drawer_plugin->setConfiguration(),
          // which is actually done below.

          // Leave drawer_config unset (actually null for now since using form_state->getValue() above)
          // for later initialization with drawer_plugin->getConfiguration() values
          // which are generally taken from visualn style configuration.
          //$drawer_config = NULL;

          // since drawer_fields is always an empty array for a drawer plugin, it is ok it
          // to set to an empty array here
          $drawer_fields = [];
        }
      }
      else {
        $drawer_config = $this->configuration['drawer_config'];
        $drawer_fields = $this->configuration['drawer_fields'];
      }
    }


    // @todo: add #element_validate

    // Attach drawer configuration form
    if($visualn_style_id) {
      // Here the drawer plugin is initialized (inside getDrawerPlugin()) with the config stored in the style.
      $visualn_style = $this->visualNStyleStorage->load($visualn_style_id);
      // @todo: maybe pass drawer_config as an argumert for VisualNStyle::getDrawerPlugin(),
      //    also we can't pass drawer_config directly at drawer_plugin instantiation because of user-defined drawers
      $drawer_plugin = $visualn_style->getDrawerPlugin();

      // The visualn style stored drawer configuration is generally only used for fresh form or when
      // switching vsiualn select box to a new style. In other cases drawer_config is provided by
      // the fetcher_config or form_state.
      if (isset($drawer_config) && !is_null($drawer_config)) {
        // The plugin configured according to the configuration stored for the fetcher or, if empty,
        // to the given visualn style configuration.
        $drawer_plugin->setConfiguration($drawer_config);
      }
      else {
        // Initialize drawer_config in case it is needed somewhere else below.
        $drawer_config = $drawer_plugin->getConfiguration();
      }


      // Use unique drawer container key for each visualn style from the select box so that the settings
      // wouldn't be overridden by the previous one on ajax calls (expecially when styles use the same
      // drawer and thus the same configuration form with the same keys).
      $drawer_container_key = $visualn_style_id;

      // get drawer configuration form

      $element[$drawer_container_key]['drawer_config'] = [];
      $element[$drawer_container_key]['drawer_config'] += [
        '#parents' => array_merge($element['#parents'], [$drawer_container_key, 'drawer_config']),
        '#array_parents' => array_merge($element['#array_parents'], [$drawer_container_key, 'drawer_config']),
      ];

      $subform_state = SubformState::createForSubform($element[$drawer_container_key]['drawer_config'], $form, $form_state);
      // attach drawer configuration form
      $element[$drawer_container_key]['drawer_config']
                = $drawer_plugin->buildConfigurationForm($element[$drawer_container_key]['drawer_config'], $subform_state);




      // @todo: Use some kind of \VisualN::buildDrawerDataKeysForm($drawer_plugin, $form, $form_state) here.

      // @todo: trim values after submitting settings
      $data_keys = $drawer_plugin->dataKeys();
      // @todo: convert textfields into a table in a #process callback
      //    maybe even inside Mapper config form method
      if (!empty($data_keys)) {
        // @todo: get option setting (legacy comment, what it means?)

        // @todo: get rid of value from 'field' or massage value at plugin submit
        //$drawer_fields = $this->configuration['drawer_fields'];
        $element[$drawer_container_key]['drawer_fields'] = [
          '#type' => 'table',
          '#header' => [t('Data key'), t('Field')],
        ];
        foreach ($data_keys as $i => $data_key) {
          $element[$drawer_container_key]['drawer_fields'][$data_key]['label'] = [
            '#plain_text' => $data_key,
          ];
          $element[$drawer_container_key]['drawer_fields'][$data_key]['field'] = [
            '#type' => 'textfield',
            '#default_value' => isset($drawer_fields[$data_key]) ? $drawer_fields[$data_key] : '',
          ];
        }
      }



      // since drawer and fields onfiguration forms may be empty, do a check (then it souldn't be of details type)
      if (Element::children($element[$drawer_container_key]['drawer_config'])
           || Element::children($element[$drawer_container_key]['drawer_fields'])) {
        $style_element_array_parents = array_slice($element['#array_parents'], 0, -1);
        // check that the triggering element is visualn_style_id but not fetcher_id select (or some other element) itself
        $triggering_element = $form_state->getTriggeringElement();
        $details_open = $triggering_element['#array_parents'] === array_merge($style_element_array_parents, ['visualn_style_id']);
        $element[$drawer_container_key] = [
          '#type' => 'details',
          '#title' => t('Style configuration'),
          '#open' => $details_open,
        ] + $element[$drawer_container_key];
      }

      // @todo: replace with #element_submit when introduced into core
      // extract values for drawer_container subform and drawer_config and drawer_fields
      //    remove drawer_container key from form_state values path
      //    also it can be done in ::submitConfigurationForm()
      $element[$drawer_container_key]['#element_validate'] = [[get_called_class(), 'validateDrawerContainerSubForm']];
    }
    return $element;
  }

  // @todo: when drawer config form_state values will be extracted on the drawer_plugin::buildConfigurationForm()
  //    level, this validation will make them empty (because of unset() function) so should be moved into submit
  //    callback by that moment.
  //public function validateDrawerContainerSubForm(&$form, FormStateInterface $form_state, $full_form) {
  // This need to be static or this will not when adding a block via blocks layout with the current fetcher selected.
  // @todo: the question is why exactly it doesn't work
  //    tried in '#element_validate' => [[$this, 'validateDrawerContainerSubForm']].
  // Here the function should be static because it it can be called after the form is built and cache
  // and thus $this will be empty whereas #process callbacks are always call at build phase. (?)
  public static function validateDrawerContainerSubForm(&$form, FormStateInterface $form_state) {
    // @todo: the code here should actually go to #element_submit, but it is not implemented at the moment in Drupal core

    // Here the full form_state (e.g. not SubformStateInterface) is supposed to be
    // since validation is done after the whole form is rendered.


    // get drawer_container_key (for selected visualn style is equal by convention to visualn_style_id,
    // see processDrawerContainerSubform() #process callback)
    $element_parents = $form['#parents'];
    // use $drawer_container_key for clarity though may get rid of array_pop() here and use end($element_parents)
    $drawer_container_key = array_pop($element_parents);

    // remove 'drawer_container' key
    $base_element_parents = array_slice($element_parents, 0, -1);

    // move drawer_config two levels up (remove 'drawer_container' and $drawer_container_key) in form_state values
    $drawer_config_values = $form_state->getValue(array_merge($element_parents, [$drawer_container_key, 'drawer_config']));
    if (!is_null($drawer_config_values)) {
      $form_state->setValue(array_merge($base_element_parents, ['drawer_config']), $drawer_config_values);
    }

    // move drawer_config two levels up (remove 'drawer_container' and $drawer_container_key) in form_state values
    $drawer_fields_values = $form_state->getValue(array_merge($element_parents, [$drawer_container_key, 'drawer_fields']));
    if (!is_null($drawer_fields_values)) {
      $new_drawer_fields_values = [];
      foreach ($drawer_fields_values as $drawer_field_key => $drawer_field) {
        $new_drawer_fields_values[$drawer_field_key] = $drawer_field['field'];
      }

      $form_state->setValue(array_merge($base_element_parents, ['drawer_fields']), $new_drawer_fields_values);
    }

    // remove remove 'drawer_container' key itself from form_state
    $form_state->unsetValue(array_merge($element_parents, [$drawer_container_key]));

    // @todo: and what about drawer_plugin::submitConfigurationForm() itself?
    //    see VisualNWidet::validateDrawerFieldsForm()
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

