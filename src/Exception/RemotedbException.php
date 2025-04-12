<?php

namespace Drupal\remotedb\Exception;

use Drupal\Core\Messenger\MessengerTrait;
use Drupal\Core\Utility\Error;
use Psr\Log\LogLevel;

/**
 * Base class for remotedb exceptions.
 */
class RemotedbException extends \Exception {

  use MessengerTrait;

  /**
   * Prints error message on screen.
   */
  public function printMessage($severity = 'error') {
    $this->messenger()->addMessage($this->getMessage(), $severity);
  }

  /**
   * Logs error in watchdog.
   *
   * @param string $level
   *   The PSR log level. Must be valid constant in \Psr\Log\LogLevel.
   */
  public function logError($level = LogLevel::ERROR) {
    $logger = \Drupal::logger('remotedb');
    Error::logException($logger, $this, Error::DEFAULT_ERROR_MESSAGE, [], $level);
  }

}
