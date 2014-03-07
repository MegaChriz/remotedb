<?php

/**
 * @file
 * Contains \Drupal\remotedbuser\Tests\UserLoginTestCase.
 */

namespace Drupal\remotedbuser\Tests;

use Drupal\remotedbuser\Tests\RemotedbUserTestBase;

class UserLoginTestCase extends RemotedbUserTestBase {
  public static function getInfo() {
    return array(
      'name' => 'User: Login',
      'description' => 'Ensure that users that only exists on the remote database can login.',
      'group' => 'Remote database',
    );
  }

  /**
   * Tests an user login.
   */
  public function testUserLogin() {
    // @todo Implement!
    $this->remotedbCreateUser();
  }
}
