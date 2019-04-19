<?php

namespace Drupal\remotedbuser\Entity;

use Entity;

/**
 *
 */
class RemotedbUser extends Entity implements RemotedbUserInterface {

  /**
   * Implements RemotedbUserInterface::toArray().
   */
  public function toArray() {
    $values = get_object_vars($this);

    // Don't send attached account along.
    unset($values['account']);

    return $values;
  }

  /**
   * Implements RemotedbUserInterface::toAccount().
   */
  public function toAccount() {
    return entity_get_controller($this->entityType)->toAccount($this);
  }

}
