<?php

namespace Drupal\remotedb_role;

use Drupal\user\UserInterface;

/**
 * Interface for requesting subscriptions for an account.
 */
interface SubscriptionServiceInterface {

  /**
   * Retrieves a list of subscriptions for the given user.
   *
   * @param \Drupal\user\UserInterface $account
   *   The account to get subscriptions for.
   *
   * @return array
   *   A list of subscriptions.
   */
  public function getSubscriptions(UserInterface $account);

}
