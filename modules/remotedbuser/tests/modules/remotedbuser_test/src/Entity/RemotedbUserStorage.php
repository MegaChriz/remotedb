<?php

namespace Drupal\remotedbuser_test\Controller;

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

  // ---------------------------------------------------------------------------
  // CONSTRUCT
  // ---------------------------------------------------------------------------

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
    $remotedb = \Drupal::entityTypeManager()->getStorage('remotedb')->create([]));
    $remotedb->setCallback([$this, 'remotedbCallback']);

    parent::__construct($entity_type, $entity_field_manager, $cache, $memory_cache, $entity_type_bundle_info, $remotedb);
  }

  // ---------------------------------------------------------------------------
  // HELPERS
  // ---------------------------------------------------------------------------

  /**
   * Gets all accounts.
   *
   * @return array
   *   An array of accounts.
   */
  public function getRemoteAccounts() {
    // @FIXME
// $result = db_select('variable')
//       ->fields('variable', array())
//       ->condition('name', 'remotedbuser_test_accounts')
//       ->execute()
//       ->fetch();

    if (is_object($result) && !empty($result->value)) {
      return unserialize($result->value);
    }
    return array();
  }

  /**
   * Save accounts.
   *
   * @param array $accounts
   *   The account to save in database.
   *
   * @return void
   */
  private function setRemoteAccounts(array $accounts) {
    return \Drupal::configFactory()->getEditable('remotedbuser.settings')->set('remotedbuser_test_accounts', $accounts)->save();
  }

  // ---------------------------------------------------------------------------
  // REMOTE DATABASE
  // ---------------------------------------------------------------------------

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
  public function remotedbCallback($method, $params) {
    $accounts = $this->getRemoteAccounts();

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
    $accounts = $this->getRemoteAccounts();
    foreach ($accounts as $account) {
      if ($account[$by] == $id) {
        return $account;
      }
    }
    return NULL;
  }

  /**
   * Saves a remote user.
   *
   * @param array $account
   *   The user data.
   *
   * @return int
   *   The remote user uid.
   */
  private function dbuserSave($account) {
    $accounts = $this->getRemoteAccounts();
    if (empty($account['uid'])) {
      // Generate uid if it doesn't have one.
      $account['uid'] = count($accounts) + 1000;
    }
    $accounts[$account['uid']] = $account;
    $this->setRemoteAccounts($accounts);
    return $account['uid'];
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
   *   The remote user's ID if authentication was succesful.
   *   FALSE otherwise.
   */
  private function dbuserAuthenticate($name, $password) {
    // @FIXME
// // @FIXME
// // This looks like another module's variable. You'll need to rewrite this call
// // to ensure that it uses the correct configuration object.
// require_once \Drupal::root() . '/' . variable_get('password_inc', 'includes/password.inc');

    $account = $this->dbuserRetrieve($name, 'name');

    // No account found? Return FALSE.
    if (empty($account)) {
      return FALSE;
    }

    $account = (object) $account;
    if (user_check_password($password, $account)) {
      return $account->uid;
    }

    // In all other cases, the password is invalid.
    return FALSE;
  }
}
