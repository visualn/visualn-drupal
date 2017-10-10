<?php

namespace Drupal\visualn\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\visualn\Plugin\VisualNDrawerManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\visualn\Entity\VisualNStyleInterface;

/**
 * Class VisualNStyleForm.
 *
 * @package Drupal\visualn\Form
 */
class VisualNStyleForm extends EntityForm {


  /**
   * The visualn drawer manager service.
   *
   * @var \Drupal\visualn\Plugin\VisualNDrawerManager
   */
  protected $visualNDrawerManager;

  /**
   * Constructs an VisualNStyleEditForm object.
   *
   * @param \Drupal\visualn\Plugin\VisualNDrawerManager $visualn_drawer_manager
   *   The visualn drawer manager service.
   */
  public function __construct(VisualNDrawerManager $visualn_drawer_manager) {
    $this->visualNDrawerManager = $visualn_drawer_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
    $container->get('plugin.manager.visualn.drawer')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $visualn_style = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $visualn_style->label(),
      '#description' => $this->t("Label for the VisualN style."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $visualn_style->id(),
      '#machine_name' => [
        'exists' => '\Drupal\visualn\Entity\VisualNStyle::load',
      ],
      '#disabled' => !$visualn_style->isNew(),
    ];

    // Get not only base drawer plugins list (base drawers) but also drawer entities list (subdrawers)
    // and add a prefix to each item (BASE_DRAWER_PREFIX or SUBDRAWER_PREFIX) so that they could be distinguished while selecting

    // @todo: where is "- Select -" is added to the list (there is no in in $drawers_list)?
    $drawers_list = [];

    // Get drawer plugins list
    $definitions = $this->visualNDrawerManager->getDefinitions();
    foreach ($definitions as $definition) {
      $drawers_list[VisualNStyleInterface::BASE_DRAWER_PREFIX . "|" . $definition['id']] = $definition['label'];
    }
    // Get drawer entities list
    foreach (visualn_subdrawer_options(FALSE) as $id => $label) {
      $drawers_list[VisualNStyleInterface::SUB_DRAWER_PREFIX . "|" . $id] = $label;
    }

    $default_drawer = $visualn_style->isNew() ? "" : $visualn_style->getDrawerType() . "|" . $visualn_style->getDrawerId();
    $form['drawer_id'] = [
      '#type' => 'select',
      '#title' => $this->t('Drawer'),
      '#options' => $drawers_list,
      '#default_value' => $default_drawer,
      '#description' => $this->t("Drawer for the VisualN style."),
      '#ajax' => [
        'callback' => '::drawerConfigForm',
        'wrapper' => 'drawer-config-form-ajax',
      ],
      '#empty_value' => '',
      '#required' => TRUE,
    ];

    // Attach drawer configuration form
    $common_drawer_id_prefixed = !empty($form_state->getValues()) ? $form_state->getValue('drawer_id') : $default_drawer;

    $drawer_id_components = $this->explodeDrawerIdIntoComponents($common_drawer_id_prefixed);
    $drawer_type = $drawer_id_components['drawer_type'];
    $common_drawer_id = $drawer_id_components['drawer_id'];

    // @todo: potentially config values can override style values e.g. "label" (see "name" attribute, it should be
    //    contained inside a container)
    $form['drawer_config'] = [];
    if ($common_drawer_id) {
      // If drawer is a subdrawer get its base drawer plugin id and the drawer config for it provided by the subdrawer.
      // In this case drawer config is actually prepared by the subdrawer form the plugin config and subdrawers settings.
      // The mechanics of how it is done depends on each specific subdrawer.
      if ($drawer_type == VisualNStyleInterface::SUB_DRAWER_PREFIX) {
        $visualn_drawer_id = $common_drawer_id;
        $visualn_drawer = \Drupal::service('entity_type.manager')->getStorage('visualn_drawer')->load($visualn_drawer_id);

        $base_drawer_id = $visualn_drawer->getBaseDrawerId();
        $drawer_config = $visualn_drawer->getDrawerConfig();
      }
      else {
        $base_drawer_id = $common_drawer_id;
        $drawer_config = [];
      }
      $drawer_config = $this->entity->getDrawerConfig() + $drawer_config;
      $drawer_plugin = $this->visualNDrawerManager->createInstance($base_drawer_id, $drawer_config);

      // set new configuration. may be used by ajax calls from drawer forms
      $configuration = $form_state->getValues();
      $configuration = !empty($configuration) ? $configuration : [];
      $configuration = $drawer_config + $configuration;
      $drawer_plugin->setConfiguration($configuration);

      $form['drawer_config'] = $drawer_plugin->buildConfigurationForm($form['drawer_config'], $form_state);
    }

    $form['drawer_config'] += [
      '#prefix' => '<div id="drawer-config-form-ajax">',
      '#suffix' => '</div>',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   *
   * @todo: Add into an interface or add description
   * @todo: Rename method if needed
   */
  public function drawerConfigForm(array $form, FormStateInterface $form_state) {
    return !empty($form['drawer_config']) ? $form['drawer_config'] : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $visualn_style = $this->entity;
    $status = $visualn_style->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label VisualN style.', [
          '%label' => $visualn_style->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label VisualN style.', [
          '%label' => $visualn_style->label(),
        ]));
    }
    $form_state->setRedirectUrl($visualn_style->toUrl('collection'));
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $common_drawer_id_prefixed = $form_state->getValue('drawer_id');

    $drawer_id_components = $this->explodeDrawerIdIntoComponents($common_drawer_id_prefixed);
    // @todo: rename to drawer_type_prefix
    $drawer_type = $drawer_id_components['drawer_type'];
    $common_drawer_id = $drawer_id_components['drawer_id'];

    if ($drawer_type == VisualNStyleInterface::SUB_DRAWER_PREFIX) {
      $visualn_drawer_id = $common_drawer_id;
      $visualn_drawer = \Drupal::service('entity_type.manager')->getStorage('visualn_drawer')->load($visualn_drawer_id);

      $base_drawer_id = $visualn_drawer->getBaseDrawerId();
      //$drawer_config = $visualn_drawer->getDrawerConfig();
    }
    else {
      $base_drawer_id = $common_drawer_id;
      //$drawer_config = [];
    }

    $drawer_plugin = $this->visualNDrawerManager->createInstance($base_drawer_id, []);

    // @todo: here drawer_id and label can be misused if there is a key with the same name in drawer config form

    // Extract config values from drawer config form for saving in VisualNStyle config entity
    // and add drawer plugin id for the visualn style.
    $this->entity->set('drawer_id', $common_drawer_id);
    $this->entity->set('drawer_type', $drawer_type);
    $drawer_plugin->submitConfigurationForm($form, $form_state);
    $drawer_config_values = $form_state->getValues();
    $this->entity->set('drawer_config', $drawer_config_values);
  }

  /**
   * @todo: make static?
   */
  protected function explodeDrawerIdIntoComponents($drawer_id_prefixed) {
    $drawer_plugin_id_explode = explode('|', $drawer_id_prefixed);

    $drawer_type = array_shift($drawer_plugin_id_explode);
    $drawer_plugin_id = implode('|', $drawer_plugin_id_explode);

    return ['drawer_type' => $drawer_type, 'drawer_id' => $drawer_plugin_id];
  }

}
