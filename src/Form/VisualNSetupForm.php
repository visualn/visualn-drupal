<?php

namespace Drupal\visualn\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\NestedArray;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Form\SubformState;
use Drupal\visualn\Manager\SetupBakerManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Drupal\visualn\Helpers\VisualNFormsHelper;

/**
 * Class VisualNSetupForm.
 */
class VisualNSetupForm extends EntityForm {

  /**
   * The visualn setup baker manager service.
   *
   * @var \Drupal\visualn\Manager\SetupBakerManager
   */
  protected $visualNSetupBakerManager;

  /**
   * Constructs a VisualNSetupEditForm object.
   *
   * @param \Drupal\visualn\Manager\SetupBakerManager $visualn_setup_baker_manager
   *   The visualn setup baker manager service.
   */
  public function __construct(SetupBakerManager $visualn_setup_baker_manager) {
    $this->visualNSetupBakerManager = $visualn_setup_baker_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
    $container->get('plugin.manager.visualn.setup_baker')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $visualn_setup = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $visualn_setup->label(),
      '#description' => $this->t("Label for the VisualN Setup."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $visualn_setup->id(),
      '#machine_name' => [
        'exists' => '\Drupal\visualn\Entity\VisualNSetup::load',
      ],
      '#disabled' => !$visualn_setup->isNew(),
    ];

    // @todo: this is almost a copy-paste from VisualNStyleForm
    //   and VisualNDataSourceForm

    $bakers_list = [];

    // Get setup baker plugins list
    $definitions = $this->visualNSetupBakerManager->getDefinitions();
    foreach ($definitions as $definition) {
      $bakers_list[$definition['id']] = $definition['label'];
    }

    $ajax_wrapper_id = 'setup-baker-config-form-ajax';

    $default_baker = $visualn_setup->getBakerId();
    $form['setup_baker_id'] = [
      '#type' => 'select',
      '#title' => $this->t('Setup Baker'),
      '#options' => $bakers_list,
      '#default_value' => $default_baker,
      '#description' => $this->t("Baker for the drawer setup."),
      '#ajax' => [
        'callback' => '::ajaxCallbackSetupBaker',
        'wrapper' => $ajax_wrapper_id,
      ],
      '#empty_value' => '',
      '#required' => TRUE,
    ];

    $form['baker_container'] = [
      '#tree' => TRUE,
      '#prefix' => '<div id="' . $ajax_wrapper_id . '">',
      '#suffix' => '</div>',
      '#type' => 'container',
      '#process' => [[$this, 'processSetupBakerSubform']],
      '#weight' => 2,
    ];
    $stored_configuration = [
      'setup_baker_id' => $default_baker,
      'setup_baker_config' => $visualn_setup->getBakerConfig(),
    ];
    $form['baker_container']['#stored_configuration'] = $stored_configuration;

    // In processSetupBakerSubform() submit callback, configuration is stored in setup_baker_config,
    // so it wouldn't override "label" or "name" attributes values in case there are config values
    // with the same keys.

    return $form;
  }

  // @todo: this should be static since may not work on field settings form (see fetcher field widget for example)
  //public static function processSetupBakerSubform(array $element, FormStateInterface $form_state, $form) {
  public function processSetupBakerSubform(array $element, FormStateInterface $form_state, $form) {
    $configuration = [
      'setup_baker_id' => $element['#stored_configuration']['setup_baker_id'],
      'setup_baker_config' => $element['#stored_configuration']['setup_baker_config'],
    ];
    $element = VisualNFormsHelper::doProcessSetupBakerContainerSubform($element, $form_state, $form, $configuration);
    return $element;
  }

  /**
   * Return setup baker configuration form via ajax request at setup baker change.
   * Should have a different name since ajaxCallback can be used by base class.
   */
  public static function ajaxCallbackSetupBaker(array $form, FormStateInterface $form_state, Request $request) {
    $triggering_element = $form_state->getTriggeringElement();
    $visualn_style_id = $form_state->getValue($form_state->getTriggeringElement()['#parents']);
    $triggering_element_parents = array_slice($triggering_element['#array_parents'], 0, -1);
    $element = NestedArray::getValue($form, $triggering_element_parents);

    return $element['baker_container'];
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $visualn_setup = $this->entity;
    $status = $visualn_setup->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label VisualN Setup.', [
          '%label' => $visualn_setup->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label VisualN Setup.', [
          '%label' => $visualn_setup->label(),
        ]));
    }
    $form_state->setRedirectUrl($visualn_setup->toUrl('edit-form'));
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    // @todo: seems that there is no need in submitForm() here
  }

}
