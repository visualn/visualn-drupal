<?php

namespace Drupal\visualn_data_sources\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class VisualNDataSetTypeForm.
 */
class VisualNDataSetTypeForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $visualn_data_set_type = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $visualn_data_set_type->label(),
      '#description' => $this->t("Label for the VisualN Data Set type."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $visualn_data_set_type->id(),
      '#machine_name' => [
        'exists' => '\Drupal\visualn_data_sources\Entity\VisualNDataSetType::load',
      ],
      '#disabled' => !$visualn_data_set_type->isNew(),
    ];

    $options = [];
    if (!$visualn_data_set_type->isNew()) {
      // @todo: instantiate on create
      $entityManager = \Drupal::service('entity_field.manager');
      // @todo: get type from entity properties
      $entity_type = 'visualn_data_set';
      $bundle = $visualn_data_set_type->id();
      $bundle_fields = $entityManager->getFieldDefinitions($entity_type, $bundle);

      foreach ($bundle_fields as $field_name => $field_definition) {
        // filter out base fields
        if ($field_definition->getFieldStorageDefinition()->isBaseField() == TRUE) {
          continue;
        }

        // @todo: move field type into constant
        if ($field_definition->getType() == 'visualn_resource_provider') {
          $options[$field_name] = $field_definition->getLabel();
        }
      }
    }


    $form['resource_provider_field'] = [
      '#type' => 'select',
      '#title' => $this->t('Resource provider field'),
      '#options' => $options,
      '#default_value' => $this->entity->getResourceProviderField(),
      '#disabled' => $visualn_data_set_type->isNew(),
      '#empty_value' => '',
      '#empty_option' => t('- Select resource provider field -'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $visualn_data_set_type = $this->entity;
    $status = $visualn_data_set_type->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label VisualN Data Set type.', [
          '%label' => $visualn_data_set_type->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label VisualN Data Set type.', [
          '%label' => $visualn_data_set_type->label(),
        ]));
    }
    $form_state->setRedirectUrl($visualn_data_set_type->toUrl('collection'));
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $resource_provider_field = $form_state->getValue('resource_provider_field') ?: '';
    $this->entity->set('resource_provider_field', $resource_provider_field);
  }

}
