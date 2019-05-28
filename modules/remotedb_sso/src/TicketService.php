<?php

namespace Drupal\remotedb_sso;

use Drupal\remotedb\Entity\RemotedbInterface;

/**
 * Class for requesting a ticket for SSO from the remote database.
 */
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
   * Constructs a new TicketService object.
   *
   * @param \Drupal\remotedb\Entity\RemotedbInterface $remotedb
   *   The remote database to use.
   */
  public function __construct(RemotedbInterface $remotedb) {
    $this->remotedb = $remotedb;
  }

  // ---------------------------------------------------------------------------
  // ACTION
  // ---------------------------------------------------------------------------

  /**
   * {@inheritdoc}
   */
  public function getTicket($account) {
    return $this->sendRequest('ticket.retrieve', [$account->mail, 'mail']);
  }

  /**
   * {@inheritdoc}
   */
  public function validateTicket($remotedb_uid, $timestamp, $hash) {
    if ($this->sendRequest('ticket.validate', [$remotedb_uid, $timestamp, $hash])) {
      // Get account details from the remote database.
      return \Drupal::entityTypeManager()->getStorage('remotedb_user')
        ->loadBy($remotedb_uid);
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
   */
  protected function sendRequest($method, array $params = []) {
    return $this->remotedb->sendRequest($method, $params);
  }

}
