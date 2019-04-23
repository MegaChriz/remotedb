<?php

namespace Drupal\remotedbuser;

/**
 * Interface for the remotedbuser.authentication service.
 */
interface RemotedbUserAuthenticationInterface {

  /**
   * Remote login methods.
   *
   * @var int
   */
  const REMOTEDB_REMOTEONLY = 0;
  const REMOTEDB_REMOTEFIRST = 1;
  const REMOTEDB_LOCALFIRST = 2;

  /**
   * Try to validate the user's login credentials locally.
   *
   * @param string $name
   *   User name to authenticate.
   * @param string $password
   *   A plain-text password, such as trimmed text from form values.
   *
   * @return int|bool
   *   The user's uid on success, or FALSE on failure to authenticate.
   */
  function authenticate($name, $password);

  /**
   * Authenticates name/password against the remote database and copies over
   * the remote user if needed.
   *
   * @return int|false
   *   The user's uid on success, or FALSE on failure to authenticate.
   *
   * @todo Deal with conflicts.
   */
  function remoteAuthenticate($name, $password);

}
