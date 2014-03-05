<?php

/**
 * Contains Drupal\remotedbuser\Entity\RemotedbUser.
 */

namespace Drupal\remotedbuser\Entity;

use \Entity;

class RemotedbUser extends Entity implements RemotedbUserInterface {
  /**
   * Implements RemotedbUserInterface::toArray().
   */
  public function toArray() {
    return get_object_vars($this);
  }

  /**
   * Implements RemotedbUserInterface::toAccount().
   */
  public function toAccount() {
    return entity_get_controller($this->entityType)->toAccount($this);
  }
}
