<?php

namespace Drupal\Tests\remotedbuser\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests module uninstallation.
 *
 * @group remotedbuser
 */
class UninstallTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'remotedbuser',
    'field',
  ];

  /**
   * Tests module uninstallation.
   */
  public function testUninstall() {
    // Confirm that the remotedbuser module has been installed.
    $module_handler = $this->container->get('module_handler');
    $this->assertTrue($module_handler->moduleExists('remotedbuser'));

    // Assert that the user entity type now has a field called 'remotedb_uid'.
    $definitions = $this->container->get('entity_field.manager')->getBaseFieldDefinitions('user');
    $this->assertArrayHasKey('remotedb_uid', $definitions);

    // Uninstall remotedbuser.
    $this->container->get('module_installer')->uninstall(['remotedbuser']);
    $this->assertFalse($module_handler->moduleExists('remotedbuser'));

    // Assert that the user entity type no longer has the remotedb_uid field.
    $definitions = $this->container->get('entity_field.manager')->getBaseFieldDefinitions('user');
    $this->assertArrayNotHasKey('remotedb_uid', $definitions);
  }

}
