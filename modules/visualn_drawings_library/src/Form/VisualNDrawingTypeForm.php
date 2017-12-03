<?php

namespace Drupal\visualn_drawings_library\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class VisualNDrawingTypeForm.
 */
class VisualNDrawingTypeForm extends EntityForm {

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

    $options = [];
    if (!$visualn_drawing_type->isNew()) {
      // @todo: instantiate on create
      $entityManager = \Drupal::service('entity_field.manager');
      // @todo: get type from entity properties
      $entity_type = 'visualn_drawing';
      $bundle = $visualn_drawing_type->id();
      $bundle_fields = $entityManager->getFieldDefinitions($entity_type, $bundle);

      foreach ($bundle_fields as $field_name => $field_definition) {
        // filter out base fields
        if ($field_definition->getFieldStorageDefinition()->isBaseField() == TRUE) {
          continue;
        }

        // @todo: move field type into constant
        if ($field_definition->getType() == 'visualn_fetcher') {
          $options[$field_name] = $field_definition->getLabel();
        }
      }
    }


    $form['drawing_fetcher_field'] = [
      '#type' => 'select',
      '#title' => $this->t('Drawing fetcher field'),
      '#options' => $options,
      '#default_value' => $this->entity->getDrawingFetcherField(),
      '#disabled' => $visualn_drawing_type->isNew(),
      '#empty_value' => '',
      '#empty_option' => t('- Select drawing fetcher field -'),
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
