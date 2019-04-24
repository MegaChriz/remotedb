<?php

namespace Drupal\Tests\remotedbuser\Kernel;

use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use Drupal\Tests\remotedb\Traits\RemotedbCreationTrait;
use Drupal\Tests\remotedbuser\Traits\RemotedbUserCreationTrait;
use Drupal\Tests\user\Traits\UserCreationTrait;

/**
 * Base class for Remote database user kernel tests.
 */
abstract class RemotedbUserKernelTestBase extends EntityKernelTestBase {

  use RemotedbCreationTrait;
  use RemotedbUserCreationTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'system',
    'user',
    'remotedb',
    'remotedbuser',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Install database schemes.
    $this->installSchema('user', ['users_data']);
    $this->installEntitySchema('remotedb');
    $this->installConfig(['remotedb', 'user', 'remotedbuser']);
  }

}
