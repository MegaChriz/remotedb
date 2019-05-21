<?php

namespace Drupal\Tests\remotedbuser\Functional;

use Drupal\remotedbuser\RemotedbUserAuthenticationInterface;
use Drupal\user\Entity\User;

/**
 * Tests behavior on name collisions between local and remote users.
 *
 * @group remotedbuser
 */
class UserExistingTest extends RemotedbUserBrowserTestBase {

  /**
   * Tests linking local user with remote user based on username.
   *
   * When an existing local user is not yet linked to a remote user, test if it
   * can be linked to a remote user that does have the same username, but not
   * the same mail address.
   */
  public function testExistingName() {
    // Create a remote user.
    $remote_account = $this->createRemoteUser();

    // Create a local user that has the same username as an existing remote
    // account, but do not link it to that account yet.
    $account = User::create([
      'name' => $remote_account->name,
      'mail' => $this->randomMachineName() . '@example.com',
      'pass' => 'abc',
      'status' => 1,
    ]);
    // Prevent this account from exchanging it with the remote database.
    $account->from_remotedb = TRUE;
    $account->save();

    // Verify that this user is not linked to a remote account yet.
    $account = $this->reloadEntity($account);
    $this->assertEquals(0, $account->remotedb_uid->value, 'The account is not linked to a remote account yet.');
    // Verify that this user has still its local mail address.
    $this->assertNotEquals($remote_account->mail, $account->getEmail(), 'The local users mail address does not equal the remote users mail address.');

    // Try to login as this user using remote login data.
    $dummy_account = $this->createDummyAccount($remote_account->name);
    $dummy_account->passRaw = $remote_account->pass_raw;
    $this->drupalLogin($dummy_account);

    // Verify that the mail addresses are equal now.
    $account = $this->reloadEntity($account);
    $this->assertEquals($remote_account->mail, $account->getEmail(), 'The local users mail address has changed.');
  }

  /**
   * Tests linking local user with remote user based on mail address.
   *
   * When an existing local user is not yet linked to a remote user, test if it
   * can be linked to a remote user that does have the same mail address, but
   * not the same mail username.
   */
  public function testExistingMail() {
    // Create a remote user.
    $remote_account = $this->createRemoteUser();

    // Create a local user that has the same username as an existing remote
    // account, but do not link it to that account yet.
    $account = User::create([
      'name' => $this->randomMachineName(),
      'mail' => $remote_account->mail,
      'pass' => 'abc',
      'status' => 1,
    ]);
    // Prevent this account from exchanging it with the remote database.
    $account->from_remotedb = TRUE;
    $account->save();

    // Verify that this user is not linked to a remote account yet.
    $account = $this->reloadEntity($account);
    $this->assertEquals(0, $account->remotedb_uid->value, 'The account is not linked to a remote account yet.');
    // Verify that this user has still its local username.
    $this->assertNotEqual($remote_account->name, $account->getAccountName(), 'The local username does not equal the remote username.');

    // Try to login as this user using remote login data.
    $dummy_account = $this->createDummyAccount($remote_account->name);
    $dummy_account->passRaw = $remote_account->pass_raw;
    $this->drupalLogin($dummy_account);

    // Verify the username and mail address from the local user are equal to
    // that of the remote user.
    $account = $this->reloadEntity($account);
    $this->assertEqual($remote_account->name, $account->getAccountName(), 'The local username has changed.');
    $this->assertEqual($remote_account->mail, $account->getEmail(), 'The local users mail address still equals the remote users mail address.');
  }

  /**
   * Tests that two local users don't receive the same mail address.
   *
   * Scenario:
   * - Local user A has the same username as remote user A, but not the same
   *   mail address.
   * - Local user B has the same mail address as remote user A, but not the same
   *   username.
   *
   * Logging in via the remote database should fail as the system doesn't know
   * to which local account the remote account should be linked.
   */
  public function testExistingNameAndMail() {
    // Create a remote user.
    $remote_account = $this->createRemoteUser();

    // Create two local users. One with the remote user's name and one with the
    // remote user's mail.
    $account1 = User::create([
      'name' => $remote_account->name,
      'mail' => $this->randomMachineName() . '@example.com',
      'pass' => 'abc',
      'status' => 1,
    ]);
    // Prevent this account from exchanging it with the remote database.
    $account1->from_remotedb = TRUE;
    $account1->save();

    $account2 = User::create([
      'name' => $this->randomMachineName(),
      'mail' => $remote_account->mail,
      'pass' => 'abc',
      'status' => 1,
    ]);
    // Prevent this account from exchanging it with the remote database.
    $account2->from_remotedb = TRUE;
    $account2->save();

    // Try to login as the first local user and ensure an error message appears.
    $edit = [
      'name' => $remote_account->name,
      'pass' => $remote_account->pass_raw,
    ];
    $this->drupalPostForm('user/login', $edit, t('Log in'));
    $this->assertText('Another user already exists in the system with the same login name. You should contact the system administrator in order to solve this conflict.');

    // Ensure the two local accounts don't have the same mail address.
    $account1 = $this->reloadEntity($account1);
    $account2 = $this->reloadEntity($account2);
    $this->assertNotEquals($account1->getEmail(), $account2->getEmail(), "The two local users don't have the same mail address.");
  }

  /**
   * Tests local user vs remote user conflict with local login fallback.
   *
   * Scenario:
   * - Local user A has the same username as remote user A, but not the same
   *   mail address.
   * - Local user B has the same mail address as remote user A, but not the same
   *   username.
   * - Logging in is first tried via remote database. If that fails, logging in
   *   is tried again via the local database.
   *
   * Logging in using the local password should succeed.
   */
  public function testExistingNameAndMailWithLocalUserFallback() {
    // Set logging in via remote database with local user fallback.
    \Drupal::configFactory()->getEditable('remotedbuser.settings')->set('login', RemotedbUserAuthenticationInterface::REMOTEFIRST)->save();

    // Create a remote user.
    $remote_account = $this->createRemoteUser();

    // Create two local users. One with the remote user's name and one with the
    // remote user's mail.
    $account1 = User::create([
      'name' => $remote_account->name,
      'mail' => $this->randomMachineName() . '@example.com',
      'pass' => 'foo',
      'status' => 1,
      'roles' => [$this->roleId],
    ]);
    // Prevent this account from exchanging it with the remote database.
    $account1->from_remotedb = TRUE;
    $account1->save();

    $account2 = User::create([
      'name' => $this->randomMachineName(),
      'mail' => $remote_account->mail,
      'pass' => 'bar',
      'status' => 1,
      'roles' => [$this->roleId],
    ]);
    // Prevent this account from exchanging it with the remote database.
    $account2->from_remotedb = TRUE;
    $account2->save();

    // Ensure that the first user can login using its local password.
    $dummy_account = $this->createDummyAccount($account1->getAccountName());
    $dummy_account->passRaw = 'foo';
    $this->drupalLogin($dummy_account);

    // Ensure the two local accounts don't have the same mail address.
    $account1 = $this->reloadEntity($account1);
    $account2 = $this->reloadEntity($account2);
    $this->assertNotEquals($account1->getEmail(), $account2->getEmail(), "The two local users don't have the same mail address.");

    // Now, try to edit the profile.
    $edit = [];
    $this->drupalPostForm('user/' . $account1->id() . '/edit', $edit, 'Save');
    $this->assertRaw('The changes have been saved.');

    // Login with the second user now.
    $dummy_account = $this->createDummyAccount($account2->getAccountName());
    $dummy_account->passRaw = 'bar';
    $this->drupalLogin($dummy_account);

    // Try to edit the profile of this user too.
    $edit = [];
    $this->drupalPostForm('user/' . $account2->id() . '/edit', $edit, 'Save');
    $this->assertRaw('The changes have been saved.');

    // Check that there are remote accounts for each local user now.
    $account1_found = FALSE;
    $account2_found = FALSE;
    $remotes = $this->remotedbUserStorage->getRemoteAccounts();
    foreach ($remotes as $remote_user) {
      switch ($remote_user['name']) {
        case $account1->getAccountName():
          $this->assertEquals($account1->getEmail(), $remote_user['mail'], 'For account 1 a remote user with the expected mail address exists.');
          $account1_found = TRUE;
          break;

        case $account2->getAccountName():
          $this->assertEquals($account2->getEmail(), $remote_user['mail'], 'For account 2 a remote user with the expected mail address exists.');
          $account2_found = TRUE;
          break;
      }
    }

    $this->assertTrue($account1_found, 'Account 1 was found in the remote database.');
    $this->assertTrue($account2_found, 'Account 2 was found in the remote database.');
  }

}
