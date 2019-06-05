<?php

namespace Drupal\remotedb_sso_test;

use Drupal\Core\Session\AccountInterface;
use Drupal\remotedb_sso\TicketServiceInterface;
use Drupal\remotedbuser\Entity\RemotedbUserStorageInterface;

/**
 * A mocked ticket service to be used in functional tests.
 */
class MockTicketService implements TicketServiceInterface {

  /**
   * {@inheritdoc}
   */
  public function getTicket(AccountInterface $account) {
    $uid = 0;
    if (!empty($account->remotedb_uid->value)) {
      $uid = $account->remotedb_uid->value;
    }
    return implode('/', [
      $uid,
      \Drupal::time()->getRequestTime(),
      user_password(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function validateTicket($remotedb_uid, $timestamp, $hash) {
    // Get account details from the remote database.
    return \Drupal::entityTypeManager()->getStorage('remotedb_user')
      ->loadBy($remotedb_uid, RemotedbUserStorageInterface::BY_ID);
  }

}
