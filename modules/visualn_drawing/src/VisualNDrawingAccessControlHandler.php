<?php

namespace Drupal\visualn_drawing;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Access controller for the VisualN Drawing entity.
 *
 * @see \Drupal\visualn_drawing\Entity\VisualNDrawing.
 */
class VisualNDrawingAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\visualn_drawing\Entity\VisualNDrawingInterface $entity */
    switch ($operation) {
      case 'view':
        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished visualn drawing entities');
        }
        return AccessResult::allowedIfHasPermission($account, 'view published visualn drawing entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit visualn drawing entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete visualn drawing entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkFieldAccess($operation, FieldDefinitionInterface $field_definition, AccountInterface $account, FieldItemListInterface $items = NULL) {
    // @todo: see NodeAccessControlHandler::checkFieldAccess()

    // Only users with the administer visualn drawing entities permission can edit administrative
    // fields.
    $administrative_fields = ['user_id', 'status'];
    //$administrative_fields = ['uid', 'status', 'created', 'promote', 'sticky'];
    if ($operation == 'edit' && in_array($field_definition->getName(), $administrative_fields, TRUE)) {
      return AccessResult::allowedIfHasPermission($account, 'administer visualn drawing entities');
    }

    // Users have access to the revision_log field either if they have
    // administrative permissions or if the new revision option is enabled.
    if ($operation == 'edit' && $field_definition->getName() == 'revision_log_message') {
      if ($account->hasPermission('administer visualn drawing entities')) {
        return AccessResult::allowed()->cachePerPermissions();
      }
      return AccessResult::allowedIf($items->getEntity()->type->entity->shouldCreateNewRevision())->cachePerPermissions();
      //return AccessResult::allowedIf($items->getEntity()->type->entity->isNewRevision())->cachePerPermissions();
    }

    // @todo: also add thumbnail field specific checks

    return parent::checkFieldAccess($operation, $field_definition, $account, $items);
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add visualn drawing entities');
  }

}
