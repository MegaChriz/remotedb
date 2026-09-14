<?php

namespace Drupal\remotedb\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\remotedb\AuthenticationPluginCollection;
use Drupal\remotedb\Plugin\AuthenticationInterface;
use Drupal\remotedb\Plugin\RemotedbTransportInterface;

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
   * Returns the transport plugin ID.
   *
   * @return string
   *   The plugin ID, e.g. xmlrpc or rest.
   */
  public function getTransport(): string;

  /**
   * Returns transport-specific settings.
   *
   * @return array
   *   Settings keyed by transport plugin ID.
   */
  public function getTransportSettings(): array;

  /**
   * Returns settings for a transport plugin.
   *
   * @param string|null $plugin_id
   *   Transport plugin ID, or NULL for the selected transport.
   *
   * @return array
   *   Configuration for that plugin.
   */
  public function getTransportPluginSettings(?string $plugin_id = NULL): array;

  /**
   * Returns the configured transport plugin.
   *
   * @return \Drupal\remotedb\Plugin\RemotedbTransportInterface
   *   The transport plugin instance.
   */
  public function getTransportPlugin(): RemotedbTransportInterface;

  /**
   * Gets the authentication method plugin collection.
   *
   * @return \Drupal\remotedb\AuthenticationPluginCollection
   *   The authentication plugin collection.
   */
  public function getAuthenticationMethods(): AuthenticationPluginCollection;

  /**
   * Gets a single authentication method plugin.
   *
   * @param string $instance_id
   *   The authentication method instance ID.
   *
   * @return \Drupal\remotedb\Plugin\AuthenticationInterface
   *   The authentication method plugin.
   */
  public function getAuthenticationMethod(string $instance_id): AuthenticationInterface;

  /**
   * Sets the configuration for an authentication method plugin instance.
   *
   * @param string $instance_id
   *   The ID of the authentication method plugin instance.
   * @param array $configuration
   *   The authentication method plugin instance configuration.
   *
   * @return $this
   */
  public function setAuthenticationMethodConfig(string $instance_id, array $configuration): static;

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
   * Sends a request to the remote server.
   *
   * @param string $method
   *   The method to call on the server.
   * @param array $params
   *   An array of parameters.
   *
   * @return mixed
   *   The result of the request.
   */
  public function sendRequest(string $method, array $params = []): mixed;

}
