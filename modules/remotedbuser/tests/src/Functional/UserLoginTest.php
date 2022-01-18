<?php

namespace Drupal\Tests\remotedbuser\Functional;

/**
 * Ensure that users that only exist on the remote database can login.
 *
 * @group remotedbuser
 */
class UserLoginTest extends RemotedbUserBrowserTestBase {

  /**
   * Tests a user login.
   */
  public function testUserLogin() {
    $remote_account = $this->createRemoteUser();

    // Login using information from remote account.
    $this->drupalGet('user');
    $edit = [
      'name' => $remote_account->name,
      'pass' => $remote_account->pass_raw,
    ];
    $this->submitForm($edit, 'Log in');

    // Assert that the remote account now exists locally and has a remote_uid.
    $this->assertLocalUser($remote_account->uid);

    // Assert that the local account is logged in.
    $this->assertLoggedIn(2);
  }

  /**
   * Tests logging in a user imported from the remote database.
   */
  public function testLoginAfterUserImport() {
    // Create a remote account.
    $remote_account = $this->createRemoteUser([
      'pass' => 'foo',
    ]);

    // Now try to login as this new user.
    $this->drupalGet('user');
    $edit = [
      'name' => $remote_account->name,
      'pass' => 'foo',
    ];
    $this->submitForm($edit, 'Log in');

    // Assert that the local account is logged in.
    $this->assertLoggedIn(2);

    // Save the account in the UI without changing anything.
    $this->drupalGet('user/2/edit');
    $this->submitForm([], 'Save');

    // Now logout and try to login again.
    $this->drupalLogout();
    $this->drupalGet('user');
    $this->submitForm($edit, 'Log in');

    // Assert again that the local account is logged in.
    $this->assertLoggedIn(2);
  }

  /**
   * Tests logging in a user after the remote user's password changed.
   */
  public function testLoginExistingUserWithUpdatedPassword() {
    // Create a remote account.
    $remote_account = $this->createRemoteUser([
      'pass' => 'foo',
    ]);

    // Save this user locally.
    $account = $remote_account->toAccount();
    $account->save();

    // Change the password of the remote user.
    $remote_account->pass = $this->hashPassword('bar');
    $remote_account->save();

    // Now try to login as this user.
    $this->drupalGet('user');
    $edit = [
      'name' => $remote_account->name,
      'pass' => 'bar',
    ];
    $this->submitForm($edit, 'Log in');

    // Assert that the local account is logged in.
    $this->assertLoggedIn(2);

    // Save the account in the UI without changing anything.
    $this->drupalGet('user/2/edit');
    $this->submitForm([], 'Save');

    // Now logout and try to login again.
    $this->drupalLogout();
    $this->drupalGet('user');
    $this->submitForm($edit, 'Log in');

    // Assert again that the local account is logged in.
    $this->assertLoggedIn(2);
  }

}
