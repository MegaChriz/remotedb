<?php

namespace Drupal\remotedb\Entity;

use Drupal\Core\Config\Entity\ConfigEntityStorageInterface;

/**
 * Provides an interface for remote database storage.
 */
interface RemotedbStorageInterface extends ConfigEntityStorageInterface {

  /**
   * Returns a list of entities as options.
   *
   * @param array $entities
   *   (optional) A list of entities.
   *   Defaults to all entities.
   *
   * @return array
   *   A list of choosable options in forms.
   */
  public function options(array $entities = NULL);

}
