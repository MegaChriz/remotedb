<?php

/**
 * @file
 * Contains Drupal\remotedb_webhook\EntityOperation\OperationActionBase.
 */

namespace Drupal\remotedb_webhook\EntityOperation;

use \EntityOperationsOperationAction;

/**
 * Base class for performing actions on remotedb entities.
 */
abstract class OperationActionBase extends EntityOperationsOperationAction {

  public $access_verb = 'edit';
  // Title: "Edit %entity".

  /**
   * Returns basic information about the operation.
   */
  function operationInfo() {
    return [
      'uses form' => FALSE,
    ] + parent::operationInfo();
  }
}
