<?php

namespace Drupal\remotedb_sso;

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
    // @FIXME
    // // @FIXME
    // // The correct configuration object could not be determined. You'll need to
    // // rewrite this call manually.
    // $value = variable_get('remotedb_sso_' . $name, NULL);
    if (is_null($value)) {
      switch ($name) {
        case 'ticket_service':
          return 'Drupal\remotedb_sso\TicketService';
      }
    }
    return $value;
  }

}
