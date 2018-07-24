<?php

namespace Drupal\visualn\Helpers;

use Drupal\visualn\Resource;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\MapDataDefinition;

class VisualN {

  const RAW_RESOURCE_FORMAT_GROUP = 'default';


  // @todo: the method isn't used any more
/*
  public static function makeBuild($options) {
    $output_type = $options['output_type'];
    $raw_input = $options['raw_input'] ?: [];

    // VisualN files should use selected raw resource format directly (if any)
    // since those formats may belong to field widget group but not to be in "default" group
    // and thus perform different from fall-back (or default) logic to create resource from raw input.
    if (!empty($options['raw_resource_format_id'])) {

      $raw_resource_format_id = $options['raw_resource_format_id'];

      // load raw resource format plugin
      $raw_resource_format_plugin = \Drupal::service('plugin.manager.visualn.raw_resource_format')
        ->createInstance($raw_resource_format_id, []);
      // get resource object from raw_input
      $resource = $raw_resource_format_plugin->buildResource($raw_input);
    }
    else {
      $resource = static::getResourceByOptions($output_type, $raw_input);
    }

    $visualn_style_id = $options['style_id'];
    $drawer_config = $options['drawer_config'];
    $drawer_fields = $options['drawer_fields'];

    return static::makeBuildByResource($resource, $visualn_style_id, $drawer_config, $drawer_fields);
  }
*/

  /**
   * Standard entry point to create drawerings based on resource and configuration data.
   *
   * @todo: convert into a service, then services used could be injected as arguments via service tags
   */
  public static function makeBuildByResource($resource, $visualn_style_id, $drawer_config, $drawer_fields, $base_drawer_id = '') {
    $build = [];

    $visualNStyleStorage = \Drupal::service('entity_type.manager')->getStorage('visualn_style');
    $visualNDrawerManager = \Drupal::service('plugin.manager.visualn.drawer');
    $visualNManagerManager = \Drupal::service('plugin.manager.visualn.manager');

    // @todo: move manager id discovery for the drawer into DefaultManager::prepareBuild()?

    if (!empty($visualn_style_id)) {
      // load style and get drawer manager from plugin definition
      $visualn_style = $visualNStyleStorage->load($visualn_style_id);
      $drawer_plugin_id = $visualn_style->getDrawerPlugin()->getPluginId();
      $manager_plugin_id = $visualNDrawerManager->getDefinition($drawer_plugin_id)['manager'];
      // @todo: pass options as part of $manager_config (?)
    }
    elseif (!empty($base_drawer_id)) {
      $drawer_plugin_id = $base_drawer_id;
      $manager_plugin_id = $visualNDrawerManager->getDefinition($drawer_plugin_id)['manager'];
    }
    else {
      return $build;
    }


    // generate vuid for the drawing
    $vuid = \Drupal::service('uuid')->generate();

    // generate html selector for the drawing (where to attach drawing selector)
    $html_selector = 'visualn-drawing--' . substr($vuid, 0, 8);

    // @todo: attributes dont render if there is nothing to render
    //$build['#attributes']['class'][] = $html_selector;
    $build['visualn_build_markup'] = ['#markup' => '<div class="' . $html_selector . '"></div>'];

    // get manager configuration, load manager plugin and prepare drawing build
    $manager_config = [
      'visualn_style_id' => $visualn_style_id,
      'drawer_config' => $drawer_config,
      'drawer_fields' => $drawer_fields,
      'html_selector' => $html_selector,
      // @todo: this was introduced later, for drawer preview page
      'base_drawer_id' => $base_drawer_id,
    ];

    $manager_plugin = $visualNManagerManager->createInstance($manager_plugin_id, $manager_config);
    $manager_plugin->prepareBuild($build, $vuid, $resource);

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
  public static function getResourceByOptions($output_type, array $raw_input) {

    // @todo: review the code here
    //    maybe use a service for that, see TypedDataManager::create for example


    $raw_resource_format_plugin_id = '';
    $raw_resource_format_plugin = NULL;

    // Use Raw Resource Formats (from "default" group) to create resource objects (if implemented)
    // to allow extensible and arbitrary resource building logic implementations.
    $raw_resource_format_definitions = self::getRawResourceFormatsByGroup(self::RAW_RESOURCE_FORMAT_GROUP);
    foreach ($raw_resource_format_definitions as $raw_resource_format_definition) {
      if ($raw_resource_format_definition['output'] == $output_type) {
        $raw_resource_format_plugin_id = $raw_resource_format_definition['id'];
        break;
      }
    }

    if ($raw_resource_format_plugin_id) {
      // @todo: should some values  be passed as plugin configuration?
      //   e.g. delimiter for csv files from configuration form (when implemented)
      $raw_resource_format_plugin = \Drupal::service('plugin.manager.visualn.raw_resource_format')
        ->createInstance($raw_resource_format_plugin_id, []);

      $resource = $raw_resource_format_plugin->buildResource($raw_input);
    }
    else {
      // Fall-back behavior for resource types without 'default' raw resource format implemented
      $resource_plugin_id = $output_type;

      // @todo: implement VisualN Resources for other output types (not only generic_data_array)

      $visualNResourceManager = \Drupal::service('plugin.manager.visualn.resource');
      $plugin_definitions = $visualNResourceManager->getDefinitions();


      if (!isset($plugin_definitions[$resource_plugin_id])) {
        $resource_plugin_id = 'generic';
      }

      $resource_plugin_config = ['raw_input' => $raw_input];
      $resource = $visualNResourceManager->createInstance($resource_plugin_id, $resource_plugin_config);

      // @todo: see TypedDataManager::create
      $resource->setValue($raw_input);
      // @todo: decide how setValue() should be used

      // @todo: needed at least for 'generic' type
      $resource->setResourceType($output_type);
    }




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

  /**
   * Get RawResourceFormat plugins ids that belong to the given group.
   *
   * @todo: maybe make non-static and convert into a service
   * @todo: maybe allow multiple groups
   */
  public static function getRawResourceFormatsByGroup($group) {

    $definitions = \Drupal::service('plugin.manager.visualn.raw_resource_format')->getDefinitions();
    if (!empty($group)) {
      $definitions = array_filter($definitions, function($definition, $k) use($group) {
        return in_array($group, $definition['groups']);
      }, ARRAY_FILTER_USE_BOTH);
    }

    return $definitions;
  }

  /**
   * Get resource base type for the given type.
   *
   * @todo: Resource base types should be provided by Resource plugins annotaions
   *    when Resource plugins themselves are implemented.
   *    Also there may be alternative sources (e.g. yaml files) for resource types and
   *    their base types info if implemented as resource type synonyms. This would allow
   *    to compose alternative groups of arbitrary resources which may be required
   *    e.g. by adapters that support multiple resources grouped by some criteria.
   */
  public static function getResourceBaseType($resource_type) {
    $resources_base_types = [
      'remote_generic_csv' => 'remote_generic',
      'remote_generic_tsv' => 'remote_generic',
      'remote_generic_dsv' => 'remote_generic',
      'remote_generic_json' => 'remote_generic',
      'remote_xml_basic' => 'remote_xml',

      'local_file_xls' => 'local_file',
      'local_file_csv' => 'local_file',
    ];

    return isset($resources_base_types[$resource_type]) ? $resources_base_types[$resource_type] : '';
  }

}
