<?php

namespace Drupal\Tests\remotedbuser\Traits;

use Drupal\Core\Session\AccountInterface;
use Drupal\remotedbuser\Entity\RemotedbUser;

/**
 * Provides methods to create remote databases with default settings.
 *
 * This trait is meant to be used only by test classes.
 */
trait RemotedbUserCreationTrait {

  /**
   * Creates a remote database entity.
   *
   * @param array $values
   *   (optional) An associative array of values for the remote user entity.
   *
   * @return \Drupal\remotedbuser\Entity\RemotedbUserInterface
   *   The created remote user entity.
   */
  protected function createRemoteUser(array $values = []) {
    $uid = &drupal_static(__METHOD__, 2);

    // Generate uid.
    if (!isset($values['uid'])) {
      $values['uid'] = ++$uid;
    }
    // Make sure that the user gets a name.
    if (empty($values['name'])) {
      $values['name'] = $this->randomMachineName();
    }
    // Fill in other default values.
    $values += [
      'mail' => $values['name'] . '@example.com',
      'status' => 1,
      'pass' => user_password(),
    ];

    // Hash password.
    if ($values['pass']) {
      $values['pass_raw'] = $values['pass'];
      $values['pass'] = $this->hashPassword($values['pass']);
    }

    $remote_user = RemotedbUser::create($values);
    $remote_user->save();

    return $remote_user;
  }

  /**
   * Hashes a password.
   *
   * @param string $pass
   *   The plain password to hash.
   *
   * @return string
   *   The hashed password.
   */
  protected function hashPassword($pass) {
    return $this->container->get('password')->hash($pass);
  }

  /**
   * Creates a dummy account.
   *
   * The dummy account is mocked from '\Drupal\Core\Session\AccountInterface'.
   *
   * @param string $name
   *   The username for the dummy account.
   *
   * @return \Drupal\Core\Session\AccountInterface
   *   A mocked account object.
   */
  protected function createDummyAccount($name) {
    $dummy_account = $this->createMock(AccountInterface::class);
    $dummy_account->expects($this->any())
      ->method('getAccountName')
      ->will($this->returnValue($name));

    return $dummy_account;
  }

}
