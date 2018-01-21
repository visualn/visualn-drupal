<?php

namespace Drupal\visualn\Helpers;

use Drupal\visualn\Resource;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\MapDataDefinition;

class VisualN {

  // @todo: replace the method
  public static function makeBuild($options) {
    $output_type = $options['output_type'];
    $adapter_settings = $options['adapter_settings'] ?: [];

    $resource = static::getResourceByOptions($output_type, $adapter_settings);
    $visualn_style_id = $options['style_id'];
    $drawer_config = $options['drawer_config'];
    $drawer_fields = $options['drawer_fields'];

    return static::makeBuildByResource($resource, $visualn_style_id, $drawer_config, $drawer_fields);
  }

  /**
   * Standard entry point to create drawerings based on resource and configuration data.
   *
   * @todo: convert into a service, then services used could be injected as arguments via service tags
   */
  public static function makeBuildByResource($resource, $visualn_style_id, $drawer_config, $drawer_fields) {
    $build = [];

    $visualNStyleStorage = \Drupal::service('entity_type.manager')->getStorage('visualn_style');
    $visualNDrawerManager = \Drupal::service('plugin.manager.visualn.drawer');
    $visualNManagerManager = \Drupal::service('plugin.manager.visualn.manager');

    // load style and get drawer manager from plugin definition
    $visualn_style = $visualNStyleStorage->load($visualn_style_id);
    $drawer_plugin_id = $visualn_style->getDrawerPlugin()->getPluginId();
    $manager_plugin_id = $visualNDrawerManager->getDefinition($drawer_plugin_id)['manager'];
    // @todo: pass options as part of $manager_config (?)


    // generate vuid for the drawing
    $vuid = \Drupal::service('uuid')->generate();

    // generate html selector for the drawing (where to attach drawing selector)
    $html_selector = 'visualn-drawing--' . substr($vuid, 0, 8);

    // @todo: attributes dont render if there is nothing to render
    //$build['#attributes']['class'][] = $html_selector;
    $build['visualn_build_markup'] = ['#markup' => '<div class="' . $html_selector . '"></div>'];


    // @todo: check if config is needed
    $manager_config = [];
    $manager_plugin = $visualNManagerManager->createInstance($manager_plugin_id, $manager_config);
    // @todo: get mapping settings from style drawer plugin object and pass to manager

    // @todo: get Resource by options and Drawer data for manager input
    //    and for chain building
    $drawing_options = [
      'style_id' => $visualn_style_id,
      'drawer_config' => $drawer_config,
      'drawer_fields' => $drawer_fields,
      'html_selector' => $html_selector,
    ];


    $manager_plugin->prepareBuild($build, $vuid, $resource, $drawing_options);

    return $build;
  }

  /**
   * This is a temporary method by now.
   *
   * Questions to consider
   * - what if some auxiliary functionality, e.g. access and authorization mechanics is required,
   *   should such data be stored in Resource or it is Adapter responsibility (should it be a
   *   different type of resource if it requires authorization)?
   * - how is it connected with ResourceFormat plugins? also how the case is processed when an
   *   external server suddenly enables authorization?
   */
  public static function getResourceByOptions($output_type, $adapter_settings) {

    // @todo: review the code here
    //    maybe use a service for that, see TypedDataManager::create for example

    $resource_plugin_id = $output_type;

    // @todo: implement VisualN Resources for other output types (not only json_generic_attached)

    $visualNResourceManager = \Drupal::service('plugin.manager.visualn.resource');
    $plugin_definitions = $visualNResourceManager->getDefinitions();


    if (!isset($plugin_definitions[$resource_plugin_id])) {
      $resource_plugin_id = 'generic';
    }

    $resource_plugin_config = ['adapter_settings' => $adapter_settings];
    $resource = $visualNResourceManager->createInstance($resource_plugin_id, $resource_plugin_config);

    // @todo: see TypedDataManager::create
    $resource->setValue($adapter_settings);
    // @todo: decide how setValue() should be used

    // @todo: needed at least for 'generic' type
    $resource->setResourceType($output_type);


    // @todo: validate resource to show the use of implementing Resource as Typed Data
    // @todo: maybe move validation to makeBuildByResource() method
    // @todo: process validation errors
    // @todo: see Resource::propertyDefinitions() comment on using DataDefinition::create()
    //    what if some resource plugins uses DataDefinition::create()

    // @see FieldItemList::defaultValuesFormValidate() for example
    $violations = $resource->validate();

    // Report errors if any.
    if (count($violations)) {
      // @todo: set error messages
    }

    return $resource;
  }

}
