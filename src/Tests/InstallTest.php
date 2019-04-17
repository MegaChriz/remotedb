<?php

namespace Drupal\remotedb\Tests;

use Drupal\remotedb\Entity\RemotedbInterface;
use Drupal\remotedb_test\Entity\MockRemotedb;

/**
 *
 */
class InstallTest extends RemotedbTestBase {
  /**
   * The number of times remotedbCallback() was called.
   *
   * @var int
   */
  private $calledRemotedbCallback = 0;

  /**
   *
   */
  public static function getInfo() {
    return [
      'name' => 'Install test',
      'description' => 'Tests if the module can be installed correctly.',
      'group' => 'Remote database',
    ];
  }

  /**
   * Tests if remotedb installation went well.
   */
  public function test() {
    $remotedb = \Drupal::entityManager()->getStorage('remotedb')->create([]);
    $this->assertTrue($remotedb instanceof RemotedbInterface, 'Remote database is instance of RemotedbInterface.');
    $this->assertTrue($remotedb instanceof MockRemotedb, 'Remote database is instance of MockRemotedb. Actual:' . get_class($remotedb));

    // Ensure the callback method for test purposes works.
    $remotedb->setCallback([$this, 'remotedbCallback']);
    $remotedb->sendRequest('fake_method', ['alpha', 'beta']);
    $this->assertEqual(1, $this->calledRemotedbCallback, 'The test callback function was called once.');
  }

  /**
   * Callback for remote database calls.
   *
   * @param string $method
   *   The method being called.
   * @param array $params
   *   An array of parameters.
   */
  public function remotedbCallback($method, $params) {
    $this->calledRemotedbCallback++;
    $this->assertEqual($method, 'fake_method', 'A fake method was called on the mocked remote database object.');
    $this->assertEqual($params, ['alpha', 'beta'], 'The test callback received expected the parameters.');
  }

}
