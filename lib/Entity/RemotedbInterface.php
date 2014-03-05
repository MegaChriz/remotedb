<?php

/**
 * Contains Drupal\remotedb\Entity\RemotedbInterface.
 */

namespace Drupal\remotedb\Entity;

interface RemotedbInterface {
  // ---------------------------------------------------------------------------
  // GETTERS
  // ---------------------------------------------------------------------------

  /**
   * Returns label for this remote database.
   */
  public function label();

  /**
   * Returns the used url.
   */
  public function getUrl();

  /**
   * Returns all options.
   *
   * @return array
   *   An array of set options.
   */
  public function getOptions();

  /**
   * Gets a header.
   *
   * @param string $header
   *   The header to get.
   *
   * @return mixed
   *   The header's value if it exists.
   *   NULL otherwise.
   */
  public function getHeader($header);

  /**
   * Returns the latest result from a XML-RPC server.
   *
   * @return string
   *   The XML-RPC Result.
   */
  public function getResult();

  // ---------------------------------------------------------------------------
  // SETTERS
  // ---------------------------------------------------------------------------

  /**
   * Sets a header.
   *
   * @param string $header
   *   The header to set.
   * @param mixed $value
   *   The header's value.
   *
   * @return void
   */
  public function setHeader($header, $value);

  // ---------------------------------------------------------------------------
  // LOADING/SAVING
  // ---------------------------------------------------------------------------

  /**
   * Saves the remote database settings.
   */
  public function save();

  // ---------------------------------------------------------------------------
  // ACTION
  // ---------------------------------------------------------------------------

  /**
   * Send a request to the XML-RPC server.
   *
   * @param string $method
   *   The method to call on the server.
   * @param array $params
   *   An array of parameters.
   *
   * @return \Drupal\remotedb\Entity\RemotedbInterface
   */
  public function sendRequest($method, array $params = array());
}
