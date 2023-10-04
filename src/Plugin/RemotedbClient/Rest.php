<?php

namespace Drupal\remotedb\Plugin\RemotedbClient;

use Drupal\remotedb\Exception\RemotedbException;
use Drupal\remotedb\Plugin\ClientBase;

/**
 * Sends request to a rest server.
 *
 * @RemotedbClient(
 *   id = "rest",
 *   title = @Translation("REST"),
 *   description = @Translation("For sending requests to REST servers.")
 * )
 */
class Rest extends ClientBase {

  /**
   * {@inheritdoc}
   */
  public function sendRequest(string $url, array $args, array $headers) {
    // @todo implement.
  }

}
