<?php

namespace Drupal\Tests\remotedbuser\Kernel;

use Drupal\Tests\remotedb\Traits\RemotedbCreationTrait;
use Drupal\KernelTests\KernelTestBase;

/**
 * Base class for Remote database user kernel tests.
 */
abstract class RemotedbUserKernelTestBase extends KernelTestBase {

  use RemotedbCreationTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'remotedb',
    'remotedbuser',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Install database schemes.
    $this->installEntitySchema('remotedb');
    $this->installConfig(['remotedb', 'remotedbuser']);
  }

}
