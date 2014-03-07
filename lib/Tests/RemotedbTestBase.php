<?php

/**
 * @file
 * Contains \Drupal\remotedb\Tests\RemotedbTestBase.
 */

namespace Drupal\remotedb\Tests;

use \DrupalWebTestCase;

abstract class RemotedbTestBase extends DrupalWebTestCase {
  /**
   * Overrides DrupalWebTestCase::setUp().
   *
   * @param array $modules
   *   A list of modules to enable.
   *
   * @return void
   */
  protected function setUp(array $modules = array()) {
    $modules = array_merge($modules, array('remotedb', 'remotedb_test', 'yvklibrary'));
    parent::setUp($modules);
  }
}
