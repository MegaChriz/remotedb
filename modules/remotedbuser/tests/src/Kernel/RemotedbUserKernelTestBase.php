<?php

namespace Drupal\Tests\remotedbuser\Kernel;

use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use Drupal\Tests\remotedb\Traits\RemotedbCreationTrait;
use Drupal\Tests\remotedbuser\Traits\RemotedbUserCreationTrait;

/**
 * Base class for Remote database user kernel tests.
 */
abstract class RemotedbUserKernelTestBase extends EntityKernelTestBase {

  use RemotedbCreationTrait;
  use RemotedbUserCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'remotedb',
    'remotedbuser',
    'remotedb_test',
    'remotedbuser_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Install database schemes.
    $this->installSchema('user', ['users_data']);
    $this->installEntitySchema('remotedb');
    $this->installEntitySchema('user');
    $this->installConfig(['remotedb', 'user', 'remotedbuser']);
  }

}
