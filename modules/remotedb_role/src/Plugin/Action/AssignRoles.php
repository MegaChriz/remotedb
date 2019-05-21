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
   * Configuration information passed into the plugin.
   *
   * @var array
   */
  protected $configuration;

  /**
   * A service to get subscriptions from.
   *
   * @var \Drupal\remotedb_role\SubscriptionServiceInterface
   */
  protected $subscription_servic;

  /**
   * AssignRoles object constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param \Drupal\remotedb_role\SubscriptionServiceInterface
   *   A service that can return subscriptions.
   *
   * @return \Drupal\remotedb_role\Plugin\Action\AssignRoles
   */
  public function __construct(array $configuration, SubscriptionServiceInterface $subscription_service) {
    $this->configuration = $configuration;
    $this->configuration += $this->defaultConfiguration();
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
    $assign = [];
    $unassign = [];
    $changed = FALSE;

    $role_names = user_roles();
    $role_settings = $this->configuration['roles'];
    $subscriptions = $this->subscription_service->getSubscriptions($account);

    if (is_array($subscriptions)) {
      // Loop through all roles and prepare a list of assign/unassign roles.
      foreach ($role_settings as $rid => $role_setting) {
        $unassign[$rid] = $role_names[$rid];
        foreach ($subscriptions as $subscription) {
          if (in_array($subscription['subscription_id'], $role_setting['subscriptions'])) {
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

    return [
      $assign,
      $unassign,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $config = [];

    $roles = user_roles(TRUE);
    unset($roles[DRUPAL_AUTHENTICATED_RID]);
    if (count($roles) > 0) {
      foreach ($roles as $rid => $role_name) {
        if (variable_get('remotedb_role_' . $rid . '_active', 0)) {
          if ($subscriptions = variable_get('remotedb_role_' . $rid . '_subscriptions', '')) {
            $subscriptions = explode("\n", $subscriptions);
            // Trim values.
            foreach ($subscriptions as $index => $subscription) {
               $subscriptions[$index] = trim($subscription);
            }
            $config['roles'][$rid]['subscriptions'] =  $subscriptions;
          }
        }
      }
    }

    return $config;
  }
}
