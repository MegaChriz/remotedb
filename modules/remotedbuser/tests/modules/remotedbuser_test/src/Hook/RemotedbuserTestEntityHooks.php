<?php

declare(strict_types=1);

namespace Drupal\remotedbuser_test\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\remotedbuser_test\Entity\RemotedbUserStorage;

/**
 * Entity hook implementations for remotedbuser_test.
 */
class RemotedbuserTestEntityHooks {

  /**
   * Implements hook_entity_type_build().
   */
  #[Hook('entity_type_build')]
  public function entityTypeBuild(array &$entity_types): void {
    if (isset($entity_types['remotedb_user'])) {
      $entity_types['remotedb_user']->setStorageClass(RemotedbUserStorage::class);
    }
  }

}
