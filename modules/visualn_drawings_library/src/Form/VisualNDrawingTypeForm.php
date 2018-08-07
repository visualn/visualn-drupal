<?php

namespace Drupal\visualn_drawings_library\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class VisualNDrawingTypeForm.
 */
class VisualNDrawingTypeForm extends EntityForm {

  const VISUALN_FETCHER_FIELD_TYPE_ID = 'visualn_fetcher';

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $visualn_drawing_type = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $visualn_drawing_type->label(),
      '#description' => $this->t("Label for the VisualN Drawing type."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $visualn_drawing_type->id(),
      '#machine_name' => [
        'exists' => '\Drupal\visualn_drawings_library\Entity\VisualNDrawingType::load',
      ],
      '#disabled' => !$visualn_drawing_type->isNew(),
    ];


    // get the list of visualn_fetcher fields attached to the entity type / bundle
    // also considered  base and bundle fields
    // see ContentEntityBase::bundleFieldDefinitions() and ::baseFieldDefinitions()
    $options = [];

    // @todo: instantiate on create
    $entityFieldManager = \Drupal::service('entity_field.manager');
    $entity_type = $visualn_drawing_type->getEntityType()->getBundleOf();

    // for new drawing type bundle is empty
    $bundle = $visualn_drawing_type->id();
    $bundle_fields = $entityFieldManager->getFieldDefinitions($entity_type, $bundle);

    // for new drawing types it may still contain base fields (e.g. "Default fetcher" field)
    // so do not skip them
    foreach ($bundle_fields as $field_name => $field_definition) {
      if ($field_definition->getType() == static::VISUALN_FETCHER_FIELD_TYPE_ID) {
        $options[$field_name] = $field_definition->getLabel();
      }
    }

    // sort options by name
    asort($options);

    // If entity type is new and visualn_fetcher base (or bundle) fields found (see Drawing entity class)
    // use the first field (generally there is one "Default fetcher" base field) as default.
    reset($options);
    $default_fetcher = $visualn_drawing_type->isNew() && !empty($options) ? key($options) : $this->entity->getDrawingFetcherField();
    $form['drawing_fetcher_field'] = [
      '#type' => 'select',
      '#title' => $this->t('Drawing fetcher field'),
      '#options' => $options,
      '#default_value' => $default_fetcher,
      '#description' => $this->t('The field that is used to provide drawing build.'),
      '#disabled' => $visualn_drawing_type->isNew() && empty($options),
      '#empty_value' => '',
      '#empty_option' => t('- Select drawing fetcher field -'),
      '#required' => !empty($options),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $visualn_drawing_type = $this->entity;
    $status = $visualn_drawing_type->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label VisualN Drawing type.', [
          '%label' => $visualn_drawing_type->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label VisualN Drawing type.', [
          '%label' => $visualn_drawing_type->label(),
        ]));
    }
    $form_state->setRedirectUrl($visualn_drawing_type->toUrl('collection'));
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $drawing_fetcher_field = $form_state->getValue('drawing_fetcher_field') ?: '';
    $this->entity->set('drawing_fetcher_field', $drawing_fetcher_field);
  }

}
