<?php

namespace Drupal\remotedbuser\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\user\UserInterface;

/**
 * Provides an interface for defining a remote user.
 *
 * @property int|string|null $uid
 * @property string|null $name
 * @property string|null $mail
 * @property \Drupal\user\UserInterface|null $account
 */
interface RemotedbUserInterface extends ContentEntityInterface {

  /**
   * Sets data from a remote account to the local account.
   *
   * @return \Drupal\user\UserInterface
   *   The unsaved account, filled with values from the remote user.
   */
  public function toAccount(): UserInterface;

}
