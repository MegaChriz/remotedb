<?php

/**
 * @file
 * Contains \Drupal\remotedb\Tests\RemotedbTestBase.
 */

namespace Drupal\remotedb\Tests;

use Drupal\remotedb\Tests\RemotedbTestBase;

class SSOTestCase extends RemotedbTestBase {
  public static function getInfo() {
    return array(
      'name' => 'SSO',
      'description' => 'Test if single sign-on functionality works as expected.',
      'group' => 'Remote database',
    );
  }

  /**
   * Tests the SSO functionality.
   *
   * @todo complete.
   * @todo add assertions.
   */
  public function test() {
    return;
    // Create an account.
    $account = $this->drupalCreateUser();
    // Create a ticket for this account.
    $ticket = $ticket_service->getTicket($user);
    list($remotedb_uid, $timestamp, $hashed_pass) = explode('/', $ticket);
    // Validate ticket.
    $remote_account = $ticket_server->validateTicket($remotedb_uid, $timestamp, $hashed_pass);
  }
}
