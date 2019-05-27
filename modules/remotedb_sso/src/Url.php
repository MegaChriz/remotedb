<?php

namespace Drupal\remotedb_sso;

/**
 * Class for generating SSO urls.
 */
class Url {

  /**
   * Creates an SSO Goto URL for the specified text.
   *
   * @param string $site
   *   The site to replace in the url.
   * @param string $text
   *   The text to replace urls in.
   *
   * @return string
   *   The text where in the URLs are modified.
   */
  public static function createSSOGotoUrl($site, $text) {
    // Remove whitespace.
    $site = trim($site);
    // Make $site regex safe first.
    $site = preg_quote($site, '/');
    // Now replace the URLS.
    return preg_replace_callback('/http:\/\/(' . $site . ')\/?(.*?)\"/i', [__CLASS__, 'helper'], $text);
  }

  /**
   *
   */
  public static function helper($a) {
    $path = 'sso/goto';
    $options = [
      'query' => [
        'site' => $a[1],
      ],
      'absolute' => TRUE,
    ];
    if (!empty($a[2])) {
      $options['query']['path'] = $a[2];
    }
    // @FIXME
    // url() expects a route name or an external URI.
    // return url($path, $options) . '"';
  }

  /**
   * Generates a SSO Login link.
   *
   * @param string $site
   *   The site to generate an Url for.
   * @param string $path
   *   The path for the website.
   *
   * @return string
   *   The generated SSO Url.
   */
  public static function generateSSOLoginLink($site, $path = NULL) {
    return 'http://' . $site . '/sso/login/' . $ticket . '/' . $path;
  }

}
