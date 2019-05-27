<?php

namespace Drupal\remotedb_sso;

/**
 * Base class for remotedb_sso tests.
 */
abstract class RemotedbSSOTestBase extends RemotedbUserTestBase {

  /**
   * Overrides DrupalWebTestCase::setUp().
   */
  protected function setUp(array $modules = []) {
    $modules = array_merge($modules, ['remotedb_sso']);
    parent::setUp($modules);

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
