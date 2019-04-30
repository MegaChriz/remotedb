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
    $remote_account = $this->createRemoteUser();
    $remote_account->save();

    // Login using information from remote account.
    $edit = [
      'name' => $remote_account->name,
      'pass' => $remote_account->pass_raw,
    ];
    $this->drupalPostForm('user', $edit, 'Log in');

    // Assert that the remote account now exists locally and has a remote_uid.
    $this->assertLocalUser($remote_account->uid);

    // Assert that the local account is logged in.
    $this->assertLoggedIn(2);
  }

}
