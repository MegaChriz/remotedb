<?php

namespace Drupal\Tests\remotedbuser\Kernel\Entity;

use Drupal\remotedbuser\Exception\RemotedbExistingUserException;
use Drupal\Tests\remotedbuser\Kernel\RemotedbUserKernelTestBase;

/**
 * @coversDefaultClass \Drupal\remotedbuser\Entity\RemotedbUserStorage
 *
 * @group remotedbuser
 */
class RemotedbUserStorageTest extends RemotedbUserKernelTestBase {

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

    $this->remotedb_user_storage = $this->entityTypeManager->getStorage('remotedb_user');
  }

  /**
   * @covers ::fromAccount
   */
  public function testFromAccount() {
    $this->markTestIncomplete();
  }

  /**
   * Tests if a new account can be copied over from the remote database.
   *
   * @covers ::toAccount
   */
  public function testRemoteUserToNewAccount() {
    // Create a remote account.
    $remote_user = $this->createRemoteUser();

    $account = $this->remotedb_user_storage->toAccount($remote_user);
    $account->save();

    // Assert expected values.
    $expected_values = [
      'uid' => 1,
      'name' => $remote_user->name,
      'mail' => $remote_user->mail,
      'status' => $remote_user->status,
      'remotedb_uid' => $remote_user->uid,
    ];
    foreach ($expected_values as $key => $expected_value) {
      $this->assertEquals($expected_value, $account->{$key}->value);
    }
  }

  /**
   * Tests if a remote user can be merged with an existing local account.
   *
   * @covers ::toAccount
   */
  public function testRemoteUserToExistingAccount() {
    // Create an account.
    $account = $this->createUser([
      'name' => 'lorem',
    ]);

    // Create a remote user with the same name.
    $remote_user = $this->createRemoteUser([
      'name' => 'lorem',
    ]);

    // Merge.
    $this->remotedb_user_storage->toAccount($remote_user)
      ->save();

    // Reload original account.
    $account = $this->reloadEntity($account);

    // Assert expected values.
    $expected_values = [
      'name' => 'lorem',
      'mail' => 'lorem@example.com',
      'status' => 1,
      'remotedb_uid' => $remote_user->uid,
    ];
    foreach ($expected_values as $key => $expected_value) {
      $this->assertEquals($expected_value, $account->{$key}->value);
    }
  }

  /**
   * Tests updating name/mail with info from remote account.
   *
   * @covers ::toAccount
   */
  public function testNameAndMailUpdateLocalAccount() {
    // Create a remote user with a certain name.
    $remote_user = $this->createRemoteUser([
      'name' => 'lorem',
    ]);

    // Create an account linked to this remote user.
    $account = $this->createUser([
      'name' => 'ipsum',
      'remotedb_uid' => $remote_user->uid,
    ]);

    // Update local account.
    $this->remotedb_user_storage->toAccount($remote_user)
      ->save();

    // Reload original account.
    $account = $this->reloadEntity($account);
    // Assert expected values.
    $expected_values = [
      'name' => 'lorem',
      'mail' => 'lorem@example.com',
      'status' => 1,
      'remotedb_uid' => $remote_user->uid,
    ];
    foreach ($expected_values as $key => $expected_value) {
      $this->assertEquals($expected_value, $account->{$key}->value);
    }
  }

  /**
   * Tests conflict between local user and remote user based on remote user id.
   *
   * Tests that an exception is thrown when a local user is linked to an
   * existing remote user and a different remote user is tried to be linked
   * to this local user.
   *
   * @covers ::toAccount
   */
  public function testRemoteUidConflict() {
    // Create a local user linked to a certain remote user.
    $account = $this->createUser([
      'name' => 'lorem',
      'remotedb_uid' => 101,
    ]);

    // Create a remote user with the same username, but different ID.
    $remote_user = $this->createRemoteUser([
      'uid' => 102,
      'name' => 'lorem',
    ]);

    $this->setExpectedException(RemotedbExistingUserException::class, 'Failed to synchronize the remote user. The remote user 102 conflicts with local user 1.');
    $this->remotedb_user_storage->toAccount($remote_user);
  }

  /**
   * Tests failing mail update.
   *
   * Tests that an existing local user cannot be updated with a mail address
   * that already belongs to an other local user.
   *
   * @covers ::toAccount
   */
  public function testFailedMailAddressUpdateLocalAccount() {
    // Create two local users.
    $account1 = $this->createUser([
      'name' => 'lorem',
    ]);
    $account2 = $this->createUser([
      'name' => 'ipsum',
    ]);

    // Create a remote user to update account 1.
    $remote_user = $this->createRemoteUser([
      'uid' => 101,
      'name' => 'lorem',
      'mail' => 'ipsum@example.com',
    ]);

    $this->setExpectedException(RemotedbExistingUserException::class, 'Failed to synchronize the remote user. The remote user 101 conflicts with local user 2.');
    $this->remotedb_user_storage->toAccount($remote_user);
  }

  /**
   * Tests failing username update.
   *
   * Tests that an existing local user cannot be updated with an username
   * that already belongs to an other local user.
   *
   * @covers ::toAccount
   */
  public function testFailedUsernameUpdateLocalAccount() {
    // Create two local users.
    $account1 = $this->createUser([
      'name' => 'lorem',
      'remotedb_uid' => 101,
    ]);
    $account2 = $this->createUser([
      'name' => 'ipsum',
    ]);

    // Create a remote user to update account 1.
    $remote_user = $this->createRemoteUser([
      'uid' => 101,
      'name' => 'ipsum',
    ]);
    $this->setExpectedException(RemotedbExistingUserException::class, 'Failed to synchronize the remote user. The remote user 101 conflicts with local user 2.');
    $this->remotedb_user_storage->toAccount($remote_user);
  }

}
