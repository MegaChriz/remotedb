<?php

namespace Drupal\remotedb_sso;

use Drupal\Core\Session\AccountInterface;
use Drupal\remotedbuser\Entity\RemotedbUserInterface;

/**
 * Interface for the sso ticket service.
 */
interface TicketServiceInterface {

  /**
   * Generate a ticket for the given user.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account to generate a ticket for.
   *
   * @return string
   *   The ticket.
   */
  public function getTicket(AccountInterface $account): string;

  /**
   * Validate a ticket.
   *
   * @param int|string $remotedb_uid
   *   The remote user uid.
   * @param int $timestamp
   *   The time the ticket was generated.
   * @param string $hash
   *   The generated ticket hash.
   *
   * @return \Drupal\remotedbuser\Entity\RemotedbUserInterface|null
   *   An instance of RemotedbUserInterface, if the ticket was valid.
   *   NULL otherwise.
   */
  public function validateTicket(int|string $remotedb_uid, int $timestamp, string $hash): ?RemotedbUserInterface;

}
