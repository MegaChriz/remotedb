<?php

namespace Drupal\Tests\remotedbuser\Kernel\Entity;

use Drupal\remotedb\Exception\RemotedbException;
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
  protected $remotedbUserStorage;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->remotedbUserStorage = $this->entityTypeManager->getStorage('remotedb_user');
  }

  /**
   * @covers ::fromAccount
   */
  public function testFromAccount() {
    // Create a local account.
    $account = $this->createUser([
      'name' => 'lorem',
      'from_remotedb' => TRUE,
    ]);

    // Convert to a remote account.
    $remote_user = $this->remotedbUserStorage->fromAccount($account);

    // Assert expected values.
    $expected_values = [
      'name' => 'lorem',
      'mail' => 'lorem@example.com',
      'status' => 1,
    ];
    foreach ($expected_values as $key => $expected_value) {
      $this->assertEquals($expected_value, $remote_user->{$key});
    }

    // Assert non-existing values.
    $this->assertNull($remote_user->uid);
    $this->assertNull($remote_user->is_new);
  }

  /**
   * @covers ::fromAccount
   */
  public function testFromAccountWithExistingRemoteUser() {
    // Create a local account.
    $account = $this->createUser([
      'remotedb_uid' => 101,
    ]);

    // Convert to a remote account.
    $remote_user = $this->remotedbUserStorage->fromAccount($account);

    // Assert that the remote user now has an ID set.
    $this->assertEquals(101, $remote_user->uid);
    $this->assertFalse($remote_user->is_new);

    // Assert non-existing values.
    $this->assertNull($remote_user->remotedb_uid);
  }

  /**
   * Tests that a local user without mail address cannot be converted.
   *
   * @covers ::fromAccount
   */
  public function testFailWithoutMail() {
    // Create a local account without mail address.
    $account = $this->createUser([
      'mail' => NULL,
      'from_remotedb' => TRUE,
    ]);

    // Attempt to convert to a remote account.
    $this->setExpectedException(RemotedbException::class, "The account cannot be saved in the remote database, because it doesn't have a mail address.");
    $remote_user = $this->remotedbUserStorage->fromAccount($account);
  }

  /**
   * Tests if a new account can be copied over from the remote database.
   *
   * @covers ::toAccount
   */
  public function testRemoteUserToNewAccount() {
    // Create a remote account.
    $remote_user = $this->createRemoteUser();

    $account = $this->remotedbUserStorage->toAccount($remote_user);
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
      'from_remotedb' => TRUE,
    ]);

    // Create a remote user with the same name.
    $remote_user = $this->createRemoteUser([
      'name' => 'lorem',
    ]);

    // Merge.
    $this->remotedbUserStorage->toAccount($remote_user)
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
    $this->remotedbUserStorage->toAccount($remote_user)
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
    $this->remotedbUserStorage->toAccount($remote_user);
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
      'from_remotedb' => TRUE,
    ]);
    $account2 = $this->createUser([
      'name' => 'ipsum',
      'from_remotedb' => TRUE,
    ]);

    // Create a remote user to update account 1.
    $remote_user = $this->createRemoteUser([
      'uid' => 101,
      'name' => 'lorem',
      'mail' => 'ipsum@example.com',
    ]);

    $this->setExpectedException(RemotedbExistingUserException::class, 'Failed to synchronize the remote user. The remote user 101 conflicts with local users 1 and 2.');
    $this->remotedbUserStorage->toAccount($remote_user);
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
      'from_remotedb' => TRUE,
    ]);
    $account2 = $this->createUser([
      'name' => 'ipsum',
      'from_remotedb' => TRUE,
    ]);

    // Create a remote user to update account 1.
    $remote_user = $this->createRemoteUser([
      'uid' => 101,
      'name' => 'ipsum',
    ]);
    $this->setExpectedException(RemotedbExistingUserException::class, 'Failed to synchronize the remote user. The remote user 101 conflicts with local users 1 and 2.');
    $this->remotedbUserStorage->toAccount($remote_user);
  }

}
