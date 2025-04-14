<?php

namespace Drupal\remotedb_sso;

use Drupal\Core\Session\AccountInterface;
use Drupal\remotedb\Entity\RemotedbInterface;
use Drupal\remotedbuser\Entity\RemotedbUserInterface;
use Drupal\remotedbuser\Entity\RemotedbUserStorageInterface;

/**
 * Class for requesting a ticket for SSO from the remote database.
 */
class TicketService implements TicketServiceInterface {

  /**
   * A remote database.
   *
   * @var \Drupal\remotedb\Entity\RemotedbInterface
   */
  private $remotedb;

  /**
   * The storage for remotedbuser entities.
   */
  protected RemotedbUserStorageInterface $remotedbUserStorage;

  /**
   * Constructs a new TicketService object.
   *
   * @param \Drupal\remotedb\Entity\RemotedbInterface $remotedb
   *   The remote database to use.
   * @param \Drupal\remotedbuser\Entity\RemotedbUserStorageInterface $remotedb_user_storage
   *   The storage for remotedbuser entities.
   */
  public function __construct(RemotedbInterface $remotedb, RemotedbUserStorageInterface $remotedb_user_storage) {
    $this->remotedb = $remotedb;
    $this->remotedbUserStorage = $remotedb_user_storage;
  }

  /**
   * {@inheritdoc}
   */
  public function getTicket(AccountInterface $account): string {
    return $this->sendRequest('ticket.retrieve', [$account->getEmail(), 'mail']);
  }

  /**
   * {@inheritdoc}
   */
  public function validateTicket($remotedb_uid, $timestamp, $hash): ?RemotedbUserInterface {
    if ($this->sendRequest('ticket.validate', [$remotedb_uid, $timestamp, $hash])) {
      // Get account details from the remote database.
      return $this->remotedbUserStorage->loadBy($remotedb_uid, RemotedbUserStorageInterface::BY_ID);
    }
    return NULL;
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
  protected function sendRequest(string $method, array $params = []) {
    return $this->remotedb->sendRequest($method, $params);
  }

}
