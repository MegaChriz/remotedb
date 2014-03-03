<?php
/**
 * @file
 * Exception classes defined by RemoteDB
 */

/**
 * Base class for RemoteDB Exceptions
 */
class RemoteDBException extends Exception {
  /**
   * Prints error message on screen.
   */
  public function printMessage($severity = 'error') {
    drupal_set_message($this->getMessage(), $severity);
  }

  /**
   * Logs error in watchdog
   */
  public function logError($severity = WATCHDOG_ERROR) {
    watchdog('RemoteDB', $this->getMessage(), array(), $severity);
  }
}

/**
 * Exceptions for when RemoteDB is temporary disabled.
 */
class RemoteDBDisabledException extends Exception { }

/**
 * Invalid URL Exception class
 */
class RemoteDBInvalidURLException extends RemoteDBException { }
