<?php

/**
 * Contains Drupal\remotedbuser\Entity\RemotedbUser.
 */

namespace Drupal\remotedbuser\Entity;

use \Entity;

class RemotedbUser extends Entity {
  /**
   * Translates remote user object to an array.
   */
  public function toArray() {
    return get_object_vars($this);
  }
}
