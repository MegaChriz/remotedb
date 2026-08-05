<?php

declare(strict_types=1);

namespace Drupal\remotedb_role\Hook;

use Drupal\Core\Action\ActionManager;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Utility\Error;
use Drupal\remotedb_role\Plugin\Action\AssignRoles;
use Drupal\user\UserInterface;
use Psr\Log\LoggerInterface;

/**
 * User hook implementations for remotedb_role.
 */
class RemotedbRoleUserHooks {

  use StringTranslationTrait;

  /**
   * Action manager service.
   */
  protected readonly ActionManager $actionManager;

  /**
   * Messenger service.
   */
  protected readonly MessengerInterface $messenger;

  /**
   * Logger channel for remotedb.
   */
  protected readonly LoggerInterface $remotedbLogger;

  /**
   * Logger channel for remotedb_role.
   */
  protected readonly LoggerInterface $roleLogger;

  /**
   * Remotedb_role module settings.
   */
  protected readonly ImmutableConfig $settings;

  /**
   * Constructs user hook implementations.
   *
   * @param \Drupal\Core\Action\ActionManager $action_manager
   *   The action manager service.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger channel factory service.
   */
  public function __construct(
    ActionManager $action_manager,
    MessengerInterface $messenger,
    ConfigFactoryInterface $config_factory,
    LoggerChannelFactoryInterface $logger_factory,
  ) {
    $this->actionManager = $action_manager;
    $this->messenger = $messenger;
    $this->settings = $config_factory->get('remotedb_role.settings');
    $this->remotedbLogger = $logger_factory->get('remotedb');
    $this->roleLogger = $logger_factory->get('remotedb_role');
  }

  /**
   * Implements hook_user_login().
   */
  #[Hook('user_login')]
  public function userLogin(UserInterface $account): void {
    $action = $this->actionManager->createInstance('remotedb_role_assign_roles');

    if (!$action instanceof AssignRoles) {
      $this->roleLogger->error('Could not apply roles because the action plugin is not of the expected type. Should have been @expected but is @actual.', [
        '@expected' => AssignRoles::class,
        '@actual' => get_class($action),
      ]);
      return;
    }

    try {
      $action->execute($account);

      if ((bool) $this->settings->get('debug')) {
        $assigned = $action->getAssignedRoles();
        $unassigned = $action->getUnassignedRoles();

        $this->messenger->addStatus($this->t('Assigned: @assigned', [
          '@assigned' => $assigned !== [] ? implode(', ', $assigned) : 'none',
        ]));
        $this->messenger->addStatus($this->t('Unassigned: @unassigned', [
          '@unassigned' => $unassigned !== [] ? implode(', ', $unassigned) : 'none',
        ]));
      }
    }
    catch (\Exception $e) {
      Error::logException($this->remotedbLogger, $e);
    }
  }

}
