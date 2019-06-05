<?php

namespace Drupal\remotedbuser_test\Entity;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Cache\MemoryCache\MemoryCacheInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\remotedb\Entity\RemotedbInterface;
use Drupal\remotedbuser\Entity\RemotedbUserStorage as OriginalRemotedbUserStorage;

/**
 * Overrides default storage class for remotedb_user entity type.
 */
class RemotedbUserStorage extends OriginalRemotedbUserStorage {

  /**
   * Constructs a RemotedbUserStorage instance.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The cache backend to be used.
   * @param \Drupal\Core\Cache\MemoryCache\MemoryCacheInterface|null $memory_cache
   *   The memory cache backend.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle info.
   * @param \Drupal\remotedb\Entity\RemotedbInterface $remotedb
   *   The remote database in which the remote users are stored.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityFieldManagerInterface $entity_field_manager, CacheBackendInterface $cache, MemoryCacheInterface $memory_cache = NULL, EntityTypeBundleInfoInterface $entity_type_bundle_info = NULL, RemotedbInterface $remotedb = NULL) {

    // Set remotedb mock.
    $remotedb = \Drupal::entityTypeManager()->getStorage('remotedb')->create([]);
    $remotedb->setCallback([$this, 'remotedbCallback']);

    parent::__construct($entity_type, $entity_field_manager, $cache, $memory_cache, $entity_type_bundle_info, $remotedb);
  }

  /**
   * Gets all accounts.
   *
   * @return array
   *   An array of accounts.
   */
  public function getRemoteAccounts() {
    return \Drupal::state()->get('remotedbuser_test_accounts', []);
  }

  /**
   * Save accounts.
   *
   * @param array $accounts
   *   The accounts to save in database.
   */
  private function setRemoteAccounts(array $accounts) {
    \Drupal::state()->set('remotedbuser_test_accounts', $accounts);
  }

  /**
   * Callback for remote database calls.
   *
   * @param string $method
   *   The method being called.
   * @param array $params
   *   An array of parameters.
   *
   * @return mixed
   *   Returns different values depending on the method call.
   */
  public function remotedbCallback($method, array $params) {
    switch ($method) {
      case 'dbuser.retrieve':
        $id = $params[0];
        $by = $params[1];
        return $this->dbuserRetrieve($id, $by);

      case 'dbuser.save':
        $account = $params[0];
        return $this->dbuserSave($account);

      case 'dbuser.authenticate':
        $name = $params[0];
        $pass = $params[1];
        return $this->dbuserAuthenticate($name, $pass);
    }
  }

  /**
   * Retrieves a single remote user.
   *
   * @param mixed $id
   *   The id of the user.
   * @param string $by
   *   The key to load the user by.
   *
   * @return array
   *   An array of user data if found.
   *   NULL otherwise.
   */
  private function dbuserRetrieve($id, $by) {
    foreach ($this->getRemoteAccounts() as $account) {
      if ($account[$by] == $id) {
        return $account;
      }
    }
    return NULL;
  }

  /**
   * Saves a remote user.
   *
   * @param array $user_data
   *   The user data.
   *
   * @return int
   *   The remote user uid.
   */
  private function dbuserSave(array $user_data) {
    // First check if this account already exists.
    $search = [
      'uid',
      'mail',
      'name',
    ];

    $accounts = [];
    foreach ($search as $key) {
      if (!isset($user_data[$key])) {
        continue;
      }

      $account = $this->dbuserRetrieve($user_data[$key], $key);
      if ($account) {
        // An account is found.
        $accounts[$key] = $account;
      }
    }

    // Use the first found account.
    $account = reset($accounts);

    if (count($accounts) > 1) {
      // Multiple accounts were found. Stop when different.
      foreach ($accounts as $double_account) {
        if ($double_account !== $account) {
          // Multiple different accounts found. Stop.
          return FALSE;
        }
      }
    }

    if (empty($account)) {
      // No existing account was found, thus create a new user.
      if (empty($user_data['uid'])) {
        // Generate uid if it doesn't have one.
        $user_data['uid'] = count($user_data) + 1000;
      }
    }
    else {
      // An existing account was found. Update it.
      $user_data = array_merge($account, $user_data);
    }

    $accounts = $this->getRemoteAccounts();
    $accounts[$user_data['uid']] = $user_data;
    $this->setRemoteAccounts($accounts);

    return $user_data['uid'];
  }

  /**
   * Authenticates an user.
   *
   * @param string $name
   *   The user's name.
   * @param string $password
   *   The user's password.
   *
   * @return int|false
   *   The remote user's ID if authentication was successful.
   *   FALSE otherwise.
   */
  private function dbuserAuthenticate($name, $password) {
    $user_data = $this->dbuserRetrieve($name, 'name');

    // No account found? Return FALSE.
    if (empty($user_data)) {
      return FALSE;
    }

    if (\Drupal::service('password')->check($password, $user_data['pass'])) {
      return $user_data['uid'];
    }

    // In all other cases, the password is invalid.
    return FALSE;
  }

}
