<?php

namespace Drupal\visualn_resource\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
//use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\link\Plugin\Field\FieldWidget\LinkWidget;

/**
 * Plugin implementation of the 'visualn_resource' widget.
 *
 * @FieldWidget(
 *   id = "visualn_resource",
 *   label = @Translation("VisualN resource"),
 *   field_types = {
 *     "visualn_resource"
 *   }
 * )
 */
class VisualNResourceWidget extends LinkWidget {
  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);
    $item = $items[$delta];
    $visualn_data = !empty($item->visualn_data) ? unserialize($item->visualn_data) : [];
    $visualn_data['resource_format'] = !empty($visualn_data['resource_format']) ? $visualn_data['resource_format'] : '';

    $definitions = \Drupal::service('plugin.manager.visualn.resource_format')->getDefinitions();
    // @todo: there should be some default behaviour for the 'None' choice (actually, this refers to formatter)
    $resource_formats = ['' => $this->t('- None -')];
    foreach ($definitions as $definition) {
      $resource_formats[$definition['id']] = $definition['label'];
    }

    $element['resource_format'] = [
      '#type' => 'select',
      '#title' => $this->t('Resource format'),
      '#description' => $this->t('The format of the data source'),
      '#default_value' => $visualn_data['resource_format'],
      '#options' => $resource_formats,
      '#weight' => '2',
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    foreach ($values as &$value) {
      $value['uri'] = static::getUserEnteredStringAsUri($value['uri']);
      $visualn_data = [
        'resource_format' => $value['resource_format'],
      ];
      $value['visualn_data'] = serialize($visualn_data);
      $value += ['options' => []];
    }
    return $values;
  }

}
