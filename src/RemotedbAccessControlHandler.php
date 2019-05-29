<?php

namespace Drupal\remotedb;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines an access controller for the remotedb entity.
 *
 * @see \Drupal\remotedb\Entity\Remotedb
 */
class RemotedbAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $remotedb, $operation, AccountInterface $account) {
    $has_perm = $account->hasPermission('remotedb.administer');

    switch ($operation) {
      case 'view':
      case 'create':
      case 'update':
      case 'delete':
        return AccessResult::allowedIf($has_perm);
    }

    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    $has_perm = $account->hasPermission('remotedb.administer');
    return AccessResult::allowedIf($has_perm);
  }

}
