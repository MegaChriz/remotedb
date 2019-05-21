<?php

/**
 * @file
 * Contains \Drupal\remotedb_sso\TicketService.
 */

namespace Drupal\remotedb_sso;

use Drupal\remotedb\Entity\RemotedbInterface;

class TicketService implements TicketServiceInterface {
  // ---------------------------------------------------------------------------
  // PROPERTIES
  // ---------------------------------------------------------------------------

  /**
   * A remote database.
   *
   * @var \Drupal\remotedb\Entity\RemotedbInterface
   */
  private $remotedb;

  // ---------------------------------------------------------------------------
  // CONSTRUCT
  // ---------------------------------------------------------------------------

  /**
   * Ticket object constructor.
   *
   * @param \Drupal\remotedb\Entity\RemotedbInterface $remotedb
   *   The remote datase to use.
   */
  public function __construct(RemotedbInterface $remotedb) {
    $this->remotedb = $remotedb;
  }

  // ---------------------------------------------------------------------------
  // ACTION
  // ---------------------------------------------------------------------------

  /**
   * Implements TicketInterface::getTicket().
   */
  public function getTicket($account) {
    return $this->sendRequest('ticket.retrieve', [$account->mail, 'mail']);
  }

  /**
   * Implements TicketInterface::validateTicket().
   */
  public function validateTicket($remotedb_uid, $timestamp, $hash) {
    if ($this->sendRequest('ticket.validate', [$remotedb_uid, $timestamp, $hash])) {
      $controller = \Drupal::entityTypeManager()->getStorage('remotedb_user');

      // Get account details from the remote database.
      return $controller->loadBy($remotedb_uid);
    }
  }

  /**
   * Sends a request to the remote database.
   *
   * @param string $method
   *   The method to call on the server.
   * @param array $params
   *   The parameters to send.
   *
   * @return mixed
   *   The result of the method call.
   * @throws RemotedbException
   *   In case the remote database object was not set.
   */
  protected function sendRequest($method, array $params = []) {
    return $this->remotedb->sendRequest($method, $params);
  }
}
