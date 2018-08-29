<?php

/**
 * @file
 * Contains visualn_data_set.page.inc.
 *
 * Page callback for VisualN Data Set entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for VisualN Data Set templates.
 *
 * Default template: visualn_data_set.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_visualn_data_set(array &$variables) {
  // Fetch VisualNDataSet Entity Object.
  $visualn_data_set = $variables['elements']['#visualn_data_set'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
