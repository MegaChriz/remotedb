<?php

declare(strict_types=1);

namespace Drupal\remotedb\Access;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Checks access for remotedb entities.
 */
class RemotedbAccessChecker {

  /**
   * Checks whether the account may manage remotedb entities.
   *
   * @param string $operation
   *   The requested operation.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity being accessed.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account requesting access.
   */
  public function access(string $operation, EntityInterface $entity, AccountInterface $account): bool {
    return $account->hasPermission('remotedb.administer');
  }

}
