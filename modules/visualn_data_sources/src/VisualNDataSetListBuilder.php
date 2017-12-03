<?php

namespace Drupal\visualn_data_sources;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;

/**
 * Defines a class to build a listing of VisualN Data Set entities.
 *
 * @ingroup visualn_data_sources
 */
class VisualNDataSetListBuilder extends EntityListBuilder {


  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('VisualN Data Set ID');
    $header['name'] = $this->t('Name');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\visualn_data_sources\Entity\VisualNDataSet */
    $row['id'] = $entity->id();
    $row['name'] = Link::createFromRoute(
      $entity->label(),
      'entity.visualn_data_set.edit_form',
      ['visualn_data_set' => $entity->id()]
    );
    return $row + parent::buildRow($entity);
  }

}
