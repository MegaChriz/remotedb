<?php

namespace Drupal\remotedb\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Component\Plugin\ConfigurableInterface;
use Drupal\Component\Plugin\DependentPluginInterface;

/**
 * Interface for remote database client plugins.
 */
interface ClientInterface extends DependentPluginInterface, PluginInspectionInterface {

  /**
   * Returns the administrative label for this client.
   *
   * @return string
   *   The client's administrative label.
   */
  public function getLabel(): string;

  /**
   * Returns the administrative description for this client.
   *
   * @return string
   *   The client's description.
   */
  public function getDescription(): string;

  /**
   * Sends a request to the server.
   *
   * @param string $url
   *   Absolute URL of the server.
   * @param array $args
   *   An associative array.
   * @param array $headers
   *   (optional) An array of headers to pass along.
   *
   * @return mixed
   *   Result of the request.
   */
  public function sendRequest(string $url, array $args, array $headers);

}
