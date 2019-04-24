<?php

namespace Drupal\remotedbuser\Entity\Query;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Query\QueryBase;
use Drupal\Core\Entity\Query\QueryFactoryInterface;

/**
 * Factory class creating entity query objects for remote entities.
 */
class QueryFactory implements QueryFactoryInterface {

  /**
   * {@inheritdoc}
   */
  public function get(EntityTypeInterface $entity_type, $conjunction) {
    return new Query($entity_type, $conjunction, QueryBase::getNamespaces($this));
  }

  /**
   * {@inheritdoc}
   */
  public function getAggregate(EntityTypeInterface $entity_type, $conjunction) {
    return new Query($entity_type, $conjunction, QueryBase::getNamespaces($this));
  }

}
