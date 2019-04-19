<?php

namespace Drupal\remotedb\Plugin\RemotedbAuthentication;

use Drupal\remotedb\Plugin\AuthenticationBase;

/**
 * Authenticates by requesting a CSRF token.
 *
 * @RemotedbAuthentication(
 *   id = "csrf",
 *   title = @Translation("CSRF"),
 *   description = @Translation("Authenticates by requesting a CSRF token.")
 * )
 */
class CSRF extends AuthenticationBase {

  /**
   * Implements AuthenticationInterface::authenticate().
   */
  public function authenticate() {
    $params = [
      'user.token' => [],
    ];
    $this->remotedb->setHeader('X-CSRF-Token', NULL);
    $token = xmlrpc($this->remotedb->getUrl(), $params, $this->remotedb->getHeaders());
    if (!empty($token) && isset($token['token'])) {
      $this->remotedb->setHeader('X-CSRF-Token', $token['token']);
      return TRUE;
    }
    return FALSE;
  }

}
