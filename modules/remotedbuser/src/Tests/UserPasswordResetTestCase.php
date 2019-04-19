<?php

namespace Drupal\remotedbuser\Tests;

use Drupal\remotedbuser\Tests\RemotedbUserTestBase;

class UserPasswordResetTestCase extends RemotedbUserTestBase {
  public static function getInfo() {
    return array(
      'name' => 'User: Reset password',
      'description' => 'Ensure that users that only exists on the remote database can request a new password.',
      'group' => 'Remote database',
    );
  }

  /**
   * Tests password reset using username.
   */
  public function testUserPasswordResetByName() {
    $remote_account = $this->remotedbCreateRemoteUser();

    // Attempt to reset password.
    $edit = array('name' => $remote_account->name);
    $this->drupalPost('user/password', $edit, t('E-mail new password'));
    // Confirm the password reset.
    $this->assertText(t('Further instructions have been sent to your e-mail address.'), 'Password reset instructions mailed message displayed.');

    // Assert that the remote account now exists locally and has a remotedb_uid.
    $this->assertLocalUser($remote_account->uid);
  }

  /**
   * Tests password reset using mail.
   */
  public function testUserPasswordResetByMail() {
    $remote_account = $this->remotedbCreateRemoteUser();

    // Attempt to reset password.
    $edit = array('name' => $remote_account->mail);
    $this->drupalPost('user/password', $edit, t('E-mail new password'));
    // Confirm the password reset.
    $this->assertText(t('Further instructions have been sent to your e-mail address.'), 'Password reset instructions mailed message displayed.');

    // Assert that the remote account now exists locally and has a remotedb_uid.
    $this->assertLocalUser($remote_account->uid);
  }
}
