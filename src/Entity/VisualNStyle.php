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
 *     "drawer_id" = "drawer_id",
 *     "drawer_type" = "drawer_type",
 *     "drawer_config" = "drawer_config"
 *   },
 *   links = {
 *     "canonical" = "/admin/config/media/visualn/styles/manage/{visualn_style}",
 *     "add-form" = "/admin/config/media/visualn/styles/add",
 *     "edit-form" = "/admin/config/media/visualn/styles/manage/{visualn_style}/edit",
 *     "delete-form" = "/admin/config/media/visualn/styles/manage/{visualn_style}/delete",
 *     "collection" = "/admin/config/media/visualn/styles"
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
   * The VisualN style drawer ID.
   *
   * @var string
   */
  protected $drawer_id;

  /**
   * The VisualN drawer type (base|subdrawer).
   *
   * @var string
   */
  protected $drawer_type;

  /**
   * The VisualN style drawer config.
   *
   * @var array
   */
  protected $drawer_config = [];

  /**
   * The VisualN style specific drawer plugin.
   *
   * @var \Drupal\visualn\Plugin\VisualNDrawerInterface
   */
  protected $drawer_plugin;

  /**
   * {@inheritdoc}
   */
  public function getDrawerId() {
    return $this->drawer_id ?: '';
  }

  /**
   * {@inheritdoc}
   */
  public function getDrawerPlugin() {

    if (!isset($this->drawer_plugin)) {
      $common_drawer_id = $this->getDrawerId();
      if (!empty($common_drawer_id)) {
        $drawer_type = $this->getDrawerType();
        if ($drawer_type == VisualNStyleInterface::SUB_DRAWER_PREFIX) {
          $visualn_drawer_id = $common_drawer_id;
          $visualn_drawer = \Drupal::service('entity_type.manager')->getStorage('visualn_drawer')->load($visualn_drawer_id);

          $base_drawer_id = $visualn_drawer->getBaseDrawerId();
          $drawer_config = $visualn_drawer->getDrawerConfig();
        }
        else {
          $base_drawer_id = $common_drawer_id;
          $drawer_config = [];
        }
        $drawer_config = $this->getDrawerConfig() + $drawer_config;
        // @todo: load manager at object instantiation
        $this->drawer_plugin = \Drupal::service('plugin.manager.visualn.drawer')->createInstance($base_drawer_id, $drawer_config);
      }
    }

    return $this->drawer_plugin;
  }

  /**
   * {@inheritdoc}
   */
  public function getDrawerType() {
    return $this->drawer_type;
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
    $this->drawer = $drawer_config;
    return $this;
  }

}
