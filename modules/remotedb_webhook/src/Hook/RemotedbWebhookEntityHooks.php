<?php

declare(strict_types=1);

namespace Drupal\remotedb_webhook\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\remotedb_webhook\Form\WebhookDisable;
use Drupal\remotedb_webhook\Form\WebhookEnable;

/**
 * Entity hook implementations for remotedb_webhook.
 */
class RemotedbWebhookEntityHooks {

  /**
   * Implements hook_entity_type_build().
   */
  #[Hook('entity_type_build')]
  public function entityTypeBuild(array &$entity_types): void {
    if (!isset($entity_types['remotedb'])) {
      return;
    }

    $entity_types['remotedb']
      ->setFormClass('webhook_enable', WebhookEnable::class)
      ->setFormClass('webhook_disable', WebhookDisable::class);
  }

}
