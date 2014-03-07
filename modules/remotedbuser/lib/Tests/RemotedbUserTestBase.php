<?php

/**
 * @file
 * Contains \Drupal\remotedbuser\Tests\RemotedbUserTestBase.
 */

namespace Drupal\remotedbuser\Tests;

use Drupal\remotedb\Tests\RemotedbTestBase;

class RemotedbUserTestBase extends RemotedbTestBase {
  /**
   * Overrides DrupalWebTestCase::setUp().
   */
  protected function setUp(array $modules = array()) {
    $modules = array_merge($modules, array('remotedbuser'));
    parent::setUp($modules);
  }

  /**
   * Creates a remote user.
   *
   * @param array $values
   *   (optional) The values to use for the remote user.
   *
   * @return \Drupal\remotedbuser\Entity\RemotedbUserInterface
   *   A remote user object.
   */
  protected function remotedbCreateRemoteUser(array $values = array()) {
    if (empty($values['name'])) {
      $values['name'] = $this->randomName();
    }

    $values += array(
      'uid' => 2, // @todo generate.
      'mail' => $values['name'] . '@example.com',
      'status' => 1,
      'pass' => user_password(),
    );

    // Hash password.
    if ($values['pass']) {
      $values['pass_raw'] = $values['pass'];
      require_once DRUPAL_ROOT . '/includes/password.inc';
      $values['pass'] = user_hash_password($values['pass']);
    }

    $account = entity_create('remotedbuser', $values);
    return $account;
  }
}
