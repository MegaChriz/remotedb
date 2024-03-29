<?php

namespace Drupal\Tests\remotedbuser\Functional;

/**
 * Tests for requesting a new password via the remote database.
 *
 * @group remotedbuser
 */
class UserPasswordResetTest extends RemotedbUserBrowserTestBase {

  /**
   * Tests password reset using username.
   */
  public function testUserPasswordResetByName() {
    $remote_account = $this->createRemoteUser();

    // Attempt to reset password.
    $this->drupalGet('user/password');
    $edit = ['name' => $remote_account->name];
    $this->submitForm($edit, 'Submit');
    // Confirm the password reset.
    $this->assertSession()->pageTextContains('an email will be sent with instructions to reset your password.');

    // Assert that the remote account now exists locally and has a remotedb_uid.
    $this->assertLocalUser($remote_account->uid);
  }

  /**
   * Tests password reset using mail.
   */
  public function testUserPasswordResetByMail() {
    $remote_account = $this->createRemoteUser();

    // Attempt to reset password.
    $this->drupalGet('user/password');
    $edit = ['name' => $remote_account->mail];
    $this->submitForm($edit, 'Submit');
    // Confirm the password reset.
    $this->assertSession()->pageTextContains('an email will be sent with instructions to reset your password.');

    // Assert that the remote account now exists locally and has a remotedb_uid.
    $this->assertLocalUser($remote_account->uid);
  }

}
