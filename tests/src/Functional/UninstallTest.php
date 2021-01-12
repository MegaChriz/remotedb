<?php

namespace Drupal\Tests\remotedb\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests module uninstallation.
 *
 * @group remotedb
 */
class UninstallTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['remotedb'];

  /**
   * Tests module uninstallation.
   */
  public function testUninstall() {
    // Confirm that the Remote database module has been installed.
    $module_handler = $this->container->get('module_handler');
    $this->assertTrue($module_handler->moduleExists('remotedb'));

    // Uninstall Remote database.
    $this->container->get('module_installer')->uninstall(['remotedb']);
    $this->assertFalse($module_handler->moduleExists('remotedb'));
  }

}
