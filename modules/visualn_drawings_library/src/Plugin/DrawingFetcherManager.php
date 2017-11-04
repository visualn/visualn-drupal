<?php

namespace Drupal\visualn_drawings_library\Plugin;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Provides the Drawing Fetcher plugin manager.
 */
class DrawingFetcherManager extends DefaultPluginManager {


  /**
   * Constructs a new DrawingFetcherManager object.
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
    parent::__construct('Plugin/DrawingFetcher', $namespaces, $module_handler, 'Drupal\visualn_drawings_library\Plugin\DrawingFetcherInterface', 'Drupal\visualn_drawings_library\Annotation\DrawingFetcher');

    $this->alterInfo('visualn_drawing_fetcher_info');
    $this->setCacheBackend($cache_backend, 'visualn_drawing_fetcher_plugins');
  }

}
