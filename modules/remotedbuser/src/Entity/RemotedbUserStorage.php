<?php

namespace Drupal\remotedbuser\Entity;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Cache\MemoryCache\MemoryCacheInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\ContentEntityStorageBase;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\remotedb\Entity\RemotedbInterface;
use Drupal\remotedb\Exception\RemotedbException;
use Drupal\remotedbuser\Exception\RemotedbExistingUserException;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class for remote user storage.
 */
class RemotedbUserStorage extends ContentEntityStorageBase implements RemotedbUserStorageInterface {

  /**
   * The entity type manager.
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The remotedbuser settings.
   */
  protected ImmutableConfig $config;

  /**
   * A remote database.
   */
  protected ?RemotedbInterface $remotedb = NULL;

  /**
   * Constructs a RemotedbUserStorage instance.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The cache backend to be used.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Config\ImmutableConfig $config
   *   The remotedbuser settings.
   * @param \Drupal\Core\Cache\MemoryCache\MemoryCacheInterface $memory_cache
   *   The memory cache backend.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle info.
   * @param \Drupal\remotedb\Entity\RemotedbInterface|null $remotedb
   *   The remote database in which the remote users are stored.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityFieldManagerInterface $entity_field_manager, CacheBackendInterface $cache, EntityTypeManagerInterface $entity_type_manager, ImmutableConfig $config, MemoryCacheInterface $memory_cache, EntityTypeBundleInfoInterface $entity_type_bundle_info, ?RemotedbInterface $remotedb = NULL) {
    parent::__construct($entity_type, $entity_field_manager, $cache, $memory_cache, $entity_type_bundle_info);

    $this->entityTypeManager = $entity_type_manager;
    $this->config = $config;

    if (!$remotedb instanceof RemotedbInterface) {
      // Get default remote database (if defined).
      $this->remotedb = \Drupal::service('remotedbuser.configuration')->getDefault();
    }
    else {
      $this->remotedb = $remotedb;
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity_field.manager'),
      $container->get('cache.entity'),
      $container->get('entity_type.manager'),
      $container->get('config.factory')->get('remotedbuser.settings'),
      $container->get('entity.memory_cache'),
      $container->get('entity_type.bundle.info'),
      $container->get('remotedbuser.configuration')->getDefault(),
    );
  }

  /**
   * Returns the remote database that is used.
   *
   * @return \Drupal\remotedb\Entity\RemotedbInterface|null
   *   A remote database object.
   */
  public function getRemotedb(): ?RemotedbInterface {
    return $this->remotedb;
  }

  /**
   * {@inheritdoc}
   */
  protected function readFieldItemsToPurge(FieldDefinitionInterface $field_definition, $batch_size) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  protected function doLoadRevisionFieldItems(int|string $revision_id): void {}

  /**
   * {@inheritdoc}
   */
  protected function doLoadMultipleRevisionsFieldItems($revision_ids) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  protected function doSaveFieldItems(ContentEntityInterface $entity, array $names = []): void {
    // Save remote user into the remote database.
    $uid = $this->sendRequest('dbuser.save', [$entity->toArray()]);
    if (is_int($uid) || (is_string($uid) && is_numeric($uid))) {
      assert($entity instanceof RemotedbUserInterface);
      $saved_uid = (int) $uid;
      $entity->uid = $saved_uid;
      $this->resetCache([$saved_uid]);
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function doDeleteFieldItems($entities): void {}

  /**
   * {@inheritdoc}
   */
  protected function purgeFieldItems(ContentEntityInterface $entity, FieldDefinitionInterface $field_definition): void {}

  /**
   * {@inheritdoc}
   */
  protected function doDeleteRevisionFieldItems(ContentEntityInterface $revision): void {}

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
  public function loadBy($id, string $load_by): ?RemotedbUserInterface {
    $entities = $this->getFromStorage([$id], $load_by);
    return $entities !== [] ? reset($entities) : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function loadByAny($id): ?RemotedbUserInterface {
    // Remove extra spaces.
    $id = trim((string) $id);

    if ($id === '') {
      // Skip "empty" users.
      return NULL;
    }

    $load_by_methods = [
      static::BY_MAIL,
      static::BY_NAME,
      static::BY_ID,
    ];
    foreach ($load_by_methods as $load_by) {
      $remote_account = $this->loadBy($id, $load_by);
      if ($remote_account instanceof RemotedbUserInterface) {
        return $remote_account;
      }
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  protected function doLoadMultiple(?array $ids = NULL) {
    // Attempt to load entities from the static cache. This will remove IDs
    // that were loaded from $ids.
    $entities_from_cache = $this->getFromStaticCache($ids ?? []);

    // Load any remaining entities from the database.
    $entities_from_storage = [];
    if ($ids !== NULL) {
      $entities_from_storage = $this->getFromStorage($ids);
      if ($entities_from_storage !== []) {
        $this->invokeStorageLoadHook($entities_from_storage);
        $this->setStaticCache($entities_from_storage);
      }
    }

    return $entities_from_cache + $entities_from_storage;
  }

  /**
   * Gets entities from the storage.
   *
   * @param array|null $ids
   *   If not empty, return entities that match these IDs. Return all entities
   *   when NULL.
   * @param string|null $load_by
   *   The key to load remote users by.
   *
   * @return \Drupal\remotedbuser\Entity\RemotedbUserInterface[]
   *   Array of entities from the storage.
   */
  protected function getFromStorage(?array $ids = NULL, ?string $load_by = NULL): array {
    $entities = [];

    if ($ids === NULL) {
      return $entities;
    }

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
      if (is_array($data)) {
        $data['is_new'] = FALSE;
        $entity = $this->create($data);
        assert($entity instanceof RemotedbUserInterface);
        // Key by the remote uid property (not entity id key, which is unset).
        $entities[$entity->uid] = $entity;
      }
    }

    if ($entities !== []) {
      $this->postLoad($entities);
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
  public function countFieldData($storage_definition, $as_bool = FALSE) {
    return 0;
  }

  /**
   * {@inheritdoc}
   */
  public function fromAccount(UserInterface $account): RemotedbUserInterface {
    $mail = $account->getEmail();
    if ($mail === NULL || $mail === '') {
      throw new RemotedbException(t("The account cannot be saved in the remote database, because it doesn't have a mail address."));
    }

    $timezone_value = $account->hasField('timezone') ? $account->get('timezone')->value : NULL;
    $language_value = $account->hasField('language') ? $account->get('language')->value : NULL;
    $values = [
      'name' => $account->getAccountName(),
      'mail' => $mail,
      'pass' => $account->getPassword(),
      'status' => $account->isActive(),
      'created' => $account->getCreatedTime(),
      'timezone' => ($timezone_value !== NULL && $timezone_value !== '' && $timezone_value !== 0 && $timezone_value !== '0') ? $timezone_value : NULL,
      'language' => ($language_value !== NULL && $language_value !== '' && $language_value !== 0 && $language_value !== '0') ? $language_value : NULL,
      'init' => $account->getInitialEmail(),
    ];

    $remotedb_uid = $account->hasField('remotedb_uid') ? $account->get('remotedb_uid')->value : NULL;
    if ($remotedb_uid !== NULL && $remotedb_uid !== '' && $remotedb_uid !== 0 && $remotedb_uid !== '0') {
      $values['uid'] = $remotedb_uid;
      $values['is_new'] = FALSE;
    }

    // Instantiate a remote user.
    $entity = $this->create($values);
    assert($entity instanceof RemotedbUserInterface);

    // Cross reference.
    $entity->account = $account;
    $account->remotedb_user = $entity;

    return $entity;
  }

  /**
   * {@inheritdoc}
   */
  public function toAccount(RemotedbUserInterface $entity): UserInterface {
    $entity_values = $entity->toArray();
    $remote_uid = $entity_values['uid'] ?? NULL;
    $remote_name = $entity_values['name'] ?? NULL;
    $remote_mail = $entity_values['mail'] ?? NULL;

    // First, get account from local database, if it exists.
    // First find by remotedb_uid, then by name and finally by mail.
    $account = NULL;
    $search = [
      'remotedb_uid' => $remote_uid,
      'name' => $remote_name,
      'mail' => $remote_mail,
    ];
    foreach ($search as $key => $value) {
      $users = $this->getUserStorage()->loadByProperties([$key => $value]);
      if ($users !== []) {
        $account = reset($users);
        break;
      }
    }

    // Check if this account is already linked to a remote account. If so, we
    // should not suddenly link it to an other account.
    if ($account instanceof UserInterface) {
      $existing_remotedb_uid = $account->get('remotedb_uid')->value;
      if ($existing_remotedb_uid !== NULL && $existing_remotedb_uid !== '' && $existing_remotedb_uid !== 0 && $existing_remotedb_uid !== '0' && $existing_remotedb_uid != $remote_uid) {
        $vars = [
          '@uid' => $account->id(),
          '@remotedb_uid' => $remote_uid,
        ];
        throw new RemotedbExistingUserException(t('Failed to synchronize the remote user. The remote user @remotedb_uid conflicts with local user @uid.', $vars));
      }
    }

    // Name and mail must be unique. If an account was found, make sure that no
    // other account exists that has either the name or the mail address from
    // the remote account.
    if ($account instanceof UserInterface) {
      $search = [
        'name' => $remote_name,
        'mail' => $remote_mail,
      ];
      foreach ($search as $key => $value) {
        $users = $this->getUserStorage()->loadByProperties([$key => $value]);
        if ($users !== []) {
          $account2 = reset($users);
          if ($account->id() != $account2->id()) {
            // We have a conflict here.
            $vars = [
              '@uid1' => $account->id(),
              '@uid2' => $account2->id(),
              '@remotedb_uid' => $remote_uid,
            ];
            throw new RemotedbExistingUserException(t('Failed to synchronize the remote user. The remote user @remotedb_uid conflicts with local users @uid1 and @uid2.', $vars));
          }
        }
      }
    }

    // Construct values to set on the local account.
    $values = $entity_values;
    // The remote user's uid should not overwrite the local user's uid,
    // but instead be saved as 'remotedb_uid'.
    $values['remotedb_uid'] = $values['uid'];
    unset($values['uid']);

    if (!$account instanceof UserInterface) {
      // No account found, create a new user.
      $account = $this->getUserStorage()->create($values);

      // Special case for password.
      if (isset($values['pass']) && $values['pass'] !== '') {
        $account->pass->value = $values['pass'];
        $account->pass->pre_hashed = TRUE;
      }
    }
    else {
      // Update user account.
      $update_props = $this->config->get('sync_properties');
      if (!is_array($update_props)) {
        $update_props = [];
      }
      foreach ($update_props as $key) {
        if (!is_string($key) || $key === '') {
          continue;
        }
        if (isset($values[$key])) {
          if ($key === 'pass') {
            // Setting the hashed password requires a special case.
            $account->pass->value = $values['pass'];
            $account->pass->pre_hashed = TRUE;
          }
          else {
            $account->set($key, $values[$key]);
          }
        }
      }

      // Always set remotedb_uid.
      $account->set('remotedb_uid', $values['remotedb_uid']);
    }

    // Cross reference.
    $entity->account = $account;
    $account->remotedb_user = $entity;

    // Set flag that account should *not* be send back to the remote database
    // again.
    $account->from_remotedb = TRUE;

    return $account;
  }

  /**
   * {@inheritdoc}
   */
  public function authenticate(string $name, string $pass): int|bool {
    $result = $this->sendRequest('dbuser.authenticate', [$name, $pass]);
    if (is_int($result)) {
      return $result;
    }
    if (is_numeric($result)) {
      return (int) $result;
    }
    if (is_bool($result)) {
      return $result;
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function validateName(string $name, UserInterface $account): bool {
    if ($account->getAccountName() == $name) {
      // The username did not change. No need to validate.
      return TRUE;
    }

    $remote_account = $this->loadBy($name, self::BY_NAME);
    if (!$remote_account instanceof RemotedbUserInterface) {
      // Name is not taken yet.
      return TRUE;
    }

    $remotedb_uid = $account->hasField('remotedb_uid') ? $account->get('remotedb_uid')->value : NULL;
    if ($remotedb_uid === NULL || $remotedb_uid === '' || $remotedb_uid === 0 || $remotedb_uid === '0') {
      // This could be a valid case, but only if user name and mail exactly
      // match.
      if ($name == $remote_account->name && $account->getEmail() == $remote_account->mail) {
        return TRUE;
      }
    }
    elseif ($remotedb_uid == $remote_account->uid) {
      // Accounts match.
      return TRUE;
    }

    // In all other cases, name is already taken!
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function validateMail(string $mail, UserInterface $account): bool {
    if ($account->getEmail() == $mail) {
      // The mail address did not change. No need to validate.
      return TRUE;
    }

    $remote_account = $this->loadBy($mail, self::BY_MAIL);
    if (!$remote_account instanceof RemotedbUserInterface) {
      // Mail address is not taken yet.
      return TRUE;
    }

    $remotedb_uid = $account->hasField('remotedb_uid') ? $account->get('remotedb_uid')->value : NULL;
    if ($remotedb_uid === NULL || $remotedb_uid === '' || $remotedb_uid === 0 || $remotedb_uid === '0') {
      // This could be a valid case, but only if user name and mail exactly
      // match.
      if ($account->getAccountName() == $remote_account->name && $mail == $remote_account->mail) {
        return TRUE;
      }
    }
    elseif ($remotedb_uid == $remote_account->uid) {
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
   *
   * @throws \Drupal\remotedb\Exception\RemotedbException
   *   In case the remote database object was not set.
   */
  protected function sendRequest(string $method, array $params = []) {
    if (!$this->remotedb instanceof RemotedbInterface) {
      throw new RemotedbException($this->t('Can not perform request to the remote database, because the RemotedbUserStorage did not receive a remote database object.'));
    }
    try {
      return $this->remotedb->sendRequest($method, $params);
    }
    catch (RemotedbException $e) {
      $e->logError();
      return FALSE;
    }
  }

  /**
   * Gets the user storage handler.
   *
   * @return \Drupal\user\UserStorageInterface
   *   The user storage.
   */
  protected function getUserStorage() {
    return $this->entityTypeManager->getStorage('user');
  }

}
