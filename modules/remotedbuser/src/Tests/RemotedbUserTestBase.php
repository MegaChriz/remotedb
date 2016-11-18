<?php

/**
 * @file
 * Contains \Drupal\remotedbuser\Tests\RemotedbUserTestBase.
 */

namespace Drupal\remotedbuser\Tests;

use Drupal\remotedb\Tests\RemotedbTestBase;

abstract class RemotedbUserTestBase extends RemotedbTestBase {
  /**
   * The controller for the remotedb_user entity.
   *
   * @var \Drupal\remotedbuser\Controller\RemotedbUserController.
   */
  protected $controller;

  /**
   * Overrides DrupalWebTestCase::setUp().
   */
  protected function setUp(array $modules = array()) {
    $modules = array_merge($modules, array('remotedbuser', 'remotedbuser_test'));
    parent::setUp($modules);

    // Create controller.
    $this->controller = entity_get_controller('remotedb_user');
  }

  /**
   * Creates a remote user.
   *
   * @param array $values
   *   (optional) The values to use for the remote user.
   *
   * @return \Drupal\remotedbuser\Entity\RemotedbUserInterface
   *   A remote user object.
   */
  protected function remotedbCreateRemoteUser(array $values = array()) {
    static $uid = 2;

    // Generate uid.
    $values['uid'] = ++$uid;
    // Make sure user gets a name.
    if (empty($values['name'])) {
      $values['name'] = $this->randomName();
    }
    // Fill in other default values.
    $values += array(
      'mail' => $values['name'] . '@example.com',
      'status' => 1,
      'pass' => user_password(),
    );

    // Hash password.
    if ($values['pass']) {
      $values['pass_raw'] = $values['pass'];
      $values['pass'] = $this->hashPassword($values['pass']);
    }

    // Create the remotedbuser.
    $account = entity_create('remotedb_user', $values);
    $account->save();

    // Check if this user can be retrieved by the controller.
    $account2 = $this->controller->loadBy($values['uid']);
    $this->assertNotNull($account2, 'The created remotedb_user can be retrieved by the controller.');
    if (!is_null($account2)) {
      $this->assertTrue($account->uid === $account2->uid, 'The retrieved remote user equals the created remotedb_user.');
    }

    return $account;
  }

  /**
   * Overrides DrupalWebTestCase::drupalCreateUser().
   *
   * Checks also if a remote account was created for this user.
   */
  protected function drupalCreateUser(array $permissions = array()) {
    $account = parent::drupalCreateUser($permissions);

    // Make sure a remote account exists.
    $this->assertTrue($account->remotedb_uid, 'The account is linked to a remote account.');
    $remote_account = $this->controller->loadBy($account->remotedb_uid);
    $this->assertNotNull($remote_account, 'The remote account was created.');
    if (!is_null($remote_account)) {
      $this->assertTrue($account->remotedb_uid === $remote_account->uid, 'The account belongs to the expected remote account.');
    }

    return $account;
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
    require_once DRUPAL_ROOT . '/includes/password.inc';
    return user_hash_password($pass);
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
    $users = user_load_multiple(array(), array('remotedb_uid' => $remotedb_uid));
    if (!empty($users)) {
      $account = reset($users);
    }
    return $this->assertNotNull($account, 'The remote user exists on the local database.');
  }
}
