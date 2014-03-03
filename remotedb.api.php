<?php

/**
 * @file
 * Hooks invoked by the RemoteDB-module.
 */

/**
 * This hook allows you to modify account data before it is send to
 * the remote database. Any modifications won't affect the account
 * in the local database.
 *
 * @param array $account
 *   The account data, as an array.
 *
 * @return void
 */
function hook_remotedb_send_user(&$account) {
  // Example: add profile information to the account.
  if (!isset($account['profile']) && !empty($account['uid'])) {
    $profile = mymodule_load_profile($account['uid']);
    $account['profile'] = $profile;
  }
}

/**
 * This hook allows you to respond to the event when account data
 * is retrieved from the remote database and saved in the local
 * database.
 *
 * @param object $account
 *   The saved account object.
 *
 * @return void
 */
function hook_remotedb_retrieve_user($account) {
  // Example: update the account's profile data.
  $profile = mymodule_load_profile($account->uid);
  $remote_profile = RemoteDB::get()->sendRequest('profile.get')->getResult();
  $profile = array_merge($profile, $remote_profile);
  mymodule_save_profile($account->uid, $profile);
}
