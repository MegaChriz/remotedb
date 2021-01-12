<?php

namespace Drupal\Tests\remotedbuser\Functional;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\remotedbuser\RemotedbUserAuthenticationInterface;

/**
 * Tests for editing username, mail or password.
 *
 * @group remotedbuser
 */
class UserEditTest extends RemotedbUserBrowserTestBase {

  /**
   * Tests that an user cannot choose an username already existing remotely.
   */
  public function testNameDuplicates() {
    // Create a remote user.
    $remote_account = $this->createRemoteUser();

    $account = $this->drupalCreateUser(['change own username']);
    $this->drupalLogin($account);

    // Test that an error message appears when attempting to use a non-unique
    // user name.
    $edit = [
      'name' => $remote_account->name,
    ];
    $this->drupalPostForm('user/' . $account->id() . '/edit', $edit, 'Save');
    $this->assertRaw(new FormattableMarkup('The name %name is already taken.', ['%name' => $edit['name']]));
  }

  /**
   * Tests if an user can change its username.
   */
  public function testLocalNameChange() {
    $account = $this->drupalCreateUser(['change own username']);
    $this->drupalLogin($account);

    $edit = [];
    $edit['current_pass'] = $account->passRaw;
    $edit['name'] = $this->randomMachineName();
    $this->drupalPostForm('user/' . $account->id() . '/edit', $edit, 'Save');
    $this->assertRaw('The changes have been saved.');

    // Assert that the change is reflected in the remote database.
    $remote_account = $this->remotedbUserStorage->load($account->remotedb_uid->value);
    $this->assertEquals($edit['name'], $remote_account->name, 'The username was also changed in the remote database.');
  }

  /**
   * Tests if an user can login with their remotely changed username.
   */
  public function testRemoteNameChange() {
    // Set logging in via the remote database only.
    \Drupal::configFactory()->getEditable('remotedbuser.settings')->set('login', RemotedbUserAuthenticationInterface::REMOTEONLY)->save();

    // Create an account and ensure it can initially login.
    $account = $this->drupalCreateUser();
    $this->drupalLogin($account);
    $this->drupalLogout();

    // Change name from remote user.
    $remote_account = $this->remotedbUserStorage->load($account->remotedb_uid->value);
    $remote_account->name = $this->randomMachineName();
    $remote_account->save();

    // Verify the user cannot log in using its old username.
    $edit = [
      'name' => $account->getAccountName(),
      'pass' => $account->passRaw,
    ];
    $this->drupalPostForm('user', $edit, 'Log in');
    $this->assertText('Unrecognized username or password. Forgot your password?');

    // Test if the user can login using the new username.
    $account->name->value = $remote_account->name;
    $this->drupalLogin($account);

    // Check if the user has a different username now.
    $account = $this->reloadEntity($account);
    $this->assertEquals($remote_account->name, $account->getAccountName(), 'The user has a new username.');
  }

  /**
   * Tests that a user cannot choose a mail address already existing remotely.
   */
  public function testMailDuplicates() {
    // Create a remote user.
    $remote_account = $this->createRemoteUser();

    $account = $this->drupalCreateUser();
    $this->drupalLogin($account);

    // Test that error message appears when attempting to use a non-unique mail
    // address.
    $edit = [];
    $edit['current_pass'] = $account->passRaw;
    $edit['mail'] = $remote_account->mail;
    $this->drupalPostForm('user/' . $account->id() . '/edit', $edit, 'Save');
    $this->assertRaw(t('The e-mail address %email is already taken.', ['%email' => $remote_account->mail]));
  }

  /**
   * Tests if a user can change its mail address.
   */
  public function testLocalMailChange() {
    $account = $this->drupalCreateUser();
    $this->drupalLogin($account);

    $edit = [];
    $edit['current_pass'] = $account->passRaw;
    $edit['mail'] = $this->randomMachineName() . '@example.com';
    $this->drupalPostForm('user/' . $account->id() . '/edit', $edit, 'Save');
    $this->assertRaw('The changes have been saved.');

    // Assert that the change is reflected in the remote database.
    $remote_account = $this->remotedbUserStorage->load($account->remotedb_uid->value);
    $this->assertEquals($edit['mail'], $remote_account->mail, 'The mail address was also changed in the remote database.');
  }

  /**
   * Tests if a mail address change in the remote database has effect locally.
   */
  public function testRemoteMailChange() {
    // Set logging in via the remote database only.
    \Drupal::configFactory()->getEditable('remotedbuser.settings')->set('login', RemotedbUserAuthenticationInterface::REMOTEONLY)->save();

    // Create an account and ensure it can initially login.
    $account = $this->drupalCreateUser();
    $this->drupalLogin($account);
    $this->drupalLogout();

    // Change mail from remote user.
    $remote_account = $this->remotedbUserStorage->load($account->remotedb_uid->value);
    $remote_account->mail = $this->randomMachineName() . '@example.com';
    $remote_account->save();

    // Login the user again.
    $this->drupalLogin($account);

    // Check if the user has a different mail address now.
    $account = $this->reloadEntity($account);
    $this->assertEquals($remote_account->mail, $account->getEmail(), 'The user has a new mail address.');
  }

  /**
   * Tests if a user can login remotely after changing its password locally.
   */
  public function testLocalPasswordChange() {
    // Set logging in via the remote database only.
    \Drupal::configFactory()->getEditable('remotedbuser.settings')->set('login', RemotedbUserAuthenticationInterface::REMOTEONLY)->save();

    $account = $this->drupalCreateUser();
    $this->drupalLogin($account);

    // Change password.
    $edit = [];
    $edit['current_pass'] = $account->passRaw;
    $edit['pass[pass1]'] = $new_pass = $this->randomMachineName();
    $edit['pass[pass2]'] = $new_pass;
    $this->drupalPostForm('user/' . $account->id() . '/edit', $edit, 'Save');

    // Make sure the user can log in with its new password.
    $this->drupalLogout();
    $account->passRaw = $new_pass;
    $this->drupalLogin($account);
  }

  /**
   * Tests if a user can still login if their password changed remotely.
   */
  public function testRemotePasswordChange() {
    // Set logging in via the remote database only.
    \Drupal::configFactory()->getEditable('remotedbuser.settings')->set('login', RemotedbUserAuthenticationInterface::REMOTEONLY)->save();

    // Create an account and ensure it can initially login.
    $account = $this->drupalCreateUser();
    $this->drupalLogin($account);
    $this->drupalLogout();

    // Change password from remote user.
    $remote_account = $this->remotedbUserStorage->load($account->remotedb_uid->value);
    $new_pass = user_password();
    $remote_account->pass = $this->hashPassword($new_pass);
    $remote_account->save();

    // Verify the user cannot log in using its old password.
    $edit = [
      'name' => $account->getAccountName(),
      'pass' => $account->passRaw,
    ];
    $this->drupalPostForm('user', $edit, 'Log in');
    $this->assertText('Unrecognized username or password. Forgot your password?');

    // Test if the user can login using the new password.
    $account->passRaw = $new_pass;
    $this->drupalLogin($account);
  }

}
