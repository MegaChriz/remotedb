<?php

namespace Drupal\remotedb\Plugin\RemotedbClient;

use Drupal\remotedb\Exception\RemotedbException;
use Drupal\remotedb\Plugin\ClientBase;

/**
 * Sends request to a xmlrpc server.
 *
 * @RemotedbClient(
 *   id = "xmlrpc",
 *   title = @Translation("XML-RPC"),
 *   description = @Translation("For sending requests to XML-RPC servers.")
 * )
 */
class XmlRpc extends ClientBase {

  /**
   * {@inheritdoc}
   */
  public function sendRequest(string $url, array $args, array $headers) {
    $result = xmlrpc($url, $args, $headers);
    if ($result === FALSE) {
      $error = xmlrpc_error();
      // Throw exception in case of errors.
      if (is_object($error) && !empty($error->is_error)) {
        throw new RemotedbException($error->message, $error->code);
      }
    }
    return $result;
  }

}
