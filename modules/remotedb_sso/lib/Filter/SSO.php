<?php

/**
 * @file
 * Contains \Drupal\remotedb_sso\Filter\SSO.
 */

namespace Drupal\remotedb_sso\Filter;

//use Drupal\remotedb\Component\ServiceContainer;
use Drupal\remotedb\Exception\RemotedbException;

class SSO {
  /**
   * Creates SSO links from links to certain external websites.
   *
   * @param string $text
   *   The text to filter.
   * @param array $sites
   *   (optional) The external sites to replace in $text.
   *   May also be a string. In that case the string will split.
   *   Takes variable 'remotedb_sso_websites' if omitted.
   *
   * @return string $text
   */
  public function filterText($text, $sites = array()) {
    try {
      // Check if the remote database is available.
      // @todo Check.
      /*
      $container = new ServiceContainer();
      $rd_service = $container->get('remoteduser', 'remotedb');
      if (!$rd_service) {
        return $text;
      }
      $remotedb = $rd_service->get();
      if (!$remotedb) {
        // Remote database is not available. Arbort.
        return $text;
      }
      */

      if (
        (is_array($sites) && count($sites) < 1)
        || (is_string($sites) && !$sites)
      ) {
        $sites = remotedb_sso_variable_get('websites');
      }
      if (is_string($sites)) {
        $sites = explode("\n", $sites);
      }

      $sso_url = url('sso/goto/', array('absolute' => TRUE));

      foreach ($sites as $site) {
        // Remove whitespace.
        $site = trim($site);
        // Make $site regex safe first.
        $site = preg_quote($site);
        // Now replace the URLS.
        $text = preg_replace('/http:\/\/(' . $site . '.*?)\"\>/i', $sso_url . '\\1">', $text);
      }
    }
    catch (RemotedbException) {
      // Ignore any remote database exceptions.
    }
    return $text;
  }
}