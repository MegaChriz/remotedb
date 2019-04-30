<?php

namespace Drupal\Tests\remotedbuser\Functional;

/**
 * Tests if users can be imported from the remote database.
 *
 * @group remotedbuser
 */
class UserImportTest extends RemotedbUserBrowserTestBase {

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Create an admin user.
    $admin = $this->drupalCreateUser(['remotedb.administer']);
    $this->drupalLogin($admin);
  }

  /**
   * Tests that two users from the remote database can be imported without errors.
   */
  public function testBasic() {
    // Create two remote users.
    $remote_account1 = $this->remotedbCreateRemoteUser();
    $remote_account2 = $this->remotedbCreateRemoteUser();

    // Try to import these users using the admin form.
    $edit = [
      'user' => implode("\n", [
        $remote_account1->mail,
        $remote_account2->mail,
      ]),
    ];
    $this->drupalPostForm('admin/config/services/remotedb/user/get', $edit, 'Get');

    // Assert messages.
    $this->assertText(format_string('User account @name copied over from the remote database.', [
      '@name' => $remote_account1->name,
    ]));
    $this->assertText(format_string('User account @name copied over from the remote database.', [
      '@name' => $remote_account2->name,
    ]));
    $this->assertNoText('No remote user found');
    $this->assertNoText('Failed to synchronize the remote user');

    // Assert that the accounts exist in the local database.
    $account1 = user_load_by_name($remote_account1->name);
    $this->assertNotNull($account1, 'Account 1 exists on the local database.');
    $this->assertEqual($account1->remotedb_uid, $remote_account1->uid, 'Account 1 got a remote database user id.');
    $account2 = user_load_by_name($remote_account2->name);
    $this->assertNotNull($account2, 'Account 2 exists on the local database.');
    $this->assertEqual($account2->remotedb_uid, $remote_account2->uid, 'Account 2 got a remote database user id.');
  }

  /**
   * Tests that importing non-existing users do not abort the process.
   */
  public function testWithFailures() {
    // Create two remote users.
    $remote_account1 = $this->remotedbCreateRemoteUser();
    $remote_account2 = $this->remotedbCreateRemoteUser();

    // For the first remote account, create an user that points to a non-existing remote user.
    $account_edit = [
      'uid' => 18,
      'name' => $remote_account1->name,
      'mail' => $this->randomName() . '@example.com',
      'pass' => $this->hashPassword('abc'),
      'status' => 1,
      'remotedb_uid' => 1200,
    ];
    \Drupal::database()->insert('users')->fields($account_edit)->execute();

    // Try to import users using the admin form.
    $edit = [
      'user' => implode("\n", [
        'non_existent@example.com',
        $remote_account1->mail,
        $remote_account2->mail,
      ]),
    ];
    $this->drupalPostForm('admin/config/services/remotedb/user/get', $edit, 'Get');

    // Assert messages.
    $this->assertText('No remote user found for non_existent@example.com.');
    $this->assertText(format_string('Failed to synchronize the remote user. The remote user @remotedb_uid conflicts with local user @uid.', [
      '@remotedb_uid' => $remote_account1->uid,
      '@uid' => 18,
    ]));
    $this->assertText(format_string('User account @name copied over from the remote database.', [
      '@name' => $remote_account2->name,
    ]));

    // Assert that account 2 exists in the local database.
    $account2 = user_load_by_name($remote_account2->name);
    $this->assertNotNull($account2, 'Account 2 exists on the local database.');
    $this->assertEqual($account2->remotedb_uid, $remote_account2->uid, 'Account 2 got a remote database user id.');
  }

  /**
   * Tests that all users get through the import process (which is divided in multiple chunks).
   */
  public function testImportManyUsers() {
    $mails = [];
    for ($i = 0; $i < 25; $i++) {
      $mails[] = $this->randomName() . '@example.com';
    }

    // Try to import users using the admin form.
    $edit = [
      'user' => implode("\n", $mails),
    ];
    $this->drupalPostForm('admin/config/services/remotedb/user/get', $edit, 'Get');
    foreach ($mails as $mail) {
      $this->assertText(format_string('No remote user found for @user.', [
        '@user' => $mail,
      ]));
    }
  }

}
