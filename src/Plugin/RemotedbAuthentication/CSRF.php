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
