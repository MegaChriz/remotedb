<?php

namespace Drupal\remotedbuser;

/**
 * Interface for the remotedbuser.configuration service.
 */
interface RemotedbUserConfigurationInterface {

  /**
   * Gets the default remote database for exchanging users.
   *
   * @return \Drupal\remotedb\Entity\RemotedbInterface|null
   *   A remote database, if configured. Null otherwise.
   */
  public function getDefault();

}