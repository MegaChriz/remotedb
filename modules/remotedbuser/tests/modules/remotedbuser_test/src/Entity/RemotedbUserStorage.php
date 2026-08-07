<?php

namespace Drupal\remotedbuser_test\Entity;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Cache\MemoryCache\MemoryCacheInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Password\PasswordInterface;
use Drupal\Core\State\StateInterface;
use Drupal\remotedb\Entity\RemotedbInterface;
use Drupal\remotedb\Entity\RemotedbStorageInterface;
use Drupal\remotedb_test\Entity\MockRemotedb;
use Drupal\remotedbuser\Entity\RemotedbUserStorage as OriginalRemotedbUserStorage;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Overrides default storage class for remotedb_user entity type.
 */
class RemotedbUserStorage extends OriginalRemotedbUserStorage {

  /**
   * State service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * Password service.
   *
   * @var \Drupal\Core\Password\PasswordInterface
   */
  protected $password;

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
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service.
   * @param \Drupal\Core\Password\PasswordInterface $password
   *   The password checking service.
   * @param \Drupal\Core\Cache\MemoryCache\MemoryCacheInterface $memory_cache
   *   The memory cache backend.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle info.
   * @param \Drupal\remotedb\Entity\RemotedbInterface|null $remotedb
   *   (optional) The remote database in which the remote users are stored.
   */
  public function __construct(
    EntityTypeInterface $entity_type,
    EntityFieldManagerInterface $entity_field_manager,
    CacheBackendInterface $cache,
    EntityTypeManagerInterface $entity_type_manager,
    ImmutableConfig $config,
    StateInterface $state,
    PasswordInterface $password,
    MemoryCacheInterface $memory_cache,
    EntityTypeBundleInfoInterface $entity_type_bundle_info,
    ?RemotedbInterface $remotedb = NULL,
  ) {
    $this->state = $state;
    $this->password = $password;

    // Set remotedb mock.
    $remotedb_storage = $entity_type_manager->getStorage('remotedb');
    if (!$remotedb_storage instanceof RemotedbStorageInterface) {
      throw new \LogicException('Expected remotedb storage to implement RemotedbStorageInterface.');
    }
    $remotedb = $remotedb_storage->create([]);
    if (!$remotedb instanceof MockRemotedb) {
      throw new \LogicException('Expected remotedb entity to be an instance of MockRemotedb.');
    }
    $remotedb->setCallback([$this, 'remotedbCallback']);

    parent::__construct($entity_type, $entity_field_manager, $cache, $entity_type_manager, $config, $memory_cache, $entity_type_bundle_info, $remotedb);
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type): static {
    return new static(
      $entity_type,
      $container->get('entity_field.manager'),
      $container->get('cache.entity'),
      $container->get('entity_type.manager'),
      $container->get('config.factory')->get('remotedbuser.settings'),
      $container->get('state'),
      $container->get('password'),
      $container->get('entity.memory_cache'),
      $container->get('entity_type.bundle.info'),
      $container->get('remotedbuser.configuration')->getDefault()
    );
  }

  /**
   * Gets all accounts.
   *
   * @return array
   *   An array of accounts.
   */
  public function getRemoteAccounts(): array {
    $accounts = $this->state->get('remotedbuser_test_accounts', []);
    return is_array($accounts) ? $accounts : [];
  }

  /**
   * Save accounts.
   *
   * @param array $accounts
   *   The accounts to save in database.
   */
  private function setRemoteAccounts(array $accounts): void {
    $this->state->set('remotedbuser_test_accounts', $accounts);
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
  public function remotedbCallback(string $method, array $params): mixed {
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

      default:
        return NULL;
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
   * @return array|null
   *   An array of user data if found, NULL otherwise.
   */
  private function dbuserRetrieve(mixed $id, string $by): ?array {
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
   * @return int|false
   *   The remote user uid or FALSE if saving failed.
   */
  private function dbuserSave(array $user_data): int|false {
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
      if ($account !== NULL) {
        // An account is found.
        $accounts[$key] = $account;
      }
    }

    // Use the first found account.
    $account = $accounts !== [] ? reset($accounts) : FALSE;

    if (count($accounts) > 1) {
      // Multiple accounts were found. Stop when different.
      foreach ($accounts as $double_account) {
        if ($double_account !== $account) {
          // Multiple different accounts found. Stop.
          return FALSE;
        }
      }
    }

    if ($account === FALSE) {
      // No existing account was found, thus create a new user.
      if (!$this->hasRemoteUid($user_data)) {
        // Generate uid if it doesn't have one.
        $user_data['uid'] = count($user_data) + 1000;
      }
    }
    else {
      // An existing account was found. Update it.
      // Local users without remotedb_uid may pass uid => NULL via toArray(),
      // which must not overwrite the existing remote uid (PHP 8.5 also
      // deprecates null as an array offset).
      $existing_uid = $account['uid'] ?? NULL;
      $user_data = array_merge($account, $user_data);
      if (!$this->hasRemoteUid($user_data)) {
        $user_data['uid'] = $existing_uid;
      }
    }

    if (!$this->hasRemoteUid($user_data)) {
      $user_data['uid'] = count($this->getRemoteAccounts()) + 1000;
    }

    $accounts = $this->getRemoteAccounts();
    $accounts[$user_data['uid']] = $user_data;
    $this->setRemoteAccounts($accounts);

    return $user_data['uid'];
  }

  /**
   * Checks whether user data contains a usable remote uid.
   *
   * @param array $user_data
   *   The remote user data.
   *
   * @return bool
   *   TRUE if a non-empty uid is present.
   */
  private function hasRemoteUid(array $user_data): bool {
    return isset($user_data['uid']) && $user_data['uid'] !== '';
  }

  /**
   * Authenticates a user.
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
  private function dbuserAuthenticate(string $name, string $password): int|false {
    $user_data = $this->dbuserRetrieve($name, 'name');

    // No account found? Return FALSE.
    if ($user_data === NULL) {
      return FALSE;
    }

    if ($this->password->check($password, $user_data['pass'])) {
      return $user_data['uid'];
    }

    // In all other cases, the password is invalid.
    return FALSE;
  }

}
