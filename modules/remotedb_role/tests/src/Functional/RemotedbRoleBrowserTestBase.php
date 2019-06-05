<?php

namespace Drupal\Tests\remotedb_role\Functional;

use Drupal\Tests\remotedb\Functional\RemotedbBrowserTestBase;

/**
 * Base class for remotedb_role functional tests.
 */
abstract class RemotedbRoleBrowserTestBase extends RemotedbBrowserTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'remotedb',
    'remotedb_test',
    'remotedb_role',
    'remotedb_role_test',
  ];

  /**
   * A remote database.
   *
   * @var \Drupal\remotedb\Entity\RemotedbInterface
   */
  protected $remotedb;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create a remote database.
    $this->remotedb = $this->createRemotedb();

    // Create a few roles.
    $this->createRole([
      'remotedb.administer',
    ], 'admin');
    $this->createRole([], 'foo');
    $this->createRole([], 'foo_bar');
  }

}
