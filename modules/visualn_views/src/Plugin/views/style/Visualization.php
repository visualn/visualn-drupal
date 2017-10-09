<?php

/**
 * @file
 * Definition of Drupal\visualn_views\Plugin\views\style\Visualization.
 */

namespace Drupal\visualn_views\Plugin\views\style;

use Drupal\core\form\FormStateInterface;
use Drupal\Core\Form\SubformState;
use Drupal\views\Plugin\views\style\StylePluginBase;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\visualn\Plugin\VisualNDrawerManager;
use Drupal\visualn\Plugin\VisualNManagerManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Style plugin to render listing.
 *
 * @ingroup views_style_plugins
 *
 * @ViewsStyle(
 *   id = "visualization",
 *   title = @Translation("VisualN"),
 *   help = @Translation("Render a listing of view data."),
 *   theme = "views_view_visualn",
 *   display_types = { "normal" }
 * )
 *
 */
class Visualization extends StylePluginBase {

  /**
   * Does the style plugin for itself support to add fields to it's output.
   *
   * @var bool
   */
  protected $usesFields = TRUE;

  /**
   * Specifies if the plugin uses row plugins.
   *
   * @todo: disable row plugin
   *
   * @var bool
   */
  protected $usesRowPlugin = TRUE;

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
   * The visualn unique identifier. Used for fields mapping and html_selector
   *   to distinguish from other drawings.
   *
   * @var string
   */
  protected $vuid;

  // @todo: add an option to filters form to override mappings

  // @todo: add a getVisualNOptions() to get style and drawer settings,
  //   by default returns $this->options array

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
      $container->get('plugin.manager.visualn.manager')
    );
  }

  /**
   * Constructs a Plugin object.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityStorageInterface $visualn_style_storage, VisualNDrawerManager $visualn_drawer_manager, VisualNManagerManager $visualn_manager_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->definition = $plugin_definition + $configuration;
    $this->visualNStyleStorage = $visualn_style_storage;
    $this->visualNDrawerManager = $visualn_drawer_manager;
    $this->visualNManagerManager = $visualn_manager_manager;
  }

  /**
   * Set default options
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['visualn_style'] = array('default' => '');
    $options['drawer_config'] = array('default' => []);
    $options['expose_keys_mapping'] = 0;

    return $options;
  }

  /**
   * Get display style options.
   *
   * By default this returns $this->options, but can be overriden
   *   e.g. by exposed keys mapping form.
   *
   * @todo: add to class interface
   */
  public function getVisualNOptions() {
    // @todo: check exposed mappings and override if any
    // @todo: rename option key
    if ($this->options['expose_keys_mapping']) {
      // @todo:
      //dsm($this->view->getExposedInput());
      $exposed_input = $this->view->getExposedInput();
      // @todo: the key should be unique. see visualn_form_alter()
      // do not confuse with 'drawer_fields' in buildOptionsForm()
      if (!empty($exposed_input['drawer_fields'])) {
        foreach ($exposed_input['drawer_fields'] as $key => $input_value) {
          // @todo: this one is actually form buildOptionsForm()
          $this->options['drawer_fields'][$key] = $input_value['field'];
        }
      }
    }
    //dsm($this->options);

    return $this->options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    //$visualn_styles = visualn_style_options(FALSE);
    $visualn_styles = visualn_style_options();
    $description_link = Link::fromTextAndUrl(
      $this->t('Configure VisualN Styles'),
      Url::fromRoute('entity.visualn_style.collection')
    );
    // @see ImageFormatter::settingsForm()
    // @todo: onChange execute an ajax callback to show mappings form for the drawer
    $form['visualn_style'] = array(
      '#type' => 'select',
      '#title' => $this->t('VisualN style'),
      '#description' => $this->t('Default style for the data to render.'),
      '#default_value' => $this->options['visualn_style'],
      '#options' => $visualn_styles,
      // @todo: add permission check for current user
      '#description' => $description_link->toRenderable() + [
        //'#access' => $this->currentUser->hasPermission('administer visualn styles')
        '#access' => TRUE
      ],
      '#ajax' => [
        'url' => views_ui_build_form_url($form_state),
      ],
      '#submit' => array(array($this, 'submitTemporaryForm')),
      //'#executes_submit_callback' => TRUE,
      '#required' => TRUE,
    );

    // Attach drawer configuration form
    $visualn_style_id = isset($form_state->getUserInput()['style_options']['visualn_style']) ? $form_state->getUserInput()['style_options']['visualn_style'] : $this->options['visualn_style'];
    if ($visualn_style_id) {
      $visualn_style = $this->visualNStyleStorage->load($visualn_style_id);
      $drawer_plugin = $visualn_style->getDrawerPlugin();
      $drawer_config = $drawer_plugin->getConfiguration();

      if ($visualn_style_id == $this->options['visualn_style']) {
        $drawer_config = $this->options['drawer_config'] + $drawer_config;
      }
      $drawer_plugin->setConfiguration($drawer_config);

      // @todo: add a checkbox to choose whether to override default drawer config or not
      //    or an option to reset to defaults
      // @todo: add group type of fieldset with info about overriding style drawer config
      $form['drawer_config'] = [];
      // @todo: check for #ajax key in the form tree and add 'url' key (or look for a better solution)
      $form['drawer_config'] = $drawer_plugin->buildConfigurationForm($form['drawer_config'], $form_state);

      $data_keys = $drawer_plugin->dataKeys();
      if (!empty($data_keys)) {
        $form['drawer_fields'] = [
          '#type' => 'table',
          '#header' => [$this->t('Data key'), $this->t('Field')],
        ];
        $field_names = $this->displayHandler->getFieldLabels();
        foreach ($data_keys as $data_key) {
          $form['drawer_fields'][$data_key]['label'] = [
            '#plain_text' => $data_key,
          ];
          $form['drawer_fields'][$data_key]['field'] = [
            '#type' => 'select',
            '#options' => $field_names,
            '#default_value' => isset($this->options['drawer_fields'][$data_key]) ? $this->options['drawer_fields'][$data_key] : '',
          ];
        }
      }
    }

    $form['expose_keys_mapping'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Expose fields mapping'),
      '#default_value' => $this->options['expose_keys_mapping'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function submitOptionsForm(&$form, FormStateInterface $form_state) {
    // restructure config values if needed
    // set options values from table fields (i.e. remove "field" key from options path to the value)
    $drawer_fields = $form_state->getValue(['style_options', 'drawer_fields']);
    foreach ($drawer_fields as $key => $drawer_field) {
      // @todo: setValue() doesn't need to return any value
      $drawer_fields = $form_state->setValue(['style_options', 'drawer_fields', $key], $drawer_field['field']);
    }

    $visualn_style_id  = $form_state->getValue(['style_options', 'visualn_style']);
    // @todo: add check if visual_style_id is selected
    $visualn_style = $this->visualNStyleStorage->load($visualn_style_id);
    $drawer_plugin = $visualn_style->getDrawerPlugin();

    // submit drawer config form values (allow plugin to extract and restructure form values)
    // we need to getValue(['style_options', 'drawer_config']) and to set it there in submitConfigurationForm()
    // @todo: check for a nicer way to get the full form for the subform if any

    $subform = $form['drawer_config'];
    //$full_form = ['style_options' => $form, '#parents' => []]; // @todo: this is a hack
    $full_form = ['subform' => $form, '#parents' => []]; // @todo: this is a hack
    $sub_form_state = SubformState::createForSubform($subform, $full_form, $form_state);
    $drawer_plugin->submitConfigurationForm($subform, $sub_form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function preRender($result) {
    parent::preRender($result);

    // generally this returns $this->options but can overridden
    $style_options = $this->getVisualNOptions();

    // @todo: since this can be cached it could not take style changes (i.e. made in style
    //   configuration interface) into consideration, so a cache tag may be needed.

    $visualn_style_id = $style_options['visualn_style'];
    if (empty($visualn_style_id)) {
      return;
    }
    // load style and get drawer manager from plugin definition
    $visualn_style = $this->visualNStyleStorage->load($visualn_style_id);
    $drawer_plugin_id = $visualn_style->getDrawerPlugin()->getPluginId();
    $manager_plugin_id = $this->visualNDrawerManager->getDefinition($drawer_plugin_id)['manager'];
    // @todo: pass options as part of $manager_config (?)
    $vuid = $this->getVuid();
    $options = [
      'style_id' => $visualn_style_id,
      // @todo: maybe move into 'drawer_settings'
      // @todo: compare with the same row in VisualNFormatterSettingsTrait::visualnViewElements()
      'drawer_config' => $style_options['drawer_config'],
      // @todo: maybe move into 'mapper_settings' (even though used in adapter)
      'drawer_fields' => $style_options['drawer_fields'],  // this setting should be used in adapter
      'output_type' => 'html_views',
    ];

    // add selector for the drawing
    $html_selector = 'js-visualn-selector-views-html--' . $this->view->id() . '--' . substr($vuid, 0, 8);
    $this->view->element['#attributes']['class'][] = $html_selector;
    $options['html_selector'] = $html_selector;  // where to attach drawing selector

    // @todo: check if config is needed
    $manager_config = [];
    $manager_plugin = $this->visualNManagerManager->createInstance($manager_plugin_id, $manager_config);
    // @todo: get mapping settings from style plugin object and pass to manager
    $manager_plugin->prepareBuild($this->view->element, $vuid, $options);
  }

  /**
   * Get Visualization vuid value.
   *
   * @todo: add into interface
   */
  public function getVuid() {
    if (empty($this->vuid)) {
      $this->vuid = \Drupal::service('uuid')->generate();
    }
    return $this->vuid;
  }

  // @todo: force using fields

}

