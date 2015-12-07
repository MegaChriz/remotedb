<?php

/**
 * @file
 * Contains \Drupal\remotedb_sso\Util.
 */

namespace Drupal\remotedb_sso;

use Drupal\remotedb_sso\TicketServiceInterface;

/**
 * Class with Util functions for remotedb_sso.
 */
class Util {
  /**
   * Gets ticket service to use.
   *
   * @return \Drupal\remotedb_sso\TicketServiceInterface | NULL
   *   An instance of TicketServiceInterface, if found.
   *   NULL otherwise.
   */
  public static function getTicketService() {
    $remotedb = remotedbuser_get_remotedb();
    if ($remotedb) {
      $ticket_service_class = static::variableGet('ticket_service');
      $ticket_service = new $ticket_service_class($remotedb);
      if ($ticket_service instanceof TicketServiceInterface) {
        return $ticket_service;
      }
    }
    return NULL;
  }

  /**
   * Gets a remotedb_sso setting.
   *
   * @param string $name
   *   The setting to get.
   *
   * @return mixed
   *   The value of the setting.
   */
  public static function variableGet($name) {
    $value = variable_get('remotedb_sso_' . $name, NULL);
    if (is_null($value)) {
      switch ($name) {
        case 'ticket_service':
          return 'Drupal\remotedb_sso\TicketService';
      }
    }
    return $value;
  }
}
