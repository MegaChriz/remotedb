<?php

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
    $entities = $ids ? \Drupal::entityManager()->getStorage($this->entityType) : array();
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
      $options[$entity_id] = $entity->label();
    }
    return $options;
  }

  /**
   * Implements EntityAPIController::export().
   */
  function export($entity, $prefix = '') {
    $vars = array(
      'name' => $entity->id(),
      'label' => $entity->label(),
      'url' => $entity->getUrl(),
    );
    $methods = $entity->getAuthenticationMethods();
    foreach ($methods as $key => $method) {
      $vars['authentication_methods'][$key] = $method->getConfiguration();
    }
    $vars += get_object_vars($entity);
    unset($vars['is_new']);
    return entity_var_json_export($vars, $prefix);
  }
}
