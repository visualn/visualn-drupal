<?php

namespace Drupal\visualn\Plugin\VisualN\Resource;

use Drupal\visualn\Plugin\VisualNResourceBase;

// @todo: change "output" annotation value - resource returns just an array of data, the adapter itself attaches it
//    as drupalSettings and sends to the client side as JSON data
//    see default output type for the plugin annotation

/**
 * Provides an 'Attached Data Resource' VisualN resource.
 *
 * @VisualNResource(
 *  id = "json_generic_attached",
 *  label = @Translation("Attached Data Resource"),
 *  output = "json_generic_attached",
 * )
 */
class AttachedDataResource extends VisualNResourceBase {
  // @todo: output key seems to be not needed here in annotation
}
