<?php

namespace Drupal\visualn\Core;

use Drupal\visualn\Core\DrawerSkinBase;
use Drupal\visualn\Core\DrawerSkinWithJsInterface;
use Drupal\visualn\ResourceInterface;
use Drupal\visualn\ChainPluginJsTrait;

/**
 * Base class for VisualN Drawer Skin plugins using js.
 *
 * @see \Drupal\visualn\Core\DrawerSkinWithJsInterface
 *
 * @ingroup drawer_skin_plugins
 */
abstract class DrawerSkinWithJsBase extends DrawerSkinBase implements DrawerSkinWithJsInterface {

  use ChainPluginJsTrait;

  /**
   * @inheritdoc
   */
  public function prepareBuild(array &$build, $vuid, ResourceInterface $resource) {
    $drawer_skin_config =  $this->getConfiguration();
    $this->prepareJsConfig($drawer_skin_config);
    $suid = $this->getSkinUid();
    $build['#attached']['drupalSettings']['visualn']['drawings'][$vuid]['skins'][$suid]['config'] = $drawer_skin_config;

    // defaults to plugin id if not overriden in drawer skin plugin class
    $drawer_skin_js_id = $this->jsId();
    $build['#attached']['drupalSettings']['visualn']['drawings'][$vuid]['skins'][$suid]['skinId'] = $drawer_skin_js_id;
    // @todo: this setting is just for reference, review
    $build['#attached']['drupalSettings']['visualn']['handlerItems']['skins'][$drawer_skin_js_id][$suid] = $vuid;

    return $resource;
  }

}
