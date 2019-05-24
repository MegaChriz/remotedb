<?php

namespace Drupal\remotedb_role;

use Drupal\remotedb\Entity\RemotedbInterface;
use Drupal\user\UserInterface;

/**
 * Class for requesting subscriptions for a specific account.
 */
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
   * {@inheritdoc}
   */
  public function getSubscriptions(UserInterface $account) {
    return $this->sendRequest('dbsubscription.retrieve', [$account->getEmail(), 'mail']);
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
