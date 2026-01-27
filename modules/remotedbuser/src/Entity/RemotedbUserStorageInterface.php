<?php

namespace Drupal\remotedbuser\Entity;

use Drupal\Core\Entity\ContentEntityStorageInterface;
use Drupal\user\UserInterface;

/**
 * A storage that supports remote user entity types.
 */
interface RemotedbUserStorageInterface extends ContentEntityStorageInterface {

  /**
   * Methods to load a user by in the remote database.
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
   *
   * @return \Drupal\remotedbuser\Entity\RemotedbUserInterface|null
   *   The remote user or NULL.
   */
  public function loadBy($id, string $load_by): ?RemotedbUserInterface;

  /**
   * Tries to load an entity based on any unique property.
   *
   * Loading is tried in the following order:
   * 1. By mail address;
   * 2. By name;
   * 3. By remote user ID.
   *
   * @param int|string $id
   *   The identifier.
   *
   * @return \Drupal\remotedbuser\Entity\RemotedbUserInterface|null
   *   The remote user or NULL.
   */
  public function loadByAny($id): ?RemotedbUserInterface;

  /**
   * Creates a remote user from a user account object.
   *
   * @param \Drupal\user\UserInterface $account
   *   The local user account.
   *
   * @return \Drupal\remotedbuser\Entity\RemotedbUserInterface
   *   A remote user object.
   *
   * @throws \Drupal\remotedb\Exception\RemotedbException
   *   If the passed in account does not have a mail address.
   */
  public function fromAccount(UserInterface $account): RemotedbUserInterface;

  /**
   * Sets data from a remote account to the local account.
   *
   * @param \Drupal\remotedbuser\Entity\RemotedbUserInterface $entity
   *   The remote user.
   *
   * @return \Drupal\user\UserInterface
   *   The unsaved account, filled with values from the remote user.
   */
  public function toAccount(RemotedbUserInterface $entity): UserInterface;

  /**
   * Authenticates a user via the remote database.
   *
   * @param string $name
   *   User name to authenticate.
   * @param string $pass
   *   A plain-text password.
   *
   * @return int|bool
   *   The remotedb user's uid on success, or FALSE on failure to authenticate.
   */
  public function authenticate(string $name, string $pass): int|bool;

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
   */
  public function validateName(string $name, UserInterface $account): bool;

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
  public function validateMail(string $mail, UserInterface $account): bool;

}
