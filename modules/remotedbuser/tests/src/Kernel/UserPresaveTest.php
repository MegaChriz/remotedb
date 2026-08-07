<?php

namespace Drupal\Tests\remotedbuser\Kernel;

use Drupal\remotedbuser_test\Entity\RemotedbUserStorage as TestRemotedbUserStorage;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests user presave synchronization with the remote database.
 *
 * @group remotedbuser
 */
#[RunTestsInSeparateProcesses]
#[Group('remotedbuser')]
class UserPresaveTest extends RemotedbUserKernelTestBase {

  /**
   * Tests that a local username change is synced to the remote database.
   */
  public function testLocalNameChangeSyncsToRemote(): void {
    $account = $this->createUser([], NULL, FALSE, [
      'name' => 'old_name',
      'mail' => 'old_name@example.com',
    ]);
    $account->save();

    $this->assertNotEmpty($account->get('remotedb_uid')->value);

    $remote_uid = $account->get('remotedb_uid')->value;
    $remote_account = $this->entityTypeManager->getStorage('remotedb_user')->load($remote_uid);
    $this->assertNotNull($remote_account);
    $this->assertEquals('old_name', $remote_account->name);

    $account->set('name', 'new_name');
    $account->save();

    $storage = $this->entityTypeManager->getStorage('remotedb_user');
    if (!$storage instanceof TestRemotedbUserStorage) {
      $this->fail('Expected remotedb_user test storage.');
    }

    $remote_accounts = $storage->getRemoteAccounts();
    $this->assertArrayHasKey($remote_uid, $remote_accounts);
    $this->assertEquals('new_name', $remote_accounts[$remote_uid]['name']);

    $remote_account = $storage->load($remote_uid);
    $this->assertNotNull($remote_account);
    $this->assertEquals('new_name', $remote_account->name);
  }

}
