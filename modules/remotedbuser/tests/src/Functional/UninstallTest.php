<?php

namespace Drupal\Tests\remotedbuser\Functional;

use Drupal\Tests\BrowserTestBase;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests module uninstallation.
 *
 * @group remotedbuser
 */
#[RunTestsInSeparateProcesses]
class UninstallTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'remotedbuser',
    'field',
  ];

  /**
   * Tests module uninstallation.
   */
  public function testUninstall(): void {
    // Confirm that the remotedbuser module has been installed.
    $this->assertTrue($this->container->get('module_handler')->moduleExists('remotedbuser'));

    // Assert that the user entity type now has a field called 'remotedb_uid'.
    $definitions = $this->container->get('entity_field.manager')->getBaseFieldDefinitions('user');
    $this->assertArrayHasKey('remotedb_uid', $definitions);

    // Uninstall remotedbuser.
    $this->container->get('module_installer')->uninstall(['remotedbuser']);

    // Rebuild container to clear previously cached field definitions.
    $this->rebuildContainer();

    // Assert that remotedbuser is uninstalled.
    $this->assertFalse($this->container->get('module_handler')->moduleExists('remotedbuser'));

    // Assert that the user entity type no longer has the remotedb_uid field.
    $definitions = $this->container->get('entity_field.manager')->getBaseFieldDefinitions('user');
    $this->assertArrayNotHasKey('remotedb_uid', $definitions);
  }

}
