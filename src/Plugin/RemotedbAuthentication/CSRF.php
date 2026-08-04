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
    $params = [
      'user.token' => [],
    ];
    $this->remotedb->setHeader('X-CSRF-Token', NULL);
    $url = $this->remotedb->getUrl();
    if (!is_string($url)) {
      return FALSE;
    }
    $token = xmlrpc($url, $params, $this->remotedb->getHeaders());
    if (is_array($token) && isset($token['token'])) {
      $this->remotedb->setHeader('X-CSRF-Token', $token['token']);
      return TRUE;
    }
    return FALSE;
  }

}
