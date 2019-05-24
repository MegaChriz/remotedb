<?php

namespace Drupal\remotedb_role\Plugin\Action;

use Drupal\Component\Plugin\ConfigurableInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Action\ActionBase;
use Drupal\Core\Entity\EntityTypeInterface;
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
   * AssignRoles object constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\remotedb_role\SubscriptionServiceInterface
   *   A service that can return subscriptions.
   *
   * @return \Drupal\remotedb_role\Plugin\Action\AssignRoles
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeInterface $entity_type, SubscriptionServiceInterface $subscription_service) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->subscriptionService = $subscription_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    if (!isset($configuration['roles'])) {
      $configuration['roles'] = $container->get('remotedb_role.settings')->get('roles');
    }

    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')->getDefinition('user_role'),
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

    $role_settings = $this->configuration['roles'];
    $subscriptions = $this->subscriptionService->getSubscriptions($account);

    // Keep track of roles that were assigned and roles that were unassigned.
    $assign = [];
    $unassign = [];
    $changed = FALSE;

    if (is_array($subscriptions)) {
      // Loop through all roles and prepare a list of assign/unassign roles.
      foreach ($role_settings as $rid => $role_setting) {
        if (empty($role_setting['status'])) {
          continue;
        }

        $unassign[$rid] = $rid;
        foreach ($subscriptions as $subscription) {
          if (in_array($subscription['subscription_id'], $role_setting['subscriptions'])) {
            $assign[$rid] = $rid;
            unset($unassign[$rid]);
          }
        }
      }

      // Unassign roles.
      foreach ($unassign as $rid) {
        if ($account->hasRole($rid)) {
          $account->removeRole($rid);
          $changed = TRUE;
        }
      }
      // Assign roles.
      foreach ($assign as $rid) {
        if (!$account->hasRole($rid)) {
          $account->addRole($rid);
          $changed = TRUE;
        }
      }
    }

    if ($changed) {
      $account->save();
    }
  }

}
