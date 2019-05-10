<?php

namespace Drupal\remotedbuser\Entity;

use Drupal\Core\Entity\ContentEntityStorageInterface;
use Drupal\user\UserInterface;

/**
 * A storage that supports remote user entity types.
 */
interface RemotedbUserStorageInterface extends ContentEntityStorageInterface {

  /**
   * Methods to load an user by in the remote database.
   *
   * @var string
   */
  const BY_ID = 'uid';
  const BY_NAME = 'name';
  const BY_MAIL = 'mail';

  /**
   * Loads a single entity by a certain property.
   *
   * @param int|string $id
   *   A remote user's identifier.
   * @param string $load_by
   *   The key to load the remote user by.
   */
  public function loadBy($id, $load_by);

  /**
   * Tries to load an entity based on any unique property.
   *
   * Loading is tried in the following order:
   * 1. By mail address;
   * 2. By name;
   * 3. By remote user ID.
   */
  public function loadByAny($id);

  /**
   * Creates a remote user from a user account object.
   *
   * @param \Drupal\user\UserInterface $account
   *   The local user account.
   *
   * @return \Drupal\remotedb\Entity\RemotedbUserInterface
   *   A remote user object.
   * @throws \Drupal\remotedb\Exception\RemotedbException
   *   If the passed in account does not have a mail address.
   */
  public function fromAccount(UserInterface $account);

  /**
   * Sets data from a remote account to the local account.
   *
   * @param \Drupal\remotedb\Entity\RemotedbUserInterface $entity
   *   The remote user.
   *
   * @return object
   *   The unsaved account, filled with values from the remote user.
   */
  public function toAccount(RemotedbUserInterface $entity);

  /**
   * Authenticates an user via the remote database.
   *
   * @param string $name
   *   User name to authenticate.
   * @param string $password
   *   A plain-text password.
   *
   * @return int|bool
   *   The remotedb user's uid on success, or FALSE on failure to authenticate.
   */
  public function authenticate($name, $pass);

  /**
   * Validates name.
   *
   * @param string $name
   *   The name to check for existence.
   * @param \Drupal\user\UserInterface $account
   *   The user's local account.
   *
   * @return bool
   *   TRUE if validation passes.
   *   FALSE otherwise.
   * @todo Remotedb_uid should probably be send along instead.
   */
  public function validateName($name, UserInterface $account);

  /**
   * Validates mail address.
   *
   * @param string $mail
   *   The mail address to check for existence.
   * @param \Drupal\user\UserInterface $account
   *   The user's local account.
   *
   * @return bool
   *   TRUE if validation passes.
   *   FALSE otherwise.
   */
  public function validateMail($mail, UserInterface $account);

}
