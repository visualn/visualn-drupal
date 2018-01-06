<?php

namespace Drupal\visualn\Helpers;

use Drupal\visualn\Resource;

class VisualN {

  // @todo: inject services as arguments
  public static function makeBuild($options) {
    $build = [];

    $visualNStyleStorage = \Drupal::service('entity_type.manager')->getStorage('visualn_style');
    $visualNDrawerManager = \Drupal::service('plugin.manager.visualn.drawer');
    $visualNManagerManager = \Drupal::service('plugin.manager.visualn.manager');

    $visualn_style_id = $options['style_id'];

    // load style and get drawer manager from plugin definition
    $visualn_style = $visualNStyleStorage->load($visualn_style_id);
    $drawer_plugin_id = $visualn_style->getDrawerPlugin()->getPluginId();
    $manager_plugin_id = $visualNDrawerManager->getDefinition($drawer_plugin_id)['manager'];
    // @todo: pass options as part of $manager_config (?)


    // generate vuid for the drawing
    $vuid = \Drupal::service('uuid')->generate();

    // generate html selector for the drawing
    $html_selector = 'visualn-drawing--' . substr($vuid, 0, 8);

    // @todo: attributes dont render if there is nothing to render
    //$build['#attributes']['class'][] = $html_selector;
    $build['visualn_build_markup'] = ['#markup' => '<div class="' . $html_selector . '"></div>'];
    $options['html_selector'] = $html_selector;  // where to attach drawing selector


    // @todo: check if config is needed
    $manager_config = [];
    $manager_plugin = $visualNManagerManager->createInstance($manager_plugin_id, $manager_config);
    // @todo: get mapping settings from style drawer plugin object and pass to manager

    // @todo: get Resource by options and Drawer data for manager input
    //    and for chain building
    $drawing_options = [
      'style_id' => $options['style_id'],
      'drawer_config' => $options['drawer_config'],
      'drawer_fields' => $options['drawer_fields'],
      'html_selector' => $options['html_selector'],
    ];

    $resource = static::getResourceByOptions($options);

    $manager_plugin->prepareBuild($build, $vuid, $resource, $drawing_options);


    return $build;
  }

  // @todo: temporary method
  public static function getResourceByOptions($options) {
    $output_type = $options['output_type'];
    $adapter_settings = $options['adapter_settings'] ?: [];
    switch ($output_type) {
      case 'asdf':
      default:
        $resource = new Resource();
        $resource->setResourceType($output_type);
        $resource->setResourceParams($adapter_settings);
        break;
    }


    return $resource;
  }

/*
  public static function makeBuildByResource($options) {
  }
*/

}
