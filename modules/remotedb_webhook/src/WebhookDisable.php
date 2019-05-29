<?php

namespace Drupal\remotedb_webhook;

/**
 * Removes a webhook url.
 */
class WebhookDisable extends OperationActionBase {

  /**
   * Returns basic information about the operation.
   */
  public function operationInfo() {
    return [
      'label' => t('Disable webhook'),
      'description' => t('Enable or disable webhooks for this remote database.'),
    ] + parent::operationInfo();
  }

  /**
   * Returns strings for the operations.
   *
   * @return
   *   An array containing the following keys:
   *   - 'form': An array of strings for the operation form, containing:
   *    - 'button label'
   *    - 'confirm question'
   *    - 'submit message'
   */
  public function operationStrings() {
    return [
      'tab title' => 'Disable webhook',
      'page title' => 'Disable webhook for %label',
      'button label' => t('Disable webhook'),
      'confirm question' => t('Are you sure you want to disable webhooks for %label?'),
      'submit message' => t('Webhooks are disabled for %entity-type %label.'),
    ];
  }

  /**
   * Access callback: deny access if webhook is already enabled.
   */
  public function operationAccess($entity_type, $entity, $params = []) {
    try {
      if (!WebhookUtil::exists($entity)) {
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
   * The disable webhooks action.
   */
  public function execute($entity_type, $entity, $parameters = []) {
    if (!($entity instanceof RemotedbInterface)) {
      throw new InvalidArgumentException('Entity must be of type \Drupal\remotedb\Entity\RemotedbInterface');
    }
    WebhookUtil::delete($entity);
  }

}
