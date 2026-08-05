<?php

declare(strict_types=1);

namespace Drupal\remotedb_test\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\remotedb_test\Entity\MockRemotedb;

/**
 * Entity hook implementations for remotedb_test.
 */
class RemotedbTestEntityHooks {

  /**
   * Implements hook_entity_type_build().
   */
  #[Hook('entity_type_build')]
  public function entityTypeBuild(array &$entity_types): void {
    if (isset($entity_types['remotedb'])) {
      $entity_types['remotedb']->setClass(MockRemotedb::class);
    }
  }

}
