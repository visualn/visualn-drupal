<?php

namespace Drupal\visualn_data_sources\Plugin;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Provides the VisualN Data Provider plugin manager.
 */
class VisualNDataProviderManager extends DefaultPluginManager {


  /**
   * Constructs a new VisualNDataProviderManager object.
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
    parent::__construct('Plugin/VisualN/DataProvider', $namespaces, $module_handler, 'Drupal\visualn_data_sources\Plugin\VisualNDataProviderInterface', 'Drupal\visualn_data_sources\Annotation\VisualNDataProvider');

    $this->alterInfo('visualn_data_provider_info');
    $this->setCacheBackend($cache_backend, 'visualn_data_provider_plugins');
  }

}
