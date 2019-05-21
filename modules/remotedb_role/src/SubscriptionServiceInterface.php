<?php

namespace Drupal\remotedb_role;

/**
 *
 */
interface SubscriptionServiceInterface {

  /**
   * Retrieves a list of subscriptions for the given user.
   *
   * @param object $account
   *   The account to get subscriptions for.
   *
   * @return array
   *   A list of subscriptions.
   */
  public function getSubscriptions($account);

}
