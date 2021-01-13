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
//    d(get_defined_vars());
    // Remove whitespace.
    $site = trim($site);
    // Make $site regex safe first.
    $site = preg_quote($site, '/');

    // Prepare protocols pattern for absolute URLs.
    // \Drupal\Component\Utility\UrlHelper::stripDangerousProtocols() will
    // replace any bad protocols with HTTP, so we need to support the identical
    // list. While '//' is technically optional for MAILTO only, we cannot
    // cleanly differ between protocols here without hard-coding MAILTO, so '//'
    // is optional for all protocols.
    // @see \Drupal\Component\Utility\UrlHelper::stripDangerousProtocols()
    $protocols = \Drupal::getContainer()->getParameter('filter_protocols');
    $protocols = implode(':(?://)?|', $protocols) . ':(?://)?';

    $valid_url_path_characters = "[\p{L}\p{M}\p{N}!\*\';:=\+,\.\$\/%#\[\]\-_~@&]";

    // Allow URL paths to contain balanced parens.
    // 1. Used in Wikipedia URLs like /Primer_(film).
    // 2. Used in IIS sessions like /S(dfd346)/.
    $valid_url_balanced_parens = '\(' . $valid_url_path_characters . '+\)';

    // Valid end-of-path characters (so /foo. does not gobble the period).
    // 1. Allow =&# for empty URL parameters and other URL-join artifacts.
    $valid_url_ending_characters = '[\p{L}\p{M}\p{N}:_+~#=/]|(?:' . $valid_url_balanced_parens . ')';

    $valid_url_query_chars = '[a-zA-Z0-9!?\*\'@\(\);:&=\+\$\/%#\[\]\-_\.,~|]';
    $valid_url_query_ending_chars = '[a-zA-Z0-9_&=#\/]';

    // Full path.
    // and allow @ in a url, but only in the middle. Catch things like
    // http://example.com/@user/.
    $valid_url_path = '(?:(?:' . $valid_url_path_characters . '*(?:' . $valid_url_balanced_parens . $valid_url_path_characters . '*)*' . $valid_url_ending_characters . ')|(?:@' . $valid_url_path_characters . '+\/))';

    // Prepare trail pattern.
    $trail = '(' . $valid_url_path . '*)?(\\?' . $valid_url_query_chars . '*' . $valid_url_query_ending_chars . ')?';

    // Match absolute URLs.
    $url_pattern = "($protocols)($site)/?(?:$trail)?";
    $pattern = "`($url_pattern)`u";

    // Now replace the URLS.
    return preg_replace_callback($pattern, [$this, 'createSsoGotoUrlCallback'], $text);
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
  protected function createSsoGotoUrlCallback(array $matches) {
    $options = [
      'query' => [
        'site' => $matches[3],
      ],
      'absolute' => TRUE,
    ];
    if (!empty($matches[4])) {
      $options['query']['path'] = $matches[4];
    }
    if (!empty($matches[5])) {
      $options['query']['path'] .= $matches[5];
    }

    return CoreUrl::fromRoute('remotedb_sso.goto', [], $options)->toString();
  }

  /**
   * {@inheritdoc}
   */
  public function generateSsoLoginLink($site, $path = NULL) {
    return 'http://' . $site . '/sso/login/' . $ticket . '/' . $path;
  }

}
