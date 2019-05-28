<?php

namespace Drupal\remotedb_sso\PathProcessor;

use Drupal\Core\PathProcessor\InboundPathProcessorInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Defines a path processor to rewrite file URLs.
 *
 * As the route system does not allow arbitrary amount of parameters convert
 * the file path to a query parameter on the request.
 */
class PathProcessorSsoLogin implements InboundPathProcessorInterface {

  /**
   * {@inheritdoc}
   */
  public function processInbound($path, Request $request) {
    if (strpos($path, '/sso/login/') === 0) {
      $pattern = '/^(\/sso\/login\/[0-9a-z]+\/[0-9a-z]+\/[0-9a-z]+)\/?(.*)/i';
      $login_path = preg_replace($pattern, '${1}', $path);
      $additional_path = preg_replace($pattern, '${2}', $path);
      $request->query->set('path', $additional_path);
      return $login_path;
    }
    return $path;
  }

}
