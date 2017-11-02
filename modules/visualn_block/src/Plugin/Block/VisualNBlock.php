<?php

namespace Drupal\visualn_block\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\visualn\Plugin\VisualNDrawerManager;
use Drupal\visualn\Plugin\VisualNManagerManager;
use Drupal\visualn\Plugin\VisualNResourceFormatManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;

use Drupal\Core\Form\SubformStateInterface;
use Drupal\visualn_iframe\ShareLinkBuilder;

/**
 * Provides a 'VisualNBlock' block.
 *
 * @Block(
 *  id = "visualn_block",
 *  admin_label = @Translation("VisualN Block"),
 * )
 */
class VisualNBlock extends BlockBase implements ContainerFactoryPluginInterface {

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
   * @param \Drupal\visualn\Plugin\VisualNDrawerManager $visualn_drawer_manager
   *   The visualn drawer manager service.
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
         'enable_share_link' => 0,
         'iframe_hash' => '',
         'visualn_style_id' => '',
         'drawer_config' => [],
         'drawer_fields' => [],
        ] + parent::defaultConfiguration();

 }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    // @todo: validate the url
    $form['resource_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Resource Url'),
      '#description' => $this->t('Resource URL to use as data source for the drawing'),
      '#default_value' => $this->configuration['resource_url'],
      '#maxlength' => 256,
      '#size' => 64,
      '#weight' => '1',
      '#required' => TRUE,
    ];

    // check if visualn_iframe module is enabled
    $moduleHandler = \Drupal::service('module_handler');
    if ($moduleHandler->moduleExists('visualn_iframe')){
      // @todo: also maybe disable access to the iframe by url (or add some other access management mechanism)
      $form['enable_share_link'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Enable Share link'),
        '#default_value' => $this->configuration['enable_share_link'],
        '#weight' => '1',
      ];
    }
    // @todo: is this needed, i.e. maybe the value in $this->configuration is enough
    $form['iframe_hash'] = [
      '#type' => 'value',
      '#value' => $this->configuration['iframe_hash'],  // hash is set in blockSubmit()
    ];

    // Get resource formats plugins list
    $definitions = $this->visualNResourceFormatManager->getDefinitions();
    // @todo: there should be some default behaviour for the 'None' choice
    $resource_formats = ['' => $this->t('- None -')];
    foreach ($definitions as $definition) {
      $resource_formats[$definition['id']] = $definition['label'];
    }

    $form['resource_format'] = [
      '#type' => 'select',
      '#title' => $this->t('Resource format'),
      '#description' => $this->t('The format of the data source'),
      '#default_value' => $this->configuration['resource_format'],
      '#options' => $resource_formats,
      '#weight' => '2',
    ];

    //$visualn_styles = visualn_style_options(FALSE);
    $visualn_styles = visualn_style_options();
    $description_link = Link::fromTextAndUrl(
      $this->t('Configure VisualN Styles'),
      Url::fromRoute('entity.visualn_style.collection')
    );
    // @todo: choose a better selector name
    $ajax_wrapper_id = 'visualn-block-config-ajax-wrapper';
    // @todo: maybe rename to visualn_style (the same note for visualn_file)
    $form['visualn_style_id'] = [
      '#type' => 'select',
      '#title' => $this->t('VisualN style'),
      '#options' => $visualn_styles,
      '#default_value' => $this->configuration['visualn_style_id'] ?: '',
      '#description' => $this->t('Default style for the data to render.'),
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
      '#weight' => '10',
    ];
    $form['drawer_container'] = [
      '#prefix' => '<div id="' . $ajax_wrapper_id . '">',
      '#suffix' => '</div>',
      '#weight' => '11',
      '#type' => 'container',
    ];

    $drawer_config = [];

    // @todo: review this check after the main issue in drupal core is resolved
    // @see https://www.drupal.org/node/2798261
    if ($form_state instanceof SubformStateInterface) {
      $visualn_style_id = $form_state->getCompleteFormState()->getValue(['settings', 'visualn_style_id']);
      $drawer_config = $form_state->getCompleteFormState()->getValue(['settings', 'drawer_container', 'drawer_config']);

      $triggering_element = $form_state->getCompleteFormState()->getTriggeringElement();
    }
    else {
      $visualn_style_id = $form_state->getValue(['settings', 'visualn_style_id']);
      $drawer_config = $form_state->getValue(['settings', 'drawer_container', 'drawer_config']);

      $triggering_element = $form_state->getTriggeringElement();
    }
    // @todo: if $drawer_config not emtpy, extractConfigArrayValues() since config form (and thus form_state)
    //    may contain submit buttons which are not wanted here

    // If changed visualn style then don't use drawer_config from form_state because it belongs to the previous
    // visualn style. Otherwise it is supposed that triggered an ajax element inside drawer config form.
    // @todo: do the same thing for widgets and formatters forms
    if (!empty($triggering_element)) {
      $form_array_parents = $form['#array_parents'] ?: [];
      if ($triggering_element['#array_parents'] === array_merge($form_array_parents, ['settings', 'visualn_style_id'])) {
        $drawer_config = [];
      }
    }

    $drawer_config = $drawer_config ?: [];

    // When the form isn't submitted, form_state values is empty for it, thus values are NULL
    // but if changed to "None", submit is triggered and the value is set though an empty string.
    $visualn_style_id = isset($visualn_style_id) ? $visualn_style_id : $this->configuration['visualn_style_id'];
    // Attach drawer configuration form
    if($visualn_style_id) {
      $visualn_style = $this->visualNStyleStorage->load($visualn_style_id);
      $drawer_plugin = $visualn_style->getDrawerPlugin();
      $drawer_config = $drawer_config + $drawer_plugin->getConfiguration();
      // @todo:
      // @todo: why can it be empty (not even an empty array)?
      $stored_drawer_config = $this->configuration['drawer_config'] ?: [];
      $drawer_config = $stored_drawer_config + $drawer_config;
      $drawer_plugin->setConfiguration($drawer_config);

      // @todo:
      // set new configuration. may be used by ajax calls from drawer forms
      //$configuration = $form_state->getValue(array_merge($element['#parents'], ['drawer_container', 'drawer_config']));
      //$configuration = !empty($configuration) ? $configuration : [];
      //$configuration = $drawer_config + $configuration;
      //$drawer_plugin->setConfiguration($configuration);

      // @todo: pass Subform:createForSubform() instead of $form_state
      // @todo: add group type of fieldset with info about overriding style drawer config
      $form['drawer_container']['drawer_config'] = [];
      $form['drawer_container']['drawer_config'] = $drawer_plugin->buildConfigurationForm($form['drawer_container']['drawer_config'], $form_state);

      // @todo: trim values after submitting settings
      $data_keys = $drawer_plugin->dataKeys();
      // @todo: convert textfields into a table in a #process callback
      //    maybe even inside Mapper config form method
      if (!empty($data_keys)) {
        // @todo: get option setting
        $drawer_fields = $this->configuration['drawer_fields'];
        $form['drawer_container']['drawer_fields'] = [
          '#type' => 'table',
          '#header' => [t('Data key'), t('Field')],
        ];
        foreach ($data_keys as $i => $data_key) {
          $form['drawer_container']['drawer_fields'][$data_key]['label'] = [
            '#plain_text' => $data_key,
          ];
          $form['drawer_container']['drawer_fields'][$data_key]['field'] = [
            '#type' => 'textfield',
            '#default_value' => isset($drawer_fields[$data_key]) ? $drawer_fields[$data_key] : '',
          ];
        }
      }

      $form['drawer_container'] = [
        '#type' => 'details',
        '#title' => t('Style configuration'),
        '#open' => $form_state->getTriggeringElement(),
      ] + $form['drawer_container'];
    }
    return $form;
  }

  /**
   * Return drawerConfigForm via ajax at style change
   * @todo: Rename method if needed
   */
  public static function ajaxCallback(array $form, FormStateInterface $form_state, Request $request) {
    return $form['settings']['drawer_container'];
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['resource_url'] = $form_state->getValue('resource_url');
    $this->configuration['iframe_hash'] = $form_state->getValue('iframe_hash');
    $this->configuration['resource_format'] = $form_state->getValue('resource_format');
    $this->configuration['visualn_style_id'] = $form_state->getValue('visualn_style_id');
    $this->configuration['drawer_config'] = $form_state->getValue(['drawer_container', 'drawer_config']);
    $drawer_fields = $form_state->getValue(['drawer_container', 'drawer_fields']);
    foreach ($drawer_fields as $k => $drawer_field) {
      if (!trim($drawer_field['field'])) {
        unset($drawer_fields[$k]);
      }
      else {
        $drawer_fields[$k] = $drawer_field['field'];
      }
    }
    $this->configuration['drawer_fields'] = $drawer_fields;


    // @todo: move this block into a visualn_iframe function or a class
    // @todo: service would be preferable to using \Drupal (assuming it's an option in current context)
    $moduleHandler = \Drupal::service('module_handler');
    // check if visualn_iframe module is enabled
    if ($moduleHandler->moduleExists('visualn_iframe')){
      $this->configuration['enable_share_link'] = $form_state->getValue(['enable_share_link']);
      // @todo: maybe use a service
      $share_link_builder = new ShareLinkBuilder();
      // @todo: record should be craeted/changed first in the block Submit handler
      $hash = $this->configuration['iframe_hash'] ?: '';
      // the key should correspond to the key from the respective content provider service class
      // @todo: set key in the class property
      $hash = $share_link_builder->createIframeDbRecord('visualn_block_key', $this->configuration, $hash);
      // for the first time the form is submitted, the hash is empty
      if (empty($this->configuration['iframe_hash'])) {
        $this->configuration['iframe_hash'] = $hash;
      }
      // @todo: since we can use not only in page regions but also in panels etc.,
      //    we can't use block_id (also it is not accessible here) or other such info for
      //    the link generation
    }
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];

    $url = $this->configuration['resource_url'];
    $visualn_style_id = $this->configuration['visualn_style_id'];
    if (empty($visualn_style_id)) {
      return $build;
    }

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

    $build['visualn_block']['#markup'] = "<div class='{$html_selector}'></div>";

    // @todo: move this block into a visualn_iframe function or a class
    // @todo: service would be preferable to using \Drupal (assuming it's an option in current context)
    $moduleHandler = \Drupal::service('module_handler');
    // check if visualn_iframe module is enabled
    if ($moduleHandler->moduleExists('visualn_iframe') && $this->configuration['enable_share_link']){
      // @todo: deal with render cache here (e.g. if module was enabled, disabled and then re-enabled - link won't appear/disapper because of block cache)
      // @todo: maybe use a service
      $share_link_builder = new ShareLinkBuilder();
      // @todo: record should be craeted/changed first in the block Submit handler
      $iframe_url = $share_link_builder->getIframeUrl($this->configuration['iframe_hash']);
      // this key is used in IframeConentProvider::provideContent()
      $build['share_iframe_link'] = $share_link_builder->buildLink($iframe_url);
      // @todo: since we can use not only in page regions but also in panels etc.,
      //    we can't use block_id (also it is not accessible here) or other such info for
      //    the link generation
    }

    $options['html_selector'] = $html_selector;  // where to attach drawing selector

    // @todo: for different drawers there can be different managers
    $manager_plugin->prepareBuild($build, $vuid, $options);

    // @todo: attach drawer

    return $build;
  }

  // @todo: delete visualn iframe record on block delete

}
