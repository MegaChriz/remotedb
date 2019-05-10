<?php

namespace Drupal\remotedbuser;

use Drupal\user\UserAuthInterface;

/**
 * Interface for the remotedbuser.authentication service.
 */
interface RemotedbUserAuthenticationInterface extends UserAuthInterface {

  /**
   * Remote login methods.
   *
   * @var int
   */
  const REMOTEONLY = 0;
  const REMOTEFIRST = 1;
  const LOCALFIRST = 2;

  /**
   * Authenticates name/password against the remote database and copies over
   * the remote user if needed.
   *
   * @return int|false
   *   The user's uid on success, or FALSE on failure to authenticate.
   */
  public function remoteAuthenticate($name, $password);

}
