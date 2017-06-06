<?php

namespace Drupal\visualn\Plugin;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Provides the VisualN Manager plugin manager.
 */
class VisualNManagerManager extends DefaultPluginManager {


  /**
   * Constructor for VisualNManagerManager objects.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/VisualN/Manager', $namespaces, $module_handler, 'Drupal\visualn\Plugin\VisualNManagerInterface', 'Drupal\visualn\Annotation\VisualNManager');

    $this->alterInfo('visualn_manager_info');
    $this->setCacheBackend($cache_backend, 'visualn_manager_plugins');
  }

}
