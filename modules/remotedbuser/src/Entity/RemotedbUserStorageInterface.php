<?php

namespace Drupal\remotedbuser\Entity;

use Drupal\Core\Entity\ContentEntityStorageInterface;

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
   * {@inheritdoc}
   */
  public function fromAccount($account);

  /**
   * Sets data from a remote account to the local account.
   *
   * @param \Drupal\remotedb\Entity\RemotedbUserInterface $entity
   *   The Remote user.
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
   * @param object $account
   *   The user's account.
   *
   * @return bool
   *   TRUE if validation passes.
   *   FALSE otherwise.
   * @todo Remotedb_uid should probably be send along instead.
   */
  public function validateName($name, $account);

  /**
   * Validates mail address.
   *
   * @param string $mail
   *   The mail address to check for existence.
   * @param object $account
   *   The user's account.
   *
   * @return bool
   *   TRUE if validation passes.
   *   FALSE otherwise.
   */
  public function validateMail($mail, $account);

}
