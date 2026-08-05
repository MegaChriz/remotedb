<?php

declare(strict_types=1);

namespace Drupal\remotedbuser\Hook;

use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\remotedbuser\Entity\RemotedbUserStorageInterface;
use Drupal\user\UserInterface;
use Psr\Log\LoggerInterface;

/**
 * User hook implementations for remotedbuser.
 */
class RemotedbuserUserHooks {

  use StringTranslationTrait;

  /**
   * Entity type manager service.
   */
  protected readonly EntityTypeManagerInterface $entityTypeManager;

  /**
   * Messenger service.
   */
  protected readonly MessengerInterface $messenger;

  /**
   * Current user proxy service.
   */
  protected readonly AccountProxyInterface $currentUser;

  /**
   * Logger channel for remotedb.
   */
  protected readonly LoggerInterface $logger;

  /**
   * Constructs user hook implementations.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger channel factory service.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user proxy service.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    MessengerInterface $messenger,
    LoggerChannelFactoryInterface $logger_factory,
    AccountProxyInterface $current_user,
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->messenger = $messenger;
    $this->currentUser = $current_user;
    $this->logger = $logger_factory->get('remotedb');
  }

  /**
   * Implements hook_user_presave().
   */
  #[Hook('user_presave')]
  public function userPresave(UserInterface $account): void {
    if (isset($account->from_remotedb) && $account->from_remotedb) {
      return;
    }

    $storage = $this->entityTypeManager->getStorage('remotedb_user');
    if (!$storage instanceof RemotedbUserStorageInterface) {
      throw new \LogicException('Expected remotedb_user storage to implement RemotedbUserStorageInterface.');
    }
    $remote_account = $storage->fromAccount($account);

    $result = $remote_account->save();
    if ($result === 0) {
      $this->messenger->addError('Er ging iets mis bij het opslaan van uw account. Neem contact met ons op.');
      $this->logger->error('Saving account failed for user %username (%uid)', [
        '%username' => $account->getAccountName(),
        '%uid' => $account->id(),
      ]);
      return;
    }

    if (!$account->hasField('remotedb_uid')) {
      return;
    }
    $remotedb_uid = $account->get('remotedb_uid')->value;
    $remote_values = $remote_account->toArray();
    $remote_uid = $remote_values['uid'] ?? NULL;
    if ($remotedb_uid === NULL || $remotedb_uid === '' || $remotedb_uid === 0 || $remotedb_uid === '0' || $remote_uid != $remotedb_uid) {
      $account->get('remotedb_uid')->value = $remote_uid;
    }
  }

  /**
   * Implements hook_user_view().
   */
  #[Hook('user_view')]
  public function userView(array &$build, UserInterface $account, EntityViewDisplayInterface $display): void {
    if (!$account->hasField('remotedb_uid')) {
      return;
    }
    $remotedb_uid = $account->get('remotedb_uid')->value;
    if ($remotedb_uid === NULL || $remotedb_uid === '' || $remotedb_uid === 0) {
      return;
    }
    if (!is_scalar($remotedb_uid)) {
      return;
    }
    $build['remotedb'] = [
      '#type' => 'user_profile_category',
      '#title' => $this->t('Remote Database'),
      '#access' => $this->currentUser->hasPermission('administer users'),
      'remotedb_uid' => [
        '#type' => 'user_profile_item',
        '#title' => $this->t('Remote database UID'),
        '#markup' => Html::escape((string) $remotedb_uid),
      ],
      '#weight' => 1,
    ];
  }

}
