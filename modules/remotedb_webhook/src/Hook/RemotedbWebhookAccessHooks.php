<?php

declare(strict_types=1);

namespace Drupal\remotedb_webhook\Hook;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Session\AccountInterface;
use Drupal\remotedb\Entity\RemotedbInterface;
use Drupal\remotedb_webhook\WebhookInterface;

/**
 * Access hook implementations for remotedb_webhook.
 */
class RemotedbWebhookAccessHooks {

  /**
   * Webhook management service.
   */
  protected readonly WebhookInterface $webhook;

  /**
   * Constructs access hook implementations.
   *
   * @param \Drupal\remotedb_webhook\WebhookInterface $webhook
   *   The webhook management service.
   */
  public function __construct(
    WebhookInterface $webhook,
  ) {
    $this->webhook = $webhook;
  }

  /**
   * Implements hook_ENTITY_TYPE_access() for 'remotedb'.
   */
  #[Hook('remotedb_access')]
  public function remotedbAccess(RemotedbInterface $remotedb, string $operation, AccountInterface $account): AccessResult {
    $has_perm = $account->hasPermission('remotedb.administer');

    switch ($operation) {
      case 'webhook_enable':
        return AccessResult::allowedIf($has_perm && !$this->webhook->exists($remotedb));

      case 'webhook_disable':
        return AccessResult::allowedIf($has_perm && $this->webhook->exists($remotedb));
    }

    return AccessResult::neutral();
  }

}
