<?php

namespace Drupal\remotedbuser\Entity;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Cache\MemoryCache\MemoryCacheInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\ContentEntityStorageBase;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\remotedb\Entity\RemotedbInterface;
use Drupal\remotedb\Exception\RemotedbException;
use Drupal\remotedbuser\Exception\RemotedbExistingUserException;
use Drupal\user\UserInterface;

/**
 * Class for remote user storage.
 */
class RemotedbUserStorage extends ContentEntityStorageBase implements RemotedbUserStorageInterface {

  // ---------------------------------------------------------------------------
  // PROPERTIES
  // ---------------------------------------------------------------------------

  /**
   * A remote database.
   *
   * @var \Drupal\remotedb\Entity\RemotedbInterface
   */
  protected $remotedb;

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
    parent::__construct($entity_type, $entity_field_manager, $cache, $memory_cache, $entity_type_bundle_info);

    if (is_null($remotedb)) {
      // Get default remote database (if defined).
      $this->remotedb = \Drupal::service('remotedbuser.configuration')->getDefault();
    }
    else {
      $this->remotedb = $remotedb;
    }
  }

  // ---------------------------------------------------------------------------
  // GETTERS
  // ---------------------------------------------------------------------------

  /**
   * Returns the remote database that is used.
   *
   * @return \Drupal\remotedb\Entity\RemotedbInterface|null
   *   A remote database object.
   */
  public function getRemotedb() {
    return $this->remotedb;
  }

  // ---------------------------------------------------------------------------
  // LOADING/SAVING
  // ---------------------------------------------------------------------------

  /**
   * {@inheritdoc}
   */
  protected function readFieldItemsToPurge(FieldDefinitionInterface $field_definition, $batch_size) {}

  /**
   * {@inheritdoc}
   */
  protected function doLoadRevisionFieldItems($revision_id) {}

  /**
   * {@inheritdoc}
   */
  protected function doSaveFieldItems(ContentEntityInterface $entity, array $names = []) {
    // Save remote user into the remote database.
    $uid = $this->sendRequest('dbuser.save', [$entity->toArray()]);
    if ($uid) {
      $entity->uid = $uid;
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function doDeleteFieldItems($entities) {}

  /**
   * {@inheritdoc}
   */
  protected function purgeFieldItems(ContentEntityInterface $entity, FieldDefinitionInterface $field_definition) {}

  /**
   * {@inheritdoc}
   */
  protected function doDeleteRevisionFieldItems(ContentEntityInterface $revision) {}

  /**
   * {@inheritdoc}
   */
  public function loadByProperties(array $values = []) {
    foreach ($values as $key => $value) {
      switch ($key) {
        case static::BY_ID:
        case static::BY_NAME:
        case static::BY_MAIL:
          return $this->getFromStorage([$value], $key);
      }
    }

    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function loadBy($id, $load_by) {
    $entities = $this->getFromStorage([$id], $load_by);
    return reset($entities);
  }

  /**
   * {@inheritdoc}
   */
  protected function doLoadMultiple(array $ids = NULL) {
    // Attempt to load entities from the persistent cache. This will remove IDs
    // that were loaded from $ids.
    $entities_from_cache = $this->getFromPersistentCache($ids);

    // Load any remaining entities from the database.
    if ($entities_from_storage = $this->getFromStorage($ids)) {
      $this->invokeStorageLoadHook($entities_from_storage);
      $this->setPersistentCache($entities_from_storage);
    }

    return $entities_from_cache + $entities_from_storage;
  }

  /**
   * Gets entities from the storage.
   *
   * @param array|null $ids
   *   If not empty, return entities that match these IDs. Return all entities
   *   when NULL.
   * @param string $load_by
   *   The key to load remote users by.
   *
   * @return \Drupal\remotedbuser\Entity\RemotedbUserInterface[]
   *   Array of entities from the storage.
   */
  protected function getFromStorage(array $ids = NULL, $load_by = NULL) {
    $entities = [];

    switch ($load_by) {
      case static::BY_ID:
      case static::BY_NAME:
      case static::BY_MAIL:
        break;

      default:
        $load_by = self::BY_ID;
    }

    foreach ($ids as $id) {
      // The remote database only supports loading one remote user at a time.
      $data = $this->sendRequest('dbuser.retrieve', [$id, $load_by]);
      if ($data) {
        $data['is_new'] = FALSE;
        $entity = $this->create($data);
        $entities[$entity->uid] = $entity;
      }
    }

    return $entities;
  }

  /**
   * {@inheritdoc}
   */
  protected function has($id, EntityInterface $entity) {
    return !$entity->isNew();
  }

  /**
   * {@inheritdoc}
   */
  protected function getQueryServiceName() {
    return 'remotedbuser.entity.query';
  }

  /**
   * {@inheritdoc}
   */
  public function countFieldData($storage_definition, $as_bool = FALSE) {}

  /**
   * {@inheritdoc}
   */
  public function fromAccount(UserInterface $account) {
    if (!$account->getEmail()) {
      throw new RemotedbException(t("The account cannot be saved in the remote database, because it doesn't have a mail address."));
    }

    $values = array(
      'name' => $account->getAccountName(),
      'mail' => $account->getEmail(),
      'pass' => $account->getPassword(),
      'status' => $account->isActive(),
      'created' => $account->getCreatedTime(),
      'timezone' => !empty($account->timezone->value) ? $account->timezone->value : NULL,
      'language' => !empty($account->language->value) ? $account->language->value : NULL,
      'init' => $account->getInitialEmail(),
    );

    if (!empty($account->remotedb_uid->value)) {
      $values['uid'] = $account->remotedb_uid->value;
      $values['is_new'] = FALSE;
    }

    // Instantiate a remote user.
    $entity = $this->create($values);

    // Cross reference.
    $entity->account = $account;
    $account->remotedb_user = $entity;

    return $entity;
  }

  /**
   * {@inheritdoc}
   */
  public function toAccount(RemotedbUserInterface $entity) {
    $user_storage = \Drupal::entityTypeManager()->getStorage('user');

    // First, get account from local database, if it exists.
    // First find by remotedb_uid, then by name and finally by mail.
    $search = array(
      'remotedb_uid' => $entity->uid,
      'name' => $entity->name,
      'mail' => $entity->mail,
    );
    foreach ($search as $key => $value) {
      $users = $user_storage->loadByProperties([$key => $value]);
      if (!empty($users)) {
        $account = reset($users);
        break;
      }
    }

    // Check if this account is already linked to a remote account. If so, we should not
    // suddenly link it to an other account.
    if (!empty($account->remotedb_uid->value) && $account->remotedb_uid->value != $entity->uid) {
      $vars = array(
        '@uid' => $account->id(),
        '@remotedb_uid' => $entity->uid,
      );
      throw new RemotedbExistingUserException(t('Failed to synchronize the remote user. The remote user @remotedb_uid conflicts with local user @uid.', $vars));
    }

    // Name and mail must be unique. If an account was found, make sure that no other account
    // exists that has either the name or the mail address from the remote account.
    if (!empty($account)) {
      $search = array(
        'name' => $entity->name,
        'mail' => $entity->mail,
      );
      foreach ($search as $key => $value) {
        $users = $user_storage->loadByProperties([$key => $value]);
        if (!empty($users)) {
          $account2 = reset($users);
          if ($account->id() != $account2->id()) {
            // We have a conflict here.
            $vars = array(
              '@uid' => $account2->id(),
              '@remotedb_uid' => $entity->uid,
            );
            throw new RemotedbExistingUserException(t('Failed to synchronize the remote user. The remote user @remotedb_uid conflicts with local user @uid.', $vars));
          }
        }
      }
    }

    // Construct values to set on the local account.
    $values = $entity->toArray();
    // The remote user's uid should not overwrite the local user's uid,
    // but instead be saved as 'remotedb_uid'.
    $values['remotedb_uid'] = $values['uid'];
    unset($values['uid']);

    if (empty($account)) {
      // No account found, create a new user.
      $account = $user_storage->create($values);
    }
    else {
      // Update user account.
      $update_props = \Drupal::service('config.factory')->get('remotedbuser.settings')->get('sync_properties');
      foreach ($update_props as $key) {
        if (empty($key)) {
          continue;
        }
        if (isset($values[$key])) {
          $account->$key = $values[$key];
        }
      }

      // Always set remotedb_uid.
      $account->remotedb_uid = $values['remotedb_uid'];
    }

    // Cross reference.
    $entity->account = $account;
    $account->remotedb_user = $entity;

    // Set flag that account should *not* be send back to the remote database again.
    $account->from_remotedb = TRUE;

    return $account;
  }

  // ---------------------------------------------------------------------------
  // ACTION
  // ---------------------------------------------------------------------------

  /**
   * {@inheritdoc}
   */
  public function authenticate($name, $pass) {
    return $this->sendRequest('dbuser.authenticate', array($name, $pass));
  }

  /**
   * {@inheritdoc}
   */
  public function validateName($name, $account) {
    if (!empty($account->name) && $account->name == $name) {
      // The username did not change. No need to validate.
      return TRUE;
    }

    $remote_account = $this->loadBy($name, self::BY_NAME);
    if (empty($remote_account)) {
      // Name is not taken yet.
      return TRUE;
    }
    elseif (empty($account->remotedb_uid)) {
      // This could be a valid case, but only if user name and mail exactly match.
      if (isset($account->mail)) {
        if ($name == $remote_account->name && $account->mail == $remote_account->mail) {
          return TRUE;
        }
      }
    }
    elseif ($account->remotedb_uid == $remote_account->uid) {
      // Accounts match.
      return TRUE;
    }
    // In all other cases, name is already taken!
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function validateMail($mail, $account) {
    if (!empty($account->mail) && $account->mail == $mail) {
      // The mail address did not change. No need to validate.
      return TRUE;
    }

    $remote_account = $this->loadBy($mail, self::BY_MAIL);
    if (empty($remote_account)) {
      // Mail address is not taken yet.
      return TRUE;
    }
    elseif (empty($account->remotedb_uid)) {
      // This could be a valid case, but only if user name and mail exactly match.
      if (isset($account->name)) {
        if ($account->name == $remote_account->name && $mail == $remote_account->mail) {
          return TRUE;
        }
      }
    }
    elseif ($account->remotedb_uid == $remote_account->uid) {
      // Accounts match.
      return TRUE;
    }
    // In all other cases, mail address is already taken!
    return FALSE;
  }

  /**
   * Sends a request to the remote database.
   *
   * @param string $method
   *   The method to call on the server.
   * @param array $params
   *   The parameters to send.
   *
   * @return mixed
   *   The result of the method call.
   * @throws RemotedbException
   *   In case the remote database object was not set.
   */
  protected function sendRequest($method, array $params = array()) {
    if (!($this->remotedb instanceof RemotedbInterface)) {
      throw new RemotedbException($this->t('Can not perform request to the remote database, because the RemotedbUserController did not receive a remote database object.'));
    }
    try {
      return $this->remotedb->sendRequest($method, $params);
    }
    catch (RemotedbException $e) {
      $e->logError();
      return FALSE;
    }
  }

}
