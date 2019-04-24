<?php

namespace Drupal\Tests\remotedbuser\Kernel\Entity;

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

    $this->remotedb_user_storage = \Drupal::entityTypeManager()->getStorage('remotedb_user');
  }

  /**
   * @covers ::fromAccount
   */
  public function _testFromAccount() {
    // @todo
  }

  /**
   * Tests if a new account can be copied over from the remote database.
   *
   * @covers ::toAccount
   */
  public function testToAccount() {
    // Create a remote account.
    $remote_user = $this->createRemoteUser();

    $account = $this->remotedb_user_storage->toAccount($remote_user);
    $account->save();

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

}
