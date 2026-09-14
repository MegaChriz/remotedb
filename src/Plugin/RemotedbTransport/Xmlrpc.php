<?php

namespace Drupal\remotedb\Plugin\RemotedbTransport;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\remotedb\Attribute\RemotedbTransport;
use Drupal\remotedb\Exception\RemotedbException;
use Drupal\remotedb\Plugin\TransportBase;

/**
 * Sends requests via XML-RPC, matching the historical sendRequest() logic.
 */
#[RemotedbTransport(
  id: 'xmlrpc',
  title: new TranslatableMarkup('XML-RPC'),
  description: new TranslatableMarkup('Connect using the Drupal XML-RPC protocol.')
)]
class Xmlrpc extends TransportBase {

  /**
   * {@inheritdoc}
   */
  public function sendRequest(string $method, array $params = []): mixed {
    $url = $this->remotedb->getUrl();
    if (!is_string($url) || $url === '') {
      throw new RemotedbException('The remote database URL is not set.');
    }
    if (!function_exists('xmlrpc')) {
      throw new RemotedbException('The XML-RPC module is not available.');
    }

    $args = [$method => $params];
    $result = xmlrpc($url, $args, $this->remotedb->getHeaders());
    if ($result === FALSE) {
      $error = xmlrpc_error();
      if (
        is_object($error)
        && property_exists($error, 'is_error')
        && $error->is_error
        && property_exists($error, 'message')
        && property_exists($error, 'code')
      ) {
        throw new RemotedbException((string) $error->message, (int) $error->code);
      }
    }
    return $result;
  }

}
