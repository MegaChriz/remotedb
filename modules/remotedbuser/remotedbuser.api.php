<?php

/**
 * @file
 * Hooks invoked by the Remote database User module.
 */

/**
 * Act on remote users when loaded from the database.
 *
 * @param array $remote_accounts
 *   An array of remote account objects, indexed by uid.
 */
function hook_remotedb_user_load($remote_accounts) {
  // @todo Provide example.
}

/**
 * A remote user account is about to be saved into the remote database.
 *
 * @param \Drupal\remotedbuser\Entity\RemotedbUserInterface $remote_account
 *   The remote account that will be saved in the remote database.
 *
 * @see hook_remotedbuser_insert()
 * @see hook_remotedbuser_update()
 */
function hook_remotedb_user_presave($remote_account) {
  // @todo Provide example.
}

/**
 * A remote user account was created.
 *
 * @param \Drupal\remotedbuser\Entity\RemotedbUserInterface $remote_account
 *   The remote account that was saved in the remote database.
 *
 * @see hook_remotedbuser_presave()
 * @see hook_remotedbuser_update()
 */
function hook_remotedb_user_insert($remote_account) {
  // @todo Provide example.
}

/**
 * A remote user account was updated.
 *
 * @param \Drupal\remotedbuser\Entity\RemotedbUserInterface $remote_account
 *   The remote account that was saved in the remote database.
 *
 * @see hook_remotedbuser_presave()
 * @see hook_remotedbuser_insert()
 */
function hook_remotedb_user_update($remote_account) {
  // @todo Provide example.
}
