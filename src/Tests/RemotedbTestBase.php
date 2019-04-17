<?php

namespace Drupal\remotedb\Tests;

use DrupalWebTestCase;

/**
 *
 */
abstract class RemotedbTestBase extends DrupalWebTestCase {

  /**
   * Overrides DrupalWebTestCase::setUp().
   *
   * @param array $modules
   *   A list of modules to enable.
   *
   * @return void
   */
  protected function setUp(array $modules = []) {
    $modules = array_merge($modules, ['remotedb', 'remotedb_test', 'yvklibrary']);
    parent::setUp($modules);
  }

}
