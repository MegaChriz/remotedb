<?php

/**
 * @file
 * Contains \Drupal\remotedb_role\SubscriptionService.
 */

namespace Drupal\remotedb_role;

use Drupal\remotedb\Entity\RemotedbInterface;

class SubscriptionService implements SubscriptionServiceInterface {
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
   * Subscription object constructor.
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
   * Implements SubscriptionInterface::getSubscription().
   */
  public function getSubscription($account) {
    return $this->sendRequest('dbsubscription.retrieve', array($account->mail, 'mail'));
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
  protected function sendRequest($method, array $params = array()) {
    return $this->remotedb->sendRequest($method, $params);
  }
}
