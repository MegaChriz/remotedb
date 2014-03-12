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
   *   ?
   * @param string $pass
   *   ?
   *
   * @return \Drupal\remotedbuser\Entity\RemotedbUserInterface.
   *   ?
   */
  public function validateTicket($remotedb_uid, $timestamp, $pass);
}
