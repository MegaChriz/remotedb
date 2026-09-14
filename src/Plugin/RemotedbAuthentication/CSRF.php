<?php

namespace Drupal\remotedb\Plugin\RemotedbAuthentication;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\remotedb\Attribute\RemotedbAuthentication;
use Drupal\remotedb\Plugin\AuthenticationBase;

/**
 * Authenticates by requesting a CSRF token.
 */
#[RemotedbAuthentication(
  id: 'csrf',
  title: new TranslatableMarkup('CSRF'),
  description: new TranslatableMarkup('Authenticates by requesting a CSRF token.')
)]
class Csrf extends AuthenticationBase {

  /**
   * Implements AuthenticationInterface::authenticate().
   */
  public function authenticate(): bool {
    $this->remotedb->setHeader('X-CSRF-Token', NULL);
    // Use the transport plugin directly. Remotedb::sendRequest() authenticates
    // first, and this plugin *is* that authentication, so calling sendRequest()
    // here would recurse. New authentication plugins may still call xmlrpc()
    // or HTTP clients themselves.
    $token = $this->remotedb->getTransportPlugin()->sendRequest('user.token', []);
    if (is_array($token) && isset($token['token'])) {
      $this->remotedb->setHeader('X-CSRF-Token', $token['token']);
      return TRUE;
    }
    return FALSE;
  }

}
