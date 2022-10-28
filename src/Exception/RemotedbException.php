<?php

namespace Drupal\remotedb\Exception;

use Drupal\Core\Logger\RfcLogLevel;
use Drupal\Core\Messenger\MessengerTrait;
use Exception;

/**
 * Base class for remotedb exceptions.
 */
class RemotedbException extends Exception {

  use MessengerTrait;

  /**
   * Prints error message on screen.
   */
  public function printMessage($severity = 'error') {
    $this->messenger()->addMessage($this->getMessage(), $severity);
  }

  /**
   * Logs error in watchdog.
   */
  public function logError($severity = RfcLogLevel::ERROR) {
    watchdog_exception('remotedb', $this, NULL, [], $severity);
  }

}
