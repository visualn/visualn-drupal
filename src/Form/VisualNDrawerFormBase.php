<?php

namespace Drupal\visualn\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformState;
use Drupal\visualn\Plugin\VisualNDrawerManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class VisualNDrawerFormBase.
 */
class VisualNDrawerFormBase extends EntityForm {

  /**
   * The visualn drawer manager service.
   *
   * @var \Drupal\visualn\Plugin\VisualNDrawerManager
   */
  protected $visualNDrawerManager;

  /**
   * Constructs an VisualNDrawerFormBase object
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

    // here original base drawer form is rendered (drawer wrappers are not used here, obvious)

    // do not mix this drawer and the drawer in drawer_plugin (which is for Base Drawer)
    $visualn_drawer = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $visualn_drawer->label(),
      '#description' => $this->t("Label for the VisualN Drawer."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $visualn_drawer->id(),
      '#machine_name' => [
        'exists' => '\Drupal\visualn\Entity\VisualNDrawer::load',
      ],
      '#disabled' => !$visualn_drawer->isNew(),
    ];

    // @todo: this (almost) a copy-paste from VisualNStyleForm
    // Get drawer plugins list
    $definitions = $this->visualNDrawerManager->getDefinitions();
    // @todo: is it really needed to include empty element here
    $drawers_list = [];
    //$drawers_list = ['' => $this->t('- Select -')];
    foreach ($definitions as $definition) {
      if ($definition['role'] == 'wrapper') {
        continue;
      }
      $drawers_list[$definition['id']] = $definition['label'];
    }
    $default_drawer = $visualn_drawer->isNew() ? '' : $visualn_drawer->getBaseDrawerId();
    $form['drawer_id'] = [
      '#type' => 'select',
      '#title' => $this->t('Base Drawer'),
      '#options' => $drawers_list,
      '#default_value' => $default_drawer,
      // @todo: check terminology (for user drawer). maybe derived drawers or smth else
      '#description' => $this->t("Base Drawer for the VisualN User Drawer."),
      '#ajax' => [
        'callback' => '::drawerConfigForm',
        'wrapper' => 'drawer-config-form-ajax',
      ],
      '#empty_value' => '',
      '#required' => TRUE,
    ];

    // Attach drawer configuration form
    $drawer_plugin_id = !empty($form_state->getValues()) ? $form_state->getValue('drawer_id') : $default_drawer;

    // @todo: potentially config values can override style values e.g. "label" (see "name" attribute, it should be
    //    contained inside a container)
    $form['drawer_config'] = [];
    if ($drawer_plugin_id) {
      $drawer_config = $visualn_drawer->getDrawerConfig();
      $drawer_plugin = $this->visualNDrawerManager->createInstance($drawer_plugin_id, $drawer_config);

      // set new configuration. may be used by ajax calls from drawer forms and also when submitting the form
      //    without ajax (when js is disabled) or when validation errors occur. see stored_drawer_config in other handlers
      $configuration = $form_state->getValues();
      $configuration = !empty($configuration) ? $configuration : [];
      // @todo: check order here. for ajax call configuration should (since changed values should go
      //    to buildConfigurationForm() to rebuild the form) override drawer_config
      $configuration = $drawer_config + $configuration;
      $drawer_plugin->setConfiguration($configuration);

      // @todo: do the same thing for VisualNStyle and VisualNSetup, also see submitConfigurationForm() in the submitForm()
      //    (pass subform and sub_form_state there to the function)
      //    also set the '#tree' key to TRUE (see below)
      $subform_state = SubformState::createForSubform($form['drawer_config'], $form, $form_state);
      $form['drawer_config'] = $drawer_plugin->buildConfigurationForm($form['drawer_config'], $subform_state);
    }

    $form['drawer_config'] += [
      '#tree' => TRUE,
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
    $visualn_drawer = $this->entity;
    $status = $visualn_drawer->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label VisualN Drawer.', [
          '%label' => $visualn_drawer->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label VisualN Drawer.', [
          '%label' => $visualn_drawer->label(),
        ]));
    }
    $form_state->setRedirectUrl($visualn_drawer->toUrl('edit-form'));
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $drawer_plugin_id = $form_state->getValue('drawer_id');
    // @todo: maybe use config (?)
    $drawer_plugin = $this->visualNDrawerManager->createInstance($drawer_plugin_id, []);

    // Extract config values from drawer config form for saving in VisualNStyle config entity
    // and add drawer plugin id for the visualn style.
    $this->entity->set('base_drawer_id', $drawer_plugin_id);

    $subform_state = SubformState::createForSubform($form['drawer_config'], $form, $form_state);
    $drawer_plugin->submitConfigurationForm($form['drawer_config'], $subform_state);
    $drawer_config_values = $form_state->getValue('drawer_config') ?: [];

    $this->entity->set('drawer_config', $drawer_config_values);
  }

}
