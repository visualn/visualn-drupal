<?php

namespace Drupal\visualn_data_sources\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\visualn_data_sources\Plugin\VisualNResourceProviderManager;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Class ResourceProviderController.
 */
class ResourceProviderController extends ControllerBase {

  /**
   * Drupal\visualn_data_sources\Plugin\VisualNResourceProviderManager definition.
   *
   * @var \Drupal\visualn_data_sources\Plugin\VisualNResourceProviderManager
   */
  protected $pluginManagerVisualnResourceProvider;

  /**
   * Constructs a new ResourceProviderController object.
   */
  public function __construct(VisualNResourceProviderManager $plugin_manager_visualn_resource_provider) {
    $this->pluginManagerVisualnResourceProvider = $plugin_manager_visualn_resource_provider;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.visualn.resource_provider')
    );
  }

  /**
   * Data.
   *
   * @return string
   *   Return Hello string.
   */
  public function data($data_type) {
    switch ($data_type) {
      case 'leaflet':
        $data = [];
        // @todo: if we want to send data as is, then there should be some adapter to transpose data
        //    or make existing adapter configurable
        //$data[] = ['title', 'lat', 'lon'];
        foreach (['first', 'second', 'third'] as $k => $title) {
          $data[] = [$title, 51.8 + mt_rand() / mt_getrandmax()*0.2 - 0.1, 104.8 + mt_rand() / mt_getrandmax()*0.2 - 0.1];
        }

        $ready_data = [];
        foreach ($data as $k => $val) {
          $ready_data[] = [
            'title' => $val[0],
            'lat' => $val[1],
            'lon' => $val[2],
          ];
        }
        $data = $ready_data;
        break;

      default:
        $data = [
          'abc' => 'cde',
          'fgh' => 'ijk',
        ];
    }
    return new JsonResponse($data);
  }

}
