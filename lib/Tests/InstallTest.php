<?php

/**
 * @file
 * Contains \Drupal\remotedb\Tests\InstallTest.
 */

namespace Drupal\remotedb\Tests;

use Drupal\remotedb\Entity\RemotedbInterface;
use Drupal\remotedb\Tests\RemotedbTestBase;
use Drupal\remotedb_mock_test\Entity\MockRemotedb;

class InstallTest extends RemotedbTestBase {
  public static function getInfo() {
    return array(
      'name' => 'Install test',
      'description' => 'Tests if the module can be installed correctly.',
      'group' => 'Remote database',
    );
  }

  /**
   * Tests if remotedb installation went well.
   */
  public function test() {
    $remotedb = entity_create('remotedb', array());
    $this->assertTrue($remotedb instanceof RemotedbInterface, 'Remote database is instance of RemotedbInterface.');
    $this->assertTrue($remotedb instanceof MockRemotedb, 'Remote database is instance of MockRemotedb. Actual:' . get_class($remotedb));
  }
}
