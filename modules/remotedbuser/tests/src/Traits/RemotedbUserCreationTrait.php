<?php

namespace Drupal\Tests\remotedbuser\Traits;

use Drupal\Core\Session\AccountInterface;
use Drupal\remotedbuser\Entity\RemotedbUser;

/**
 * Provides methods to create remote databases with default settings.
 *
 * This trait is meant to be used only by test classes.
 */
trait RemotedbUserCreationTrait {

  /**
   * Creates a remote database entity.
   *
   * @param array $values
   *   (optional) An associative array of values for the remote user entity.
   *
   * @return \Drupal\remotedbuser\Entity\RemotedbUserInterface
   *   The created remote user entity.
   */
  protected function createRemoteUser(array $values = []) {
    $uid = &drupal_static(__METHOD__, 2);

    // Generate uid.
    if (!isset($values['uid'])) {
      $values['uid'] = ++$uid;
    }
    // Make sure that the user gets a name.
    if (empty($values['name'])) {
      $values['name'] = $this->randomMachineName();
    }
    // Fill in other default values.
    $values += [
      'mail' => $values['name'] . '@example.com',
      'status' => 1,
      'pass' => \Drupal::service('password_generator')->generate(),
    ];

    // Hash password.
    if ($values['pass']) {
      $values['pass_raw'] = $values['pass'];
      $values['pass'] = $this->hashPassword($values['pass']);
    }

    $remote_user = RemotedbUser::create($values);
    $remote_user->save();

    return $remote_user;
  }

  /**
   * Hashes a password.
   *
   * @param string $pass
   *   The plain password to hash.
   *
   * @return string
   *   The hashed password.
   */
  protected function hashPassword($pass) {
    return $this->container->get('password')->hash($pass);
  }

  /**
   * Creates a dummy account.
   *
   * The dummy account is mocked from '\Drupal\Core\Session\AccountInterface'.
   *
   * @param string $name
   *   The username for the dummy account.
   *
   * @return \Drupal\Core\Session\AccountInterface
   *   A mocked account object.
   */
  protected function createDummyAccount($name): AccountInterface {
    return new MockAccount([
      'name' => $name,
    ]);
  }

}

/**
 * Mock implementation of AccountInterface with configurable return values.
 */
class MockAccount implements AccountInterface {

  /**
   * The user ID.
   *
   * @var int
   */
  protected int $id = 1;

  /**
   * The user roles.
   *
   * @var string[]
   */
  protected array $roles = ['authenticated'];

  /**
   * The user's permissions.
   *
   * @var string[]
   */
  protected array $permissions = [];

  /**
   * Whether the account is authenticated.
   *
   * @var bool
   */
  protected bool $isAuthenticated = TRUE;

  /**
   * Whether the account is anonymous.
   *
   * @var bool
   */
  protected bool $isAnonymous = FALSE;

  /**
   * The preferred language code.
   *
   * @var string
   */
  protected string $preferredLangcode = 'en';

  /**
   * The preferred admin language code.
   *
   * @var string
   */
  protected string $preferredAdminLangcode = 'en';

  /**
   * The account login name.
   *
   * @var string
   */
  protected string $name = 'mock_user';

  /**
   * The display name.
   *
   * @var string
   */
  protected string $displayName = 'Mock User';

  /**
   * The user email address.
   *
   * @var string|null
   */
  protected ?string $email = 'mock@example.com';

  /**
   * The account timezone.
   *
   * @var string
   */
  protected string $timezone = 'UTC';

  /**
   * The timestamp of the last access.
   *
   * @var int
   */
  protected int $lastAccessedTime;

  /**
   * Temporary property used by drupalLogin().
   */
  protected string $passRaw;

  /**
   * Temporary property used by drupalLogin().
   */
  protected ?string $sessionId;

  /**
   * Constructor with optional overrides.
   *
   * @param array $overrides
   *   An associative array of property names and values to override defaults.
   */
  public function __construct(array $overrides = []) {
    foreach ($overrides as $property => $value) {
      if (property_exists($this, $property)) {
        $this->$property = $value;
      }
    }

    // Set default last accessed time if not explicitly provided.
    if (!isset($overrides['lastAccessedTime'])) {
      $this->lastAccessedTime = time();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function id() {
    return $this->id;
  }

  /**
   * {@inheritdoc}
   */
  public function getRoles($exclude_locked_roles = FALSE) {
    if ($exclude_locked_roles) {
      return array_filter($this->roles, fn($role) => !in_array($role, [self::ANONYMOUS_ROLE, self::AUTHENTICATED_ROLE]));
    }
    return $this->roles;
  }

  /**
   * {@inheritdoc}
   */
  public function hasPermission(/* string */$permission) {
    return in_array($permission, $this->permissions);
  }

  /**
   * {@inheritdoc}
   */
  public function isAuthenticated() {
    return $this->isAuthenticated;
  }

  /**
   * {@inheritdoc}
   */
  public function isAnonymous() {
    return $this->isAnonymous;
  }

  /**
   * {@inheritdoc}
   */
  public function getPreferredLangcode($fallback_to_default = TRUE) {
    return $this->preferredLangcode;
  }

  /**
   * {@inheritdoc}
   */
  public function getPreferredAdminLangcode($fallback_to_default = TRUE) {
    return $this->preferredAdminLangcode;
  }

  /**
   * {@inheritdoc}
   */
  public function getAccountName() {
    return $this->name;
  }

  /**
   * {@inheritdoc}
   */
  public function getDisplayName() {
    return $this->displayName;
  }

  /**
   * {@inheritdoc}
   */
  public function getEmail() {
    return $this->email;
  }

  /**
   * {@inheritdoc}
   */
  public function getTimezone() {
    return $this->timezone;
  }

  /**
   * {@inheritdoc}
   */
  public function getLastAccessedTime() {
    return $this->lastAccessedTime;
  }

  /**
   * Magic setter to allow dynamic property assignment.
   *
   * @param string $name
   *   The property name.
   * @param mixed $value
   *   The value to assign.
   */
  public function __set(string $name, mixed $value): void {
    if (property_exists($this, $name)) {
      $this->$name = $value;
    }
  }

  /**
   * Magic getter to allow dynamic property access.
   *
   * @param string $name
   *   The property name.
   *
   * @return mixed|null
   *   The value of the property, or null if it doesn't exist.
   */
  public function __get(string $name): mixed {
    return property_exists($this, $name) ? $this->$name : null;
  }

  /**
   * Magic isset to check if a property exists and is not null.
   *
   * @param string $name
   *   The property name.
   *
   * @return bool
   *   TRUE if the property exists and is not null, FALSE otherwise.
   */
  public function __isset(string $name): bool {
    return property_exists($this, $name) && isset($this->$name);
  }

  /**
   * Magic unset to nullify a property if it exists.
   *
   * @param string $name
   *   The property name.
   */
  public function __unset(string $name): void {
    if (property_exists($this, $name)) {
      $this->$name = NULL;
    }
  }

}
