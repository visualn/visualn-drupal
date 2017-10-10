<?php

namespace Drupal\visualn\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the VisualN Drawer entity.
 *
 * @ConfigEntityType(
 *   id = "visualn_drawer",
 *   label = @Translation("VisualN Drawer"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\visualn\VisualNDrawerListBuilder",
 *     "form" = {
 *       "add" = "Drupal\visualn\Form\VisualNDrawerForm",
 *       "edit" = "Drupal\visualn\Form\VisualNDrawerForm",
 *       "delete" = "Drupal\visualn\Form\VisualNDrawerDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\visualn\VisualNDrawerHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "visualn_drawer",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid",
 *     "base_drawer_id" = "base_drawer_id",
 *     "drawer_config" = "drawer_config"
 *   },
 *   links = {
 *     "canonical" = "/admin/config/media/visualn/drawers/manage/{visualn_drawer}",
 *     "add-form" = "/admin/config/media/visualn/drawers/add",
 *     "edit-form" = "/admin/config/media/visualn/drawers/manage/{visualn_drawer}/edit",
 *     "delete-form" = "/admin/config/media/visualn/drawers/manage/{visualn_drawer}/delete",
 *     "collection" = "/admin/config/media/visualn/drawers"
 *   }
 * )
 */
class VisualNDrawer extends ConfigEntityBase implements VisualNDrawerInterface {

  /**
   * The VisualN Drawer ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The VisualN Drawer label.
   *
   * @var string
   */
  protected $label;

  /**
   * The VisualN Base Drawer plugin ID.
   *
   * @var string
   */
  protected $base_drawer_id;

  /**
   * The VisualN User Drawer Base Drawer config.
   *
   * @var array
   */
  protected $drawer_config = [];

  // @todo: add setDrawerId() method if needed

  /**
   * {@inheritdoc}
   */
  public function getBaseDrawerId() {
    return $this->base_drawer_id ?: '';
  }

  /**
   * {@inheritdoc}
   */
  public function getDrawerConfig() {
    return $this->drawer_config;
  }

  /**
   * {@inheritdoc}
   */
  public function setDrawerConfig($drawer_config) {
    $this->drawer_config = $drawer_config;
    return $this;
  }

}
