<?php

namespace Drupal\Tests\remotedb_sso\Functional;

use Drupal\Tests\remotedbuser\Functional\RemotedbUserBrowserTestBase;

/**
 * Base class for remotedb_sso tests.
 */
abstract class RemotedbSsoBrowserTestBase extends RemotedbUserBrowserTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'remotedb',
    'remotedb_test',
    'remotedbuser',
    'remotedbuser_test',
    'remotedb_sso',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create a dummy remote database.
    $record = [
      'name' => 'test',
      'label' => 'Test',
      'url' => 'http://www.example.com/server',
      'status' => 1,
      'module' => 'remotedb_sso',
    ];
    \Drupal::database()->insert('remotedb')->fields($record)->execute();

    // Set remotedb for remotedbuser.
    // @FIXME
    // // @FIXME
    // // This looks like another module's variable. You'll need to rewrite this call
    // // to ensure that it uses the correct configuration object.
    // variable_set('remotedbuser_remotedb', 'test');
    // Set ticket service.
    \Drupal::configFactory()->getEditable('remotedb_sso.settings')->set('remotedb_sso_ticket_service', 'Drupal\remotedb_sso\Tests\Mocks\MockTicketService')->save();
  }

}
