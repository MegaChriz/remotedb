<?php

namespace Drupal\remotedb\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface for defining a remote database entity.
 */
interface RemotedbInterface extends ConfigEntityInterface {

  // ---------------------------------------------------------------------------
  // GETTERS
  // ---------------------------------------------------------------------------

  /**
   * Returns the used url.
   *
   * @return string
   *   The url of the remote database connection.
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
   */
  public function setHeader($header, $value);

  // ---------------------------------------------------------------------------
  // ACTION
  // ---------------------------------------------------------------------------

  /**
   * Sends a request to the XML-RPC server.
   *
   * @param string $method
   *   The method to call on the server.
   * @param array $params
   *   An array of parameters.
   *
   * @return string
   *   The XML-RPC Result.
   */
  public function sendRequest($method, array $params = []);

}
