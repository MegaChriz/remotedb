<?php

/**
 * Contains Drupal\remotedb\Controller\RemotedbController.
 */

namespace Drupal\remotedb\Controller;

use \EntityAPIController;
use \EntityFieldQuery;

/**
 * Remotedb entity controller class.
 */
class RemotedbController extends EntityAPIController {
  /**
   * Get all remote databases.
   */
  public function loadAll() {
    $query = new EntityFieldQuery();
    $query->entityCondition('entity_type', $this->entityType);
    $results = $query->execute();
    $ids = isset($results[$this->entityType]) ? array_keys($results[$this->entityType]) : array();
    $entities = $ids ? entity_load($this->entityType, $ids) : array();
    return $entities;
  }

  /**
   * Returns a list of entities as options.
   *
   * @param array $entities
   *   (optional) A list of entities.
   *   Defaults to all entities.
   *
   * @return array
   *   A list of choosable options in forms.
   */
  public function options($entities = NULL) {
    if (is_null($entities)) {
      $entities = $this->loadAll();
    }
    $options = array();
    foreach ($entities as $entity_id => $entity) {
      $options[$entity_id] = entity_label($this->entityType, $entity);
    }
    return $options;
  }
}
