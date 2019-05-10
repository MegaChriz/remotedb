<?php

namespace Drupal\remotedb\Exception;

use Drupal\Core\Logger\RfcLogLevel;
use Exception;

/**
 * Base class for remotedb exceptions.
 */
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
  public function logError($severity = RfcLogLevel::ERROR) {
    watchdog_exception('remotedb', $this, NULL, [], $severity);
  }

}
