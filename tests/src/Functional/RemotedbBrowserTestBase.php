<?php

namespace Drupal\Tests\remotedb\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Provides a base class for Remotedb functional tests.
 */
abstract class RemotedbBrowserTestBase extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'remotedb',
    'remotedb_test',
  ];

}
