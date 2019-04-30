<?php

namespace Drupal\Tests\remotedbuser\Functional;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Tests\remotedb\Functional\RemotedbBrowserTestBase;
use Drupal\Tests\remotedbuser\Traits\RemotedbUserCreationTrait;
use Drupal\user\Entity\User;

/**
 * Provides a base class for Remote database User functional tests.
 */
abstract class RemotedbUserBrowserTestBase extends RemotedbBrowserTestBase {

  use RemotedbUserCreationTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'remotedb',
    'remotedb_test',
    'remotedbuser',
    'remotedbuser_test',
  ];

  /**
   * The remote database user storage.
   *
   * @var \Drupal\remotedbuser\Entity\RemotedbUserStorage
   */
  protected $remotedb_user_storage;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->entityTypeManager = \Drupal::entityTypeManager();
    $this->remotedb_user_storage = $this->entityTypeManager->getStorage('remotedb_user');
  }

  /**
   * Overrides DrupalWebTestCase::drupalCreateUser().
   *
   * Checks also if a remote account was created for this user.
   */
  protected function drupalCreateUser(array $permissions = [], $name = NULL, $admin = FALSE, array $values = []) {
    $account = parent::drupalCreateUser($permissions, $name, $admin, $values);

    // Make sure that a remote account exists.
    $this->assertTrue($account->remotedb_uid->value, 'The account is linked to a remote account.');
    $remote_account = $this->remotedb_user_storage->load($account->remotedb_uid->value);
    $this->assertNotNull($remote_account, 'The remote account was created.');
    if (!is_null($remote_account)) {
      $this->assertTrue($account->remotedb_uid->value === $remote_account->uid, 'The account belongs to the expected remote account.');
    }

    return $account;
  }

  /**
   * Asserts that a remote user exists as a local user.
   *
   * @var int $remotedb_uid
   *   The expected remote user uid.
   *
   * @return bool
   *   TRUE on pass, FALSE on fail.
   */
  protected function assertLocalUser($remotedb_uid) {
    $account = NULL;
    $users = \Drupal::entityTypeManager()->getStorage('user')->loadByProperties(['remotedb_uid' => $remotedb_uid]);
    if (!empty($users)) {
      $account = reset($users);
    }
    return $this->assertNotNull($account, 'The remote user exists on the local database.');
  }

  /**
   * Asserts that the user with the given ID is logged in.
   *
   * @param int $uid
   *   The ID of the user that we expect to be logged in.
   */
  protected function assertLoggedIn($uid) {
    $account = User::load($uid);
    $account->sessionId = $this->getSession()->getCookie(\Drupal::service('session_configuration')->getOptions(\Drupal::request())['name']);
    $this->assertTrue($this->drupalUserIsLoggedIn($account), new FormattableMarkup('User %name successfully logged in.', ['%name' => $account->getAccountName()]));

    $this->loggedInUser = $account;
    $this->container->get('current_user')->setAccount($account);
  }

}
