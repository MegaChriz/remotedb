<?php

namespace Drupal\remotedb\Exception;

use \Exception;

class RemotedbException extends Exception {
  /**
   * Prints error message on screen.
   */
  public function printMessage($severity = 'error') {
    drupal_set_message($this->getMessage(), $severity);
  }

  /**
   * Logs error in watchdog.
   */
  public function logError($severity = WATCHDOG_ERROR) {
    watchdog_exception('remotedb', $this, NULL, array(), $severity);
  }
}
