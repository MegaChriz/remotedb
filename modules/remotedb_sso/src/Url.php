<?php

namespace Drupal\remotedb_sso;

use Drupal\Core\Url as CoreUrl;

/**
 * Class for generating SSO urls.
 */
class Url implements UrlInterface {

  /**
   * {@inheritdoc}
   */
  public function createSsoGotoUrl($site, $text) {
    // Remove whitespace.
    $site = trim($site);
    // Make $site regex safe first.
    $site = preg_quote($site, '/');
    // Now replace the URLS.
    return preg_replace_callback('/https?:\/\/(' . $site . ')\/?(.*?)\"/i', [$this, 'createSsoGotoUrlCallback'], $text);
  }

  /**
   * Regular expression callback for ::createSsoGotoUrl().
   *
   * Creates an url.
   *
   * @param array $matches
   *   A list of matches from the regular expression.
   *
   * @return string
   *   The generated url.
   */
  protected function createSsoGotoUrlCallback($matches) {
    $options = [
      'query' => [
        'site' => $matches[1],
      ],
      'absolute' => TRUE,
    ];
    if (!empty($matches[2])) {
      $options['query']['path'] = $matches[2];
    }

    return CoreUrl::fromRoute('remotedb_sso.goto', [], $options)->toString() . '"';
  }

  /**
   * {@inheritdoc}
   */
  public function generateSsoLoginLink($site, $path = NULL) {
    return 'http://' . $site . '/sso/login/' . $ticket . '/' . $path;
  }

}
