<?php

namespace Drupal\Tests\remotedb\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\remotedb\Traits\RemotedbCreationTrait;

/**
 * Provides a base class for Remotedb functional tests.
 */
abstract class RemotedbBrowserTestBase extends BrowserTestBase {

  use RemotedbCreationTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'remotedb',
    'remotedb_test',
  ];

}
