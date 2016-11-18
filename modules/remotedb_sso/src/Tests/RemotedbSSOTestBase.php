<?php

/**
 * @file
 * Contains \Drupal\remotedb_sso\Tests\RemotedbSSOTestBase.
 */

namespace Drupal\remotedb_sso\Tests;

use Drupal\remotedbuser\Tests\RemotedbUserTestBase;

/**
 * Base class for remotedb_sso tests.
 */
abstract class RemotedbSSOTestBase extends RemotedbUserTestBase {
  /**
   * Overrides DrupalWebTestCase::setUp().
   */
  protected function setUp(array $modules = array()) {
    $modules = array_merge($modules, array('remotedb_sso'));
    parent::setUp($modules);

    // Create a dummy remote database.
    $record = array(
      'name' => 'test',
      'label' => 'Test',
      'url' => 'http://www.example.com/server',
      'status' => 1,
      'module' => 'remotedb_sso',
    );
    drupal_write_record('remotedb', $record);

    // Set remotedb for remotedbuser.
    variable_set('remotedbuser_remotedb', 'test');

    // Set ticket service.
    variable_set('remotedb_sso_ticket_service', 'Drupal\remotedb_sso\Tests\Mocks\MockTicketService');
  }
}
