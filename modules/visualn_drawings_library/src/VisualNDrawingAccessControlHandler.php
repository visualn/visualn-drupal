<?php

namespace Drupal\visualn_drawings_library;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the VisualN Drawing entity.
 *
 * @see \Drupal\visualn_drawings_library\Entity\VisualNDrawing.
 */
class VisualNDrawingAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\visualn_drawings_library\Entity\VisualNDrawingInterface $entity */
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
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add visualn drawing entities');
  }

}
