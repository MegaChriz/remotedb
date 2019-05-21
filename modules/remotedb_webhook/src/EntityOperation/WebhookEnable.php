<?php

/**
 * @file
 * Contains Drupal\remotedb_webhook\EntityOperation\WebhookEnable.
 */

namespace Drupal\remotedb_webhook\EntityOperation;

use \Exception;
use \InvalidArgumentException;
use Drupal\remotedb\Entity\RemotedbInterface;
use Drupal\remotedb_webhook\Webhook as WebhookUtil;

/**
 * Register a webhook url.
 */
class WebhookEnable extends OperationActionBase {
  /**
   * Returns basic information about the operation.
   */
  function operationInfo() {
    return [
      'label' => t('Enable webhook'),
      'description' => t('Enable or disable webhooks for this remote database.'),
    ] + parent::operationInfo();
  }

  /**
   * Returns strings for the operations.
   *
   * @return
   *  An array containing the following keys:
   *  - 'form': An array of strings for the operation form, containing:
   *    - 'button label'
   *    - 'confirm question'
   *    - 'submit message'
   */
  function operationStrings() {
    return [
      'tab title' => 'Enable webhook',
      'page title' => 'Enable webhook for %label',
      'button label' => t('Enable webhook'),
      'confirm question' => t('Are you sure you want to enable webhooks for %label?'),
      'submit message' => t('Webhooks are enabled for %entity-type %label.'),
    ];
  }

  /**
   * Access callback: deny access if webhook is already enabled.
   */
  function operationAccess($entity_type, $entity, $params = []) {
    try {
      if (WebhookUtil::exists($entity)) {
        return FALSE;
      }
    }
    catch (Exception $e) {
      // In case of exceptions, return FALSE.
      return FALSE;
    }
    // We only deny access; entity_access() will take over.
  }

  /**
   * The enable webhooks action.
   */
  function execute($entity_type, $entity, $parameters = []) {
    if (!($entity instanceof RemotedbInterface)) {
      throw new InvalidArgumentException('Entity must be of type \Drupal\remotedb\Entity\RemotedbInterface');
    }
    WebhookUtil::add($entity);
  }
}
