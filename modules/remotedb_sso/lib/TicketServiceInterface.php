<?php

/**
 * @file
 * Contains \Drupal\remotedb_sso\TicketServiceInterface.
 */

namespace Drupal\remotedb_sso;

use Drupal\remotedb\Entity\RemotedbInterface;

interface TicketServiceInterface {
  /**
   * Generate a ticket for the given user.
   *
   * @param object $account
   *   The account to generate an URL for.
   *
   * @return string
   *   The ticket.
   */
  public function getTicket($account);

  /**
   * Generate a ticket for the given user.
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
