<?php

namespace Drupal\visualn\Core;

use Drupal\visualn\Core\DrawerSkinInterface;
use Drupal\visualn\Core\VisualNPluginJsInterface;

/**
 * Interface for VisualN Drawer Skin plugins using js.
 *
 * @see \Drupal\visualn\Core\DrawerSkinWithJsBase
 *
 * @ingroup drawer_skin_plugins
 */
interface DrawerSkinWithJsInterface extends DrawerSkinInterface, VisualNPluginJsInterface {

}
