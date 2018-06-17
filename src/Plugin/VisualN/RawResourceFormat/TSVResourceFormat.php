<?php

/**
 * @file
 * Conatins TSVResourceFormat
 */

namespace Drupal\visualn\Plugin\VisualN\RawResourceFormat;

use Drupal\visualn\Plugin\RawResourceFormatBase;

/**
 * Provides a 'TSV' VisualN raw resource format.
 *
 * @VisualNRawResourceFormat(
 *  id = "visualn_tsv",
 *  label = @Translation("TSV"),
 *  output = "remote_generic_tsv",
 * )
 */
class TSVResourceFormat extends RawResourceFormatBase {

  // @todo: plugins could also have configuration forms,
  //   e.g. for csv delimiter property

}
