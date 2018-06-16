<?php

/**
 * @file
 * Conatins TSVResourceFormat
 */

namespace Drupal\visualn\Plugin\VisualN\ResourceFormat;

use Drupal\visualn\Plugin\VisualNResourceFormatBase;

/**
 * Provides a 'TSV' VisualN resource format.
 *
 * @VisualNResourceFormat(
 *  id = "visualn_tsv",
 *  label = @Translation("TSV"),
 *  output = "remote_generic_tsv",
 * )
 */
class TSVResourceFormat extends VisualNResourceFormatBase {

  // @todo: plugins could also have configuration forms,
  //   e.g. for csv delimiter property

}
