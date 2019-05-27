<?php

namespace Drupal\remotedb_sso;

/**
 * Interface for generating SSO urls.
 */
interface UrlInterface {

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
  public function createSsoGotoUrl($site, $text);

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
  public function generateSsoLoginLink($site, $path = NULL);

}
