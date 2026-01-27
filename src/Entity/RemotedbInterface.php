<?php

namespace Drupal\remotedb\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface for defining a remote database entity.
 */
interface RemotedbInterface extends ConfigEntityInterface {

  /**
   * Returns the used url.
   *
   * @return string|null
   *   The url of the remote database connection or null if the url is not yet
   *   defined.
   */
  public function getUrl(): ?string;

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
  public function getHeader(string $header): mixed;

  /**
   * Returns all headers.
   *
   * @return array
   *   An array of set headers.
   */
  public function getHeaders(): array;

  /**
   * Sets a header.
   *
   * @param string $header
   *   The header to set.
   * @param mixed $value
   *   The header's value.
   */
  public function setHeader(string $header, mixed $value): void;

  /**
   * Sends a request to the XML-RPC server.
   *
   * @param string $method
   *   The method to call on the server.
   * @param array $params
   *   An array of parameters.
   *
   * @return mixed
   *   The XML-RPC Result.
   */
  public function sendRequest(string $method, array $params = []): mixed;

}
