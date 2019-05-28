<?php

namespace Drupal\remotedb_sso;

/**
 * Interface for factory for instantiating ticket service.
 */
interface TicketServiceFactoryInterface {

  /**
   * Returns the ticket service to use.
   *
   * @return \Drupal\remotedb_sso\TicketServiceInterface
   *   The ticket service.
   */
  public function get();

}
