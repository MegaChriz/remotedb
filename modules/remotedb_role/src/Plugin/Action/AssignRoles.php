<?php

namespace Drupal\remotedb_role\Plugin\Action;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Action\ActionBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\remotedb_role\SubscriptionServiceInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Assigns or unassigns roles based on the subscriptions the user has.
 *
 * @Action(
 *   id = "remotedb_role_assign_roles",
 *   label = @Translation("Assign and unassign roles"),
 *   type = "user"
 * )
 */
class AssignRoles extends ActionBase implements ContainerFactoryPluginInterface {

  /**
   * A service to get subscriptions from.
   *
   * @var \Drupal\remotedb_role\SubscriptionServiceInterface
   */
  protected $subscriptionService;

  /**
   * A list of roles that were assigned by this action.
   *
   * @var array
   */
  protected $assigned = [];

  /**
   * A list of roles that were unassigned by this action.
   *
   * @var array
   */
  protected $unassigned = [];

  /**
   * AssignRoles object constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\remotedb_role\SubscriptionServiceInterface $subscription_service
   *   A service that can return subscriptions.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, SubscriptionServiceInterface $subscription_service) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->subscriptionService = $subscription_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    if (!isset($configuration['roles'])) {
      $configuration['roles'] = $container->get('config.factory')
        ->get('remotedb_role.settings')
        ->get('roles');
    }

    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('remotedb_role.subscription')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    $result = AccessResult::allowed();
    return $return_as_object ? $result : $result->isAllowed();
  }

  /**
   * {@inheritdoc}
   */
  public function execute($account = NULL) {
    if (empty($account)) {
      // No account given. Abort.
      return;
    }

    if ($account->hasPermission('remotedb_role.bypass')) {
      // Roles of this account may not be assigned/revoked. Abort.
      return;
    }

    $role_settings = $this->configuration['roles'];
    $subscriptions = $this->subscriptionService->getSubscriptions($account);

    // Keep track of roles that were assigned and roles that were unassigned.
    $this->assigned = [];
    $this->unassigned = [];
    $changed = FALSE;

    if (is_array($subscriptions)) {
      // Loop through all roles and prepare a list of assign/unassign roles.
      foreach ($role_settings as $rid => $role_setting) {
        if (empty($role_setting['status'])) {
          continue;
        }

        $this->unassigned[$rid] = $rid;
        foreach ($subscriptions as $subscription) {
          if (in_array($subscription['subscription_id'], $role_setting['subscriptions'])) {
            $this->assigned[$rid] = $rid;
            unset($this->unassigned[$rid]);
          }
        }
      }

      // Unassign roles.
      foreach ($this->unassigned as $rid) {
        if ($account->hasRole($rid)) {
          $account->removeRole($rid);
          $changed = TRUE;
        }
        else {
          unset($this->unassigned[$rid]);
        }
      }
      // Assign roles.
      foreach ($this->assigned as $rid) {
        if (!$account->hasRole($rid)) {
          $account->addRole($rid);
          $changed = TRUE;
        }
        else {
          unset($this->assigned[$rid]);
        }
      }
    }

    if ($changed) {
      $account->save();
    }
  }

  /**
   * Returns the roles that got assigned by the latest execute call.
   *
   * @return array
   *   A list of assigned role ID's.
   */
  public function getAssignedRoles() {
    return $this->assigned;
  }

  /**
   * Returns the roles that got assigned by the latest execute call.
   *
   * @return array
   *   A list of assigned role ID's.
   */
  public function getUnassignedRoles() {
    return $this->unassigned;
  }

}
