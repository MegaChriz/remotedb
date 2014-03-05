<?php

/**
 * @file
 * Contains \Drupal\remotedb\Tests\UserPasswordResetTestCase.
 */

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
   * Tests password reset functionality.
   */
  public function testUserPasswordReset() {
    // @todo Implement!
  }
}
