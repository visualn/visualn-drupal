<?php

namespace Drupal\visualn_resource\Plugin\Field\FieldType;

//use Drupal\Component\Utility\Random;
//use Drupal\Core\Field\FieldDefinitionInterface;
//use Drupal\Core\Field\FieldItemBase;
//use Drupal\Core\Field\FieldStorageDefinitionInterface;
//use Drupal\Core\Form\FormStateInterface;
//use Drupal\Core\StringTranslation\TranslatableMarkup;
//use Drupal\Core\TypedData\DataDefinition;
use Drupal\link\Plugin\Field\FieldType\LinkItem;

/**
 * Plugin implementation of the 'visualn_resource' field type.
 *
 * @FieldType(
 *   id = "visualn_resource",
 *   label = @Translation("VisualN resource"),
 *   description = @Translation("Stores a URL string that points to a resource for visualization"),
 *   default_widget = "visualn_resource",
 *   default_formatter = "visualn_resource",
 *   constraints = {"LinkType" = {}, "LinkAccess" = {}, "LinkExternalProtocols" = {}, "LinkNotExistingInternal" = {}}
 * )
 */
class VisualNResourceItem extends LinkItem {

}
