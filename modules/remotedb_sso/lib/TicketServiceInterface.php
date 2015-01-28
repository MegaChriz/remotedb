<?php

/**
 * @file
 * Contains \Drupal\remotedb_sso\TicketServiceInterface.
 */

namespace Drupal\remotedb_sso;

interface TicketServiceInterface {
  /**
   * Generate a ticket for the given user.
   *
   * @param object $account
   *   The account to generate a ticket for.
   *
   * @return string
   *   The ticket.
   */
  public function getTicket($account);

  /**
   * Validate a ticket.
   *
   * @param integer $remotedb_uid
   *   The remote user uid.
   * @param integer $timestamp
   *   The time the ticket was generated.
   * @param string $hash
   *   The generated ticket hash.
   *
   * @return \Drupal\remotedbuser\Entity\RemotedbUserInterface.
   *   An instance of RemotedbUserInterface, if the ticket was valid.
   *   NULL otherwise.
   */
  public function validateTicket($remotedb_uid, $timestamp, $hash);
}
