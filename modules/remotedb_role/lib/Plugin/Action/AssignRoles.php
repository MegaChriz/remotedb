<?php

/**
 * @file
 * Contains \Drupal\remotedb_role\Plugin\Action\AssignRoles.
 */

namespace Drupal\remotedb_role\Plugin\Action;

use Drupal\remotedb_role\SubscriptionServiceInterface;

/**
 * Assigns or unassigns roles based on the subscriptions the user has.
 *
 * @Action(
 *   id = "remotedb_role_assign_roles",
 *   label = @Translation("Assign and unassign roles"),
 *   type = "user"
 * )
 */
class AssignRoles {
  /**
   * A service to get subscriptions from.
   *
   * @var \Drupal\remotedb_role\SubscriptionServiceInterface
   */
  protected $subscription_servic;

  /**
   * AssignRoles object constructor.
   *
   * @param \Drupal\remotedb_role\SubscriptionServiceInterface
   *   A service that can return subscriptions.
   *
   * @return \Drupal\remotedb_role\Plugin\Action\AssignRoles
   */
  public function __construct(SubscriptionServiceInterface $subscription_service) {
    $this->subscription_service = $subscription_service;
  }

  /**
   * Executes action.
   *
   * @param object $account
   *   The account to assign/unassign roles for.
   *
   * @return array
   *   An array containing the roles that were assigned/unassigned:
   *   - (array) roles that were assigned.
   *   - (array) roles that were unassigned.
   */
  public function execute($account = NULL) {
    if (empty($account)) {
      // No account given. Abort.
      return;
    }

    // Keep track of roles that were assigned and roles that were unassigned.
    $assign = array();
    $unassign = array();
    $changed = FALSE;

    $role_names = user_roles();
    $role_settings = remotedb_role_get_active_settings();
    $subscriptions = $this->subscription_service->getSubscriptions($account);

    if (count($subscriptions) > 0) {
      // Loop through all roles and prepare a list of assign/unassign roles.
      foreach ($role_settings as $rid => $role_setting) {
        $unassign[$rid] = $role_names[$rid];
        foreach ($remotedb_subscriptions as $remotedb_subscription) {
          if (in_array($remotedb_subscription['subscription_id'], $role_setting['subscriptions'])) {
            $assign[$rid] = $role_names[$rid];
            unset($unassign[$rid]);
          }
        }
      }

      // Unassign roles.
      if (count($unassign) > 0) {
        foreach ($unassign as $rid => $role_name) {
          if (isset($account->roles[$rid])) {
            unset($account->roles[$rid]);
            $changed = TRUE;
          }
        }
      }
      // Assign roles.
      if (count($assign) > 0) {
        foreach ($assign as $rid => $role_name) {
          if (!isset($account->roles[$rid])) {
            $account->roles[$rid] = $role_names[$rid];
            $changed = TRUE;
          }
        }
      }
    }

    if ($changed) {
      user_save($account);
    }

    return array(
      $assign,
      $unassign,
    );
  }
}
