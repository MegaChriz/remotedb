<?php

/**
 * @file
 * Contains \Drupal\remotedbuser\Tests\RemotedbUserTestBase.
 */

namespace Drupal\remotedbuser\Tests;

use Drupal\remotedb\Tests\RemotedbTestBase;

class RemotedbUserTestBase extends RemotedbTestBase {
  /**
   * Overrides DrupalWebTestCase::setUp().
   */
  protected function setUp(array $modules = array()) {
    $modules = array_merge($modules, array('remotedbuser'));
    parent::setUp($modules);
  }
}
