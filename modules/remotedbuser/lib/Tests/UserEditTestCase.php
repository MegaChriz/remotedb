<?php

/**
 * @file
 * Contains \Drupal\remotedbuser\Tests\UserEditTestCase.
 */

namespace Drupal\remotedbuser\Tests;

use Drupal\remotedbuser\Tests\RemotedbUserTestBase;

class UserEditTestCase extends RemotedbUserTestBase {
  public static function getInfo() {
    return array(
      'name' => 'User: Edit',
      'description' => 'Test if user can edit his username, mail or password.',
      'group' => 'Remote database',
    );
  }

  /**
   * Tests if an user can not change its username to one that already exists remotely.
   */
  public function testNameDuplicates() {
    // Create a remote user.
    $remote_account = $this->remotedbCreateRemoteUser();

    $account = $this->drupalCreateUser(array('change own username'));
    $this->drupalLogin($account);

    // Test that error message appears when attempting to use a non-unique user name.
    $edit = array();
    $edit['name'] = $remote_account->name;
    $this->drupalPost("user/$account->uid/edit", $edit, t('Save'));
    $this->assertRaw(t('The name %name is already taken.', array('%name' => $edit['name'])));
  }

  /**
   * Tests if an user can change its username.
   */
  public function testLocalNameChange() {
    $account = $this->drupalCreateUser(array('change own username'));
    $this->drupalLogin($account);

    $edit = array();
    $edit['current_pass'] = $account->pass_raw;
    $edit['name'] = $this->randomName();
    $this->drupalPost("user/$account->uid/edit", $edit, t('Save'));
    $this->assertRaw(t("The changes have been saved."));

    // Assert that the change is reflected in the remote database.
    $remote_account = $this->controller->loadBy($account->remotedb_uid);
    $this->assertEqual($edit['name'], $remote_account->name, 'The username was also changed in the remote database.');
  }

  /**
   * Tests if an user can login using his new username if it changed in the remote database.
   */
  public function testRemoteNameChange() {
    // Set logging in via the remote database only.
    variable_set('remotedbuser_login', REMOTEDB_REMOTEONLY);

    // Create an account and ensure it can initially login.
    $account = $this->drupalCreateUser();
    $this->drupalLogin($account);
    $this->drupalLogout();

    // Change name from remote user.
    $remote_account = $this->controller->loadBy($account->remotedb_uid);
    $remote_account->name = $this->randomName();
    $remote_account->save();

    // Verify the user can not log in using its old username.
    $edit = array(
      'name' => $account->name,
      'pass' => $account->pass_raw
    );
    $this->drupalPost('user', $edit, t('Log in'));
    $this->assertText(t('Sorry, unrecognized username or password. Have you forgotten your password?'));

    // Test if the user can login using the new username.
    $account->name = $remote_account->name;
    $this->drupalLogin($account);

    // Check if the user has a different username now.
    $account = user_load($account->uid, TRUE);
    $this->assertEqual($remote_account->name, $account->name, 'The user has a new username.');
  }

  /**
   * Tests if an user can not change its mail address to one that already exists remotely.
   */
  public function testMailDuplicates() {
    // Create a remote user.
    $remote_account = $this->remotedbCreateRemoteUser();

    $account = $this->drupalCreateUser();
    $this->drupalLogin($account);

    // Test that error message appears when attempting to use a non-unique mail address.
    $edit = array();
    $edit['current_pass'] = $account->pass_raw;
    $edit['mail'] = $remote_account->mail;
    $this->drupalPost("user/$account->uid/edit", $edit, t('Save'));
    $this->assertRaw(t('The e-mail address %email is already taken.', array('%email' => $remote_account->mail)));
  }

  /**
   * Tests if an user can change its mail address.
   */
  public function testLocalMailChange() {
    $account = $this->drupalCreateUser();
    $this->drupalLogin($account);

    $edit = array();
    $edit['current_pass'] = $account->pass_raw;
    $edit['mail'] = $this->randomName() . '@example.com';
    $this->drupalPost("user/$account->uid/edit", $edit, t('Save'));
    $this->assertRaw(t("The changes have been saved."));

    // Assert that the change is reflected in the remote database.
    $remote_account = $this->controller->loadBy($account->remotedb_uid);
    $this->assertEqual($edit['mail'], $remote_account->mail, 'The mail address was also changed in the remote database.');
  }

  /**
   * Tests if an mail address change in the remote database has effect locally.
   */
  public function testRemoteMailChange() {
    // Set logging in via the remote database only.
    variable_set('remotedbuser_login', REMOTEDB_REMOTEONLY);

    // Create an account and ensure it can initially login.
    $account = $this->drupalCreateUser();
    $this->drupalLogin($account);
    $this->drupalLogout();

    // Change mail from remote user.
    $remote_account = $this->controller->loadBy($account->remotedb_uid);
    $remote_account->mail = $this->randomName() . '@example.com';
    $remote_account->save();

    // Login the user again.
    $this->drupalLogin($account);

    // Check if the user has a different mail address now.
    $account = user_load($account->uid, TRUE);
    $this->assertEqual($remote_account->mail, $account->mail, 'The user has a new mail address.');
  }

  /**
   * Tests if an user that changes its password locally can still login via the remote
   * database.
   */
  public function testLocalPasswordChange() {
    // Set logging in via the remote database only.
    variable_set('remotedbuser_login', REMOTEDB_REMOTEONLY);

    $account = $this->drupalCreateUser();
    $this->drupalLogin($account);

    // Change password.
    $edit = array();
    $edit['current_pass'] = $account->pass_raw;
    $edit['pass[pass1]'] = $new_pass = $this->randomName();
    $edit['pass[pass2]'] = $new_pass;
    $this->drupalPost("user/$account->uid/edit", $edit, t('Save'));

    // Make sure the user can log in with its new password.
    $this->drupalLogout();
    $account->pass_raw = $new_pass;
    $this->drupalLogin($account);
  }

  /**
   * Tests if an user can still login if the password changed on the remote database.
   */
  public function testRemotePasswordChange() {
    // Set logging in via the remote database only.
    variable_set('remotedbuser_login', REMOTEDB_REMOTEONLY);

    // Create an account and ensure it can initially login.
    $account = $this->drupalCreateUser();
    $this->drupalLogin($account);
    $this->drupalLogout();

    // Change password from remote user.
    $remote_account = $this->controller->loadBy($account->remotedb_uid);
    $new_pass = user_password();
    $remote_account->pass_raw = $new_pass;
    $remote_account->pass = $this->hashPassword($new_pass);
    $remote_account->save();

    // Verify the user can not log in using its old password.
    $edit = array(
      'name' => $account->name,
      'pass' => $account->pass_raw
    );
    $this->drupalPost('user', $edit, t('Log in'));
    $this->assertText(t('Sorry, unrecognized username or password. Have you forgotten your password?'));

    // Test if the user can login using the new password.
    $account->pass_raw = $new_pass;
    $this->drupalLogin($account);
  }
}