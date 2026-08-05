<?php

namespace Drupal\remotedb_sso;

use Drupal\Core\Entity\EntityTypeManagerInterface;
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
   * The entity type manager.
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Constructs a new TicketService object.
   *
   * @param \Drupal\remotedb\Entity\RemotedbInterface $remotedb
   *   The remote database to use.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(RemotedbInterface $remotedb, EntityTypeManagerInterface $entity_type_manager) {
    $this->remotedb = $remotedb;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function getTicket(AccountInterface $account): string {
    $ticket = $this->sendRequest('ticket.retrieve', [$account->getEmail(), 'mail']);
    if (!is_string($ticket)) {
      return '';
    }
    return $ticket;
  }

  /**
   * {@inheritdoc}
   */
  public function validateTicket(int|string $remotedb_uid, int $timestamp, string $hash): ?RemotedbUserInterface {
    if ((bool) $this->sendRequest('ticket.validate', [$remotedb_uid, $timestamp, $hash])) {
      // Get account details from the remote database.
      return $this->getRemotedbUserStorage()->loadBy($remotedb_uid, RemotedbUserStorageInterface::BY_ID);
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

  /**
   * Gets the remotedb_user storage handler.
   */
  protected function getRemotedbUserStorage(): RemotedbUserStorageInterface {
    $storage = $this->entityTypeManager->getStorage('remotedb_user');
    if (!$storage instanceof RemotedbUserStorageInterface) {
      throw new \LogicException('Expected remotedb_user storage to implement RemotedbUserStorageInterface.');
    }
    return $storage;
  }

}
