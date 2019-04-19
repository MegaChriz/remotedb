<?php

namespace Drupal\remotedb\Entity;

use Drupal\Core\Config\Entity\ConfigEntityStorage;

/**
 * Remote database storage class.
 */
class RemotedbStorage extends ConfigEntityStorage implements RemotedbStorageInterface {

  /**
   * {@inheritdoc}
   */
  public function options($entities = NULL) {
    if (is_null($entities)) {
      $entities = $this->loadMultiple();
    }
    $options = [];
    foreach ($entities as $entity_id => $entity) {
      $options[$entity_id] = $entity->label();
    }
    return $options;
  }

}
