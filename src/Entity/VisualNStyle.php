<?php

namespace Drupal\visualn\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the VisualN style entity.
 *
 * @ConfigEntityType(
 *   id = "visualn_style",
 *   label = @Translation("VisualN style"),
 *   handlers = {
 *     "list_builder" = "Drupal\visualn\VisualNStyleListBuilder",
 *     "form" = {
 *       "add" = "Drupal\visualn\Form\VisualNStyleForm",
 *       "edit" = "Drupal\visualn\Form\VisualNStyleForm",
 *       "delete" = "Drupal\visualn\Form\VisualNStyleDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\visualn\VisualNStyleHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "visualn_style",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid",
 *     "drawer" = "drawer"
 *   },
 *   links = {
 *     "canonical" = "/admin/config/media/visualn-styles/manage/{visualn_style}",
 *     "add-form" = "/admin/config/media/visualn-styles/add",
 *     "edit-form" = "/admin/config/media/visualn-styles/manage/{visualn_style}/edit",
 *     "delete-form" = "/admin/config/media/visualn-styles/manage/{visualn_style}/delete",
 *     "collection" = "/admin/config/media/visualn-styles"
 *   }
 * )
 */
class VisualNStyle extends ConfigEntityBase implements VisualNStyleInterface {

  /**
   * The VisualN style ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The VisualN style label.
   *
   * @var string
   */
  protected $label;

  /**
   * The VisualN style drawer config.
   *
   * @var array
   */
  protected $drawer = [];

  /**
   * {@inheritdoc}
   */
  public function getDrawerId() {
    return $this->drawer['id'] ?: '';
  }

  /**
   * {@inheritdoc}
   */
  public function getDrawerConfig() {
    return $this->drawer;
  }

  /**
   * {@inheritdoc}
   */
  public function setDrawerConfig($drawer_config) {
    $this->drawer = $drawer_config;
    return $this;
  }

}
