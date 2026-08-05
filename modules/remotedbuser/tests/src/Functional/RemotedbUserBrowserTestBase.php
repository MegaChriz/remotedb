<?php

namespace Drupal\Tests\remotedbuser\Functional;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\remotedbuser\Entity\RemotedbUserStorageInterface;
use Drupal\remotedbuser_test\Entity\RemotedbUserStorage as TestRemotedbUserStorage;
use Drupal\Tests\remotedb\Functional\RemotedbBrowserTestBase;
use Drupal\Tests\remotedbuser\Traits\RemotedbUserCreationTrait;
use Drupal\user\Entity\User;
use Drupal\user\UserInterface;

/**
 * Provides a base class for Remote database User functional tests.
 */
abstract class RemotedbUserBrowserTestBase extends RemotedbBrowserTestBase {

  use RemotedbUserCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'remotedb',
    'remotedb_test',
    'remotedbuser',
    'remotedbuser_test',
  ];

  /**
   * The entity type manager.
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * ID of role that allows users to change their own account.
   *
   * @var int
   */
  protected $roleId;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->container->get('entity_type.manager');
    $this->roleId = $this->createRole(['change own username', 'cancel account']);
    $this->useOneTimeLoginLinks = FALSE;
  }

  /**
   * Gets the remotedb_user storage handler.
   */
  protected function remotedbUserStorage(): RemotedbUserStorageInterface {
    $storage = $this->entityTypeManager->getStorage('remotedb_user');
    if (!$storage instanceof RemotedbUserStorageInterface) {
      throw new \LogicException('Expected remotedb_user storage to implement RemotedbUserStorageInterface.');
    }
    return $storage;
  }

  /**
   * Gets the remotedb_user test storage handler.
   */
  protected function remotedbUserTestStorage(): TestRemotedbUserStorage {
    $storage = $this->remotedbUserStorage();
    if (!$storage instanceof TestRemotedbUserStorage) {
      throw new \LogicException('Expected remotedb_user storage to be the remotedbuser_test storage.');
    }
    return $storage;
  }

  /**
   * Overrides UserCreationTrait::drupalCreateUser().
   *
   * Checks also if a remote account was created for this user.
   */
  protected function drupalCreateUser(array $permissions = [], $name = NULL, $admin = FALSE, array $values = []): UserInterface {
    $account = parent::drupalCreateUser($permissions, $name, $admin, $values);

    // Make sure that a remote account exists.
    $this->assertNotEmpty($account->remotedb_uid->value, 'The account is linked to a remote account.');
    $remote_account = $this->remotedbUserStorage()->load($account->remotedb_uid->value);
    $this->assertNotNull($remote_account, 'The remote account was created.');
    if (!is_null($remote_account)) {
      $this->assertTrue($account->remotedb_uid->value === $remote_account->uid, 'The account belongs to the expected remote account.');
    }

    return $account;
  }

  /**
   * Loads a single user by name.
   *
   * @param string $name
   *   The name to load a user by.
   *
   * @return \Drupal\user\UserInterface|null
   *   A user account if found. Null otherwise.
   */
  protected function loadUserByName(string $name): ?UserInterface {
    $accounts = $this->container->get('entity_type.manager')
      ->getStorage('user')
      ->loadByProperties([
        'name' => $name,
      ]);

    if ($accounts === []) {
      return NULL;
    }

    $account = reset($accounts);
    if (!$account instanceof UserInterface) {
      throw new \LogicException(sprintf('Loading user %s did not result into an object of the expected type.', $name));
    }
    return $account;
  }

  /**
   * Asserts that a remote user exists as a local user.
   *
   * @param int $remotedb_uid
   *   The expected remote user uid.
   */
  protected function assertLocalUser(int $remotedb_uid): void {
    $account = NULL;
    $users = \Drupal::entityTypeManager()->getStorage('user')->loadByProperties(['remotedb_uid' => $remotedb_uid]);
    if (!empty($users)) {
      $account = reset($users);
    }
    $this->assertNotNull($account, 'The remote user exists on the local database.');
  }

  /**
   * Asserts that the user with the given ID is logged in.
   *
   * @param int $uid
   *   The ID of the user that we expect to be logged in.
   */
  protected function assertLoggedIn(int $uid): void {
    $account = User::load($uid);
    $account->sessionId = $this->getSession()->getCookie(\Drupal::service('session_configuration')->getOptions(\Drupal::request())['name']);
    $this->assertTrue($this->drupalUserIsLoggedIn($account), new FormattableMarkup('User %name successfully logged in.', ['%name' => $account->getAccountName()]));

    $this->loggedInUser = $account;
    $this->container->get('current_user')->setAccount($account);
  }

}
