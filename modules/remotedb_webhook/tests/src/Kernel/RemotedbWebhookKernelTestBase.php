<?php

namespace Drupal\Tests\remotedb_webhook\Kernel;

use Drupal\Tests\remotedbuser\Kernel\RemotedbUserKernelTestBase;

/**
 * Base class for Remotedb Webhook kernel tests.
 */
abstract class RemotedbWebhookKernelTestBase extends RemotedbUserKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'remotedb',
    'remotedb_test',
    'remotedbuser',
    'remotedbuser_test',
    'remotedb_webhook',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create user 1.
    $storage = \Drupal::entityTypeManager()->getStorage('user');
    $storage
      ->create([
        'uid' => 1,
        'name' => 'entity-test',
        'mail' => 'entity@localhost',
        'status' => TRUE,
      ])
      ->save();
  }

}
