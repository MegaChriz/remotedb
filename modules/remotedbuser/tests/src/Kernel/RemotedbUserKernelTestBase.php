<?php

namespace Drupal\Tests\remotedbuser\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\remotedb\Traits\RemotedbCreationTrait;
use Drupal\Tests\remotedbuser\Traits\RemotedbUserCreationTrait;

/**
 * Base class for Remote database user kernel tests.
 */
abstract class RemotedbUserKernelTestBase extends KernelTestBase {

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
    $this->installSchema('system', ['sequences']);
    $this->installSchema('user', ['users_data']);
    $this->installEntitySchema('user');
    $this->installEntitySchema('remotedb');
    $this->installConfig(['remotedb', 'user', 'remotedbuser']);
  }

}
