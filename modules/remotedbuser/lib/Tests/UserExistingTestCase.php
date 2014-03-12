<?php

/**
 * @file
 * Contains \Drupal\remotedbuser\Tests\UserExistingTestCase.
 */

namespace Drupal\remotedbuser\Tests;

use Drupal\remotedbuser\Tests\RemotedbUserTestBase;

class UserExistingTestCase extends RemotedbUserTestBase {
  public static function getInfo() {
    return array(
      'name' => 'User: Existing user conflicts',
      'description' => 'Test how the system deals with name collisions between local and remote users.',
      'group' => 'Remote database',
    );
  }

  /**
   * Tests if a local user gets linked to the expected remote user if the name equals,
   * but the mail differs.
   */
  public function testExistingName() {
    // Create a remote user.
    $remote_account = $this->remotedbCreateRemoteUser();

    // Create a local user directly into the database to avoid user hooks being run.
    $account_edit = array(
      'uid' => db_next_id(db_query('SELECT MAX(uid) FROM {users}')->fetchField()),
      'name' => $remote_account->name,
      'mail' => $this->randomName() . '@example.com',
      'status' => 1,
    );
    drupal_write_record('users', $account_edit);

    // Verify that this user is not linked to a remote account yet.
    $account = user_load($account_edit['uid'], TRUE);
    $this->assertEqual(0, $account->remotedb_uid, 'The account is not linked to a remote account yet.');
    // Verify that this user has still its local mail address.
    $this->assertNotEqual($remote_account->mail, $account->mail, 'The local users mail address does not equal the remote users mail address.');

    // Try to login as this user using remote login data.
    $dummy_account = new \stdClass();
    $dummy_account->name = $remote_account->name;
    $dummy_account->pass_raw = $remote_account->pass_raw;
    $this->drupalLogin($dummy_account);
    // Verify that the mail addresses are equal now.
    $account = user_load($account_edit['uid'], TRUE);
    $this->assertEqual($remote_account->mail, $account->mail, 'The local users mail address has changed.');
  }

  /**
   * Tests if a local user gets linked to the expected remote user if the mail equals,
   * but the name differs.
   */
  public function testExistingMail() {
    // Create a remote user.
    $remote_account = $this->remotedbCreateRemoteUser();

    // Create a local user directly into the database to avoid user hooks being run.
    $account_edit = array(
      'uid' => db_next_id(db_query('SELECT MAX(uid) FROM {users}')->fetchField()),
      'name' => $this->randomName(),
      'mail' => $remote_account->mail,
      'status' => 1,
    );
    drupal_write_record('users', $account_edit);

    // Verify that this user is not linked to a remote account yet.
    $account = user_load($account_edit['uid'], TRUE);
    $this->assertEqual(0, $account->remotedb_uid, 'The account is not linked to a remote account yet.');
    // Verify that this user has still its local username.
    $this->assertNotEqual($remote_account->name, $account->name, 'The local username does not equal the remote username.');

    // Try to login as this user using remote login data.
    $dummy_account = new \stdClass();
    $dummy_account->name = $remote_account->name;
    $dummy_account->pass_raw = $remote_account->pass_raw;
    $this->drupalLogin($dummy_account);
    // Verify the username and mail address from the local user are equal to that of the
    // remote user.
    $account = user_load($account_edit['uid'], TRUE);
    $this->assertEqual($remote_account->name, $account->name, 'The local username has changed.');
    $this->assertEqual($remote_account->mail, $account->mail, 'The local users mail address still equals the remote users mail address.');
  }

  /**
   * Tests if two local users don't get the same mail address when the first user
   * has an username that exists in remote database and the second user has a mail
   * address of that same remote user.
   * Tests if login fails when using the remote password.
   */
  public function testExistingNameAndMail() {
    // Create a remote user.
    $remote_account = $this->remotedbCreateRemoteUser();

    // Create two local users. One with the remote user's name and one with the remote user's mail.
    $account_edit1 = array(
      'uid' => db_next_id(db_query('SELECT MAX(uid) FROM {users}')->fetchField()),
      'name' => $remote_account->name,
      'mail' => $this->randomName() . '@example.com',
      'status' => 1,
    );
    drupal_write_record('users', $account_edit1);
    $account_edit2 = array(
      'uid' => db_next_id(db_query('SELECT MAX(uid) FROM {users}')->fetchField()),
      'name' => $this->randomName(),
      'mail' => $remote_account->mail,
      'status' => 1,
    );
    drupal_write_record('users', $account_edit2);

    // Try to login as the first local user and ensure an error message appears.
    $edit = array(
      'name' => $remote_account->name,
      'pass' => $remote_account->pass_raw,
    );
    $this->drupalPost('user', $edit, t('Log in'));
    $this->assertText(t('Another user already exists in the system with the same login name. You should contact the system administrator in order to solve this conflict.'));

    // Ensure the two local accounts don't have the same mail address.
    $account1 = user_load($account_edit1['uid'], TRUE);
    $account2 = user_load($account_edit2['uid'], TRUE);
    $this->assertNotEqual($account1->mail, $account2->mail, "The two local users don't have the same mail address.");
  }

  /**
   * Tests if two local users don't get the same mail address when the first user
   * has an username that exists in remote database and the second user has a mail
   * address of that same remote user.
   * Tests if login succeeds using the local password.
   *
   * @todo editing account fails with 'username is already taken'.
   */
  public function testExistingNameAndMailWithLocalUserFallback() {
    // Set logging in via remote database with local user fallback.
    variable_set('remotedbuser_login', REMOTEDB_REMOTEFIRST);

    // Create a remote user.
    $remote_account = $this->remotedbCreateRemoteUser();

    // Create two local users. One with the remote user's name and one with the remote user's mail.
    $local_pass = $this->randomName();
    $account_edit1 = array(
      'uid' => db_next_id(db_query('SELECT MAX(uid) FROM {users}')->fetchField()),
      'name' => $remote_account->name,
      'mail' => $this->randomName() . '@example.com',
      'pass' => $this->hashPassword($local_pass),
      'status' => 1,
    );
    drupal_write_record('users', $account_edit1);
    $account_edit2 = array(
      'uid' => db_next_id(db_query('SELECT MAX(uid) FROM {users}')->fetchField()),
      'name' => $this->randomName(),
      'mail' => $remote_account->mail,
      'pass' => $this->hashPassword($local_pass),
      'status' => 1,
    );
    drupal_write_record('users', $account_edit2);

    // Ensure the first user can login using its local password.
    $dummy_account = new \stdClass();
    $dummy_account->name = $account_edit1['name'];
    $dummy_account->pass_raw = $local_pass;
    $this->drupalLogin($dummy_account);

    // Ensure the two local accounts don't have the same mail address.
    $account1 = user_load($account_edit1['uid'], TRUE);
    $account2 = user_load($account_edit2['uid'], TRUE);
    $this->assertNotEqual($account1->mail, $account2->mail, "The two local users don't have the same mail address.");

    // Now, try to edit the profile.
    $edit = array();
    $this->drupalPost("user/$account1->uid/edit", $edit, t('Save'));
    $this->assertRaw(t("The changes have been saved."));

    $remotes = $this->controller->getRemoteAccounts();
    //outputlog(get_defined_vars());
  }

  /**
   * Tests if an user can login that only exists locally and only logging in via
   * the remote database is allowed.
   *
   * @todo Login fails.
   */
  public function testExistingUserLocalLogin() {
    // Set logging in via remote database only.
    variable_set('remotedbuser_login', REMOTEDB_REMOTEONLY);

    // Create a remote user.
    $remote_account = $this->remotedbCreateRemoteUser();

    // Create a local user with different data.
    $local_pass = $this->randomName();
    $account_edit = array(
      'uid' => db_next_id(db_query('SELECT MAX(uid) FROM {users}')->fetchField()),
      'name' => $this->randomName(),
      'mail' => $this->randomName() . '@example.com',
      'pass' => $this->hashPassword($local_pass),
      'status' => 1,
    );
    drupal_write_record('users', $account_edit);

    // Ensure this user can login even if it doesn't exists in the remote database.
    $dummy_account = new \stdClass();
    $dummy_account->name = $account_edit['name'];
    $dummy_account->pass_raw = $local_pass;
    $this->drupalLogin($dummy_account);
  }
}
