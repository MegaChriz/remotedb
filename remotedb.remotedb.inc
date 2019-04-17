<?php

/**
 * @file
 * Hooks for remotedb module.
 */

/**
 * Implements hook_remotedb_authentication().
 */
function remotedb_remotedb_authentication() {
  $namespace = 'Drupal\remotedb\Plugin\RemotedbAuthentication';

  return [
    'login' => [
      'id' => 'login',
      'title' => t('Login'),
      'description' => t('Logs in an user on the remote database.'),
      'class' => $namespace . '\Login',
    ],
    'csrf' => [
      'id' => 'csrf',
      'title' => t('CSRF'),
      'description' => t('Authenticates by requesting a CSRF token.'),
      'class' => $namespace . '\CSRF',
    ],
  ];
}
