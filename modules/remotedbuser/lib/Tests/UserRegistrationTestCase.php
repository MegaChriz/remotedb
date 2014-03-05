<?php

/**
 * @file
 * Contains \Drupal\remotedbuser\Tests\UserRegistrationTestCase.
 */

namespace Drupal\remotedbuser\Tests;

use Drupal\remotedbuser\Tests\RemotedbUserTestBase;

class UserRegistrationTestCase extends RemotedbUserTestBase {
  public static function getInfo() {
    return array(
      'name' => 'User: Registration',
      'description' => 'Test registration of users.',
      'group' => 'Remote database',
    );
  }

  /**
   * Tests if registration fails if the user already exists remotely.
   */
  public function testRegistrationUserExists() {
    // @todo Implement!
  }
}
