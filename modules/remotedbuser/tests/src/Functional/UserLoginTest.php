<?php

namespace Drupal\Tests\remotedbuser\Functional;

/**
 * Ensure that users that only exist on the remote database can login.
 *
 * @group remotedbuser
 */
class UserLoginTest extends RemotedbUserBrowserTestBase {

  /**
   * Tests an user login.
   */
  public function testUserLogin() {
    $remote_account = $this->remotedbCreateRemoteUser();

    // Log out current user if there is one logged in.
    if ($this->loggedInUser) {
      $this->drupalLogout();
    }

    // Login using information from remote account.
    $edit = [
      'name' => $remote_account->name,
      'pass' => $remote_account->pass_raw,
    ];
    $this->drupalPost('user', $edit, t('Log in'));
    // Assert that the user logged in.
    $pass = $this->assertLink(t('Log out'), 0, t('User %name successfully logged in.', ['%name' => $remote_account->name]), t('User login'));

    // Assert that the remote account now exists locally and has a remote_uid.
    $this->assertLocalUser($remote_account->uid);
  }

}
