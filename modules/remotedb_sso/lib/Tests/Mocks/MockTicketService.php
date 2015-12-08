<?php

/**
 * @file
 * Contains \Drupal\remotedb_sso\Tests\Mocks\MockTicketService.
 */

namespace Drupal\remotedb_sso\Tests\Mocks;

use Drupal\remotedb_sso\TicketServiceInterface;

class MockTicketService implements TicketServiceInterface {
  /**
   * Implements TicketInterface::getTicket().
   */
  public function getTicket($account) {
    $uid = 0;
    if (!empty($account->remotedb_uid)) {
      $uid = $account->remotedb_uid;
    }
    return implode('/', array($uid, REQUEST_TIME, user_password()));
  }

  /**
   * Implements TicketInterface::validateTicket().
   */
  public function validateTicket($remotedb_uid, $timestamp, $hash) {
    $controller = entity_get_controller('remotedb_user');

    // Get account details from the remote database.
    return $controller->loadBy($remotedb_uid);
  }
}
