<?php

namespace Drupal\remotedb_role;

/**
 * Interface for factory for instantiating subscription service.
 */
interface SubscriptionServiceFactoryInterface {

  /**
   * Returns the subscription service to use.
   *
   * @return \Drupal\remotedb_role\SubscriptionServiceInterface
   *   The subscription service.
   */
  public function get();

}
