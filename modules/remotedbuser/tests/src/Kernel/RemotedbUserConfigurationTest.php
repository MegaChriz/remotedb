<?php

namespace Drupal\Tests\remotedbuser\Kernel;

use PHPUnit\Framework\Attributes\Group;
use Drupal\remotedbuser\RemotedbUserConfiguration;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * @coversDefaultClass \Drupal\remotedbuser\RemotedbUserConfiguration
 *
 * @group remotedbuser
 */
#[RunTestsInSeparateProcesses]
#[Group('remotedbuser')]
class RemotedbUserConfigurationTest extends RemotedbUserKernelTestBase {

  /**
   * @covers ::getDefault
   */
  public function testGetDefault(): void {
    // Create a remote database.
    $remotedb = $this->createRemotedb();

    // Configure this database to be the default.
    $config = $this->container->get('config.factory')->getEditable('remotedbuser.settings');
    $config->set('remotedb', $remotedb->id())
      ->save();

    $remotedb_user_configuration = new RemotedbUserConfiguration($this->container->get('config.factory'), $this->container->get('entity_type.manager'));
    $this->assertSame($remotedb->id(), $remotedb_user_configuration->getDefault()->id());
  }

  /**
   * @covers ::getDefault
   */
  public function testGetDefaultWithoutRemoteDatabase(): void {
    $remotedb_user_configuration = new RemotedbUserConfiguration($this->container->get('config.factory'), $this->container->get('entity_type.manager'));
    $this->assertNull($remotedb_user_configuration->getDefault());
  }

}
