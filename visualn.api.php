<?php

/**
 * @file
 * Documentation landing page and topics.
 */

/**
 * @mainpage
 * Welcome to the VisualN API Documentation!
 *
 * This site is an API reference for VisualN, generated from comments embedded
 * in the source code.
 *
 * Here are some topics to help you get started developing with VisualN.
 *
 * @section essentials Essential background concepts
 *
 * - @link drawings Drawings @endlink
 *
 * @section workflow_mechanics Workflow mechanics
 *
 * - @link chain_plugins Chain plugins @endlink
 * - @link manager_plugins Managers @endlink
 * - @link drawer_plugins Drawers @endlink
 * - @link adapter_plugins Adapters @endlink
 * - @link mapper_plugins Mappers @endlink
 *
 * @section interface User interface
 *
 * - @link visualn_styles Visualization styles @endlink
 * - @link setup_baker_plugins Setup Bakers @endlink
 * - @link fetcher_plugins Fetchers @endlink
 * - @link visualn_fields Fields @endlink
 * - @link visualn_blocks Blocks @endlink
 * - @link visualn_views Views integration @endlink
 * - @link raw_resource_formats Resource format plugins @endlink
 *
 * @section data_sources Data sources
 *
 * - @link resource_plugins Resource plugins @endlink
 * - @link resource_proivder_plugins Resource providers @endlink
 * - @link data_generator_plugins Data generators @endlink
 * - @link data_set_entities Data Set entities @endlink
 *
 * @section embedding_drawings Embedding drawings
 *
 * - @link drawing_entities Drawing entities and Library of drawings @endlink
 * - @link visualn_tokens Drawing tokens @endlink
 * - @link visualn_iframes Drawing iframes @endlink
 *
 * @section subdrawers User-defined drawers (subdrawers)
 *
 * - @link subdrawer_entities Subdrawer entites @endlink
 * - @link drawer_modifiers Drawer modifiers @endlink
 *
 */

/**
 * @defgroup drawings Drawings
 * @{
 * Drawings types and overview
 *
 * A drawing is basically any piece of markup built around
 * and idea or purpose and representing a self-contained unit.
 *
 * As any html markup drawings can have scripts and styles attached.
 * Practically there is no limit of what a drawing may be: charts, image galleries,
 * embedded video, js apps, LaTeX images etc.
 * @}
 */

/**
 * @defgroup chain_plugins Workflows mechanics
 * @{
 * Some test title here
 *
 * Some test content here
 * @}
 */

/**
 * @defgroup manager_plugins Manager plugins
 * @{
 * Managers are used to compose chain of plugins and create drawing build.
 *
 * Manager plugins main purpose is to compose a chain from adapter, mapper and
 * drawer plugins and apply it to the input resource object to get a drawing
 * build as a result.
 * Developers can create custom managers that would implement custom logic
 * if DefaultManager doesn't fit their needs.
 * @}
 */

/**
 * @defgroup raw_resource_formats Raw Resource Format plugins
 * @{
 * Raw Resource Formats describe real physical resources used.
 *
 * Raw resources are the real "stuff" which is converted/translated into a resource object
 * to be used to build a drawing.
 * Due to arbitrary nature of possible physical resources no strict assumptions can be made
 * about their structure, origin or location that would be common
 * for all possible physical (real) resources.
 *
 * Though every real resource has some common features, namely a set of comprised/provided
 * values/parameters of some nature, expected type of resultant resource object and
 * the way (logic) to convert those input values/parameters into a resource object.
 * These features constitute real resource formats which are implemented
 * in form of Raw Resource Format plugins.
 *
 * The plugins are commonly used as an entry point into drawing building chain. They are
 * used by VisualN fields to let user explicitly select the format of file or url resource
 * or seamlessly when resource object is created.
 *
 * Raw Resource Format plugins have "group" key to let modules group formats by some criteria.
 * The "default" group tells that the format should be used to create a resource object
 * of a given type by default. See VisualN::getResourceByOptions() helper.
 *
 * Each format plugin must have "output" key set in its annotation. It defines the type
 * of resultant resource object produced by the plugin.
 * @}
 */

/**
 * @defgroup resource_proivder_plugins Resource providers
 * @{
 * Provide Resource objects objects to create drawings.
 *
 * Resource providers are typically used by Drawing Fetchers or Data Set entities
 * via Resource provider field type.
 * @}
 */

/**
 * Provide adapters subchain suggestions to be used by DefaultManager chain builder
 *
 * The 'adapters' items should correspond to the order adapters should be called in.
 *
 * @todo: this hook later may be changed or removed
 *
 * @param array $subchain_suggestions
 *   An of adapter subchains suggested by modules.
 *
 * @ingroup manager_plugins
 */
function hook_visualn_adapter_subchains_alter(&$subchain_suggestions) {

  // @todo: maybe use associative keys to uniquely identify a given suggestion
  $subchain_suggestions[] = [
    'adapters' => [
      'adapter_id_1',
      'adapter_id_2',
    ],
    'input' => 'custom_input_type',
    'output' => 'generic_data_array',
  ];
}

// @todo: add other similar hooks from other managers

/**
 * Alter ResourceFormat plugins definitions
 *
 * In particular, the hook is used to set/alter resource format 'groups' property
 *
 * @todo: this hook later may be changed or removed
 *
 * @todo: mention where the code is taken from (visualn.module)
 *
 * @todo: add a link to the RawResourceFormat manager class
 *
 * @ingroup raw_resource_formats
 */
function hook_visualn_raw_resource_format_info_alter(&$definitions) {

  // VisualN Resource field widget allows to select Raw Resource Format to be used
  // for input urls. It uses Raw Resource Format plugin annotation "groups" property
  // to filter only relative ones.
  $ids = ['visualn_json', 'visualn_csv', 'visualn_tsv', 'visualn_xml'];
  // @todo: maybe set group directly in plugins annotation
  foreach ($definitions as $k => $definition) {
    if (in_array($definition['id'], $ids)) {
      $definitions[$k]['groups'][] = 'visualn_resource_widget';
    }
  }
}
