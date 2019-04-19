<?php

namespace Drupal\Tests\remotedb\Functional;

use Drupal\remotedb\Entity\RemotedbInterface;
use Drupal\remotedb_test\Entity\MockRemotedb;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests module installation.
 *
 * @group remotedb
 */
class InstallTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [];

  /**
   * Module handler to ensure installed modules.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  public $moduleHandler;

  /**
   * Module installer.
   *
   * @var \Drupal\Core\Extension\ModuleInstallerInterface
   */
  public $moduleInstaller;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->moduleHandler = $this->container->get('module_handler');
    $this->moduleInstaller = $this->container->get('module_installer');
  }

  /**
   * Tests that the module is installable.
   */
  public function testInstallation() {
    $this->assertFalse($this->moduleHandler->moduleExists('remotedb'));
    $this->assertTrue($this->moduleInstaller->install(['remotedb']));
    $this->assertTrue($this->moduleHandler->moduleExists('remotedb'));
  }

  /**
   * Tests that the module is installable.
   */
  public function testInstallationWithTestModule() {
    $this->assertFalse($this->moduleHandler->moduleExists('remotedb'));
    $this->assertTrue($this->moduleInstaller->install(['remotedb', 'remotedb_test']));
    $this->assertTrue($this->moduleHandler->moduleExists('remotedb'));
    $this->assertTrue($this->moduleHandler->moduleExists('remotedb_test'));

    // Ensure that the remotedb entity implements the right interface.
    $remotedb = \Drupal::entityTypeManager()->getStorage('remotedb')->create([]);
    $this->assertTrue($remotedb instanceof RemotedbInterface, 'Remote database is instance of RemotedbInterface.');
    $this->assertTrue($remotedb instanceof MockRemotedb, 'Remote database is instance of MockRemotedb. Actual:' . get_class($remotedb));

    // Ensure that the callback method for test purposes works.
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
    $this->assertSame($method, 'fake_method', 'A fake method was called on the mocked remote database object.');
    $this->assertSame($params, ['alpha', 'beta'], 'The test callback received expected the parameters.');
  }

}
