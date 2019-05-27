<?php
namespace Drupal\remotedb_sso;

class MockTicketService implements TicketServiceInterface {
  /**
   * Implements TicketInterface::getTicket().
   */
  public function getTicket($account) {
    $uid = 0;
    if (!empty($account->remotedb_uid)) {
      $uid = $account->remotedb_uid;
    }
    return implode('/', [$uid, REQUEST_TIME, user_password()]);
  }

  /**
   * Implements TicketInterface::validateTicket().
   */
  public function validateTicket($remotedb_uid, $timestamp, $hash) {
    $controller = \Drupal::entityTypeManager()->getStorage('remotedb_user');

    // Get account details from the remote database.
    return $controller->loadBy($remotedb_uid);
  }
}
