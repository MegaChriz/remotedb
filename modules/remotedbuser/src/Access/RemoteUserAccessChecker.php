<?php

declare(strict_types=1);

namespace Drupal\remotedbuser\Access;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\AccountProxyInterface;

/**
 * Checks access to remote user retrieval features.
 */
class RemoteUserAccessChecker {

  /**
   * Current user proxy service.
   */
  protected readonly AccountProxyInterface $currentUser;

  /**
   * Constructs remote user access checker.
   *
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user proxy service.
   */
  public function __construct(AccountProxyInterface $current_user) {
    $this->currentUser = $current_user;
  }

  /**
   * Checks if user may retrieve users from the remote database.
   *
   * @param \Drupal\Core\Session\AccountInterface|null $account
   *   The account to check access for.
   */
  public function hasAccess(?AccountInterface $account = NULL): bool {
    if (!$account instanceof AccountInterface) {
      $account = $this->currentUser;
    }

    return $account->hasPermission('remotedb.administer')
      || $account->hasPermission('remotedbuser.getuser');
  }

}
