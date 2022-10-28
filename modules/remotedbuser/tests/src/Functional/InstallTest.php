<?php

namespace Drupal\Tests\remotedbuser\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests module installation.
 *
 * @group remotedbuser
 */
class InstallTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'field',
  ];

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
   * The number of times the mocked remote database callback was called.
   *
   * @var int
   */
  protected $calledRemotedbCallback = 0;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->moduleHandler = $this->container->get('module_handler');
    $this->moduleInstaller = $this->container->get('module_installer');
  }

  /**
   * Reloads services used by this test.
   */
  protected function reloadServices() {
    $this->rebuildContainer();
    $this->moduleHandler = $this->container->get('module_handler');
    $this->moduleInstaller = $this->container->get('module_installer');
  }

  /**
   * Tests that the module is installable.
   */
  public function testInstallation() {
    $this->assertFalse($this->moduleHandler->moduleExists('remotedbuser'));
    $this->assertTrue($this->moduleInstaller->install(['remotedbuser']));
    $this->reloadServices();
    $this->assertTrue($this->moduleHandler->moduleExists('remotedbuser'));

    // Assert that the user entity type now has a field called 'remotedb_uid'.
    $definitions = $this->container->get('entity_field.manager')->getBaseFieldDefinitions('user');
    $this->assertArrayHasKey('remotedb_uid', $definitions);
  }

}
