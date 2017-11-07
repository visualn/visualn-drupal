<?php

/**
 * @file
 * Module to embed visualn drawings using tokens
 * Made on basis of token_embed_views module (8.x-1.0-alpha3 version)
 */

use Drupal\Core\Render\BubbleableMetadata;

/**
 * Implements hook_token_info().
 */
function visualn_embed_token_token_info() {
  $info = array();
  $info['types']['visualn'] = array(
    'name' => t('VisualN'),
    'description' => ('Tokens to embed visualn drawings.'),
  );
  $info['tokens']['visualn']['embed']['drawing'] = array(
    'name' => t('Embed visualn drawing'),
    'description' => t('Embed visualn drawing using tokens. The following values may be appended to the token: drawing-entity-id'),
    // @todo: add an option to select fetcher field (and maybe even delta)
    //'description' => t('Embed visualn drawing using tokens. The following values may be appended to the token: drawing-entity-id:drawing-fetcher-field-name:delta'),
    'dynamic' => TRUE,
  );

  return $info;
}

/**
 * Implements hook_tokens().
 */
function visualn_embed_token_tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {
  $replacements = array();
  if ($type == 'visualn') {
    foreach ($tokens as $name => $original) {
      $args = explode(':', $name);
      $entity_id = $args[2];
      // @todo: maybe do additional checks (e.g. permissions)
      $entity = \Drupal::entityTypeManager()->getStorage('visualn_drawing')->load($entity_id);
      if (!empty($entity)) {
        $drawing_markup = $entity->buildDrawing();
        $replacements[$original] = \Drupal::service('renderer')->render($drawing_markup);
      }
      // @todo: add cache tags
    }
  }

  return $replacements;
}
