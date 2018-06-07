<?php

/**
 * @file
 * Conatins CSVResourceFormat
 */

namespace Drupal\visualn\Plugin\VisualN\ResourceFormat;

use Drupal\visualn\Plugin\VisualNResourceFormatBase;

/**
 * Provides a 'CSV' VisualN resource format.
 *
 * @VisualNResourceFormat(
 *  id = "visualn_csv",
 *  label = @Translation("CSV"),
 *  output = "remote_generic_csv",
 * )
 */
class CSVResourceFormat extends VisualNResourceFormatBase {
}
