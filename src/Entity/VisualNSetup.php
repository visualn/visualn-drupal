<?php

namespace Drupal\visualn\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the VisualN Setup entity.
 *
 * @ConfigEntityType(
 *   id = "visualn_setup",
 *   label = @Translation("VisualN Setup"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\visualn\VisualNSetupListBuilder",
 *     "form" = {
 *       "add" = "Drupal\visualn\Form\VisualNSetupForm",
 *       "edit" = "Drupal\visualn\Form\VisualNSetupForm",
 *       "delete" = "Drupal\visualn\Form\VisualNSetupDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\visualn\VisualNSetupHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "visualn_setup",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/config/media/visualn/setups/manage/{visualn_setup}",
 *     "add-form" = "/admin/config/media/visualn/setups/add",
 *     "edit-form" = "/admin/config/media/visualn/setups/manage/{visualn_setup}/edit",
 *     "delete-form" = "/admin/config/media/visualn/setups/manage/{visualn_setup}/delete",
 *     "collection" = "/admin/config/media/visualn/setups"
 *   }
 * )
 */
class VisualNSetup extends ConfigEntityBase implements VisualNSetupInterface {

  /**
   * The VisualN Setup ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The VisualN Setup label.
   *
   * @var string
   */
  protected $label;

}
