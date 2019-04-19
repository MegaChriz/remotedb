<?php

namespace Drupal\remotedbuser\Entity;

/**
 *
 */
interface RemotedbUserInterface {

  /**
   * Saves the entity.
   */
  public function save();

  /**
   * Translates remote user object to an array.
   *
   * @return array
   *   An array of values, representing the remote user.
   */
  public function toArray();

  /**
   * Sets data from a remote account to the local account.
   *
   * @return object
   *   The unsaved account, filled with values from the remote user.
   */
  public function toAccount();

}
