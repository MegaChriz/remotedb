<?php

namespace Drupal\remotedb_webhook\Controller;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Controller\ControllerBase;

/**
 * Default controller for the remotedb_webhook module.
 */
class DefaultController extends ControllerBase {

  /**
   *
   */
  public function remotedb_webhook_process_webhook_access($key, AccountInterface $account) {
    return $key == Webhook::getKey();
  }

  /**
   *
   */
  public function remotedb_webhook_process_webhook() {
    if (empty($_POST)) {
      \Drupal::logger('remotedb')->notice('Tried to process a webhook with no post data.', []);
      return 'Remote database Webhook Endpoint.';
    }
    if (empty($_POST['data']) || empty($_POST['type'])) {
      \Drupal::logger('remotedb')->notice('Tried to process a webhook with unsufficient information.', []);
      return;
    }

    $data = $_POST['data'];
    $type = $_POST['type'];

    Webhook::process($type, $data);

    // Allow other modules to act on a webhook.
    \Drupal::moduleHandler()->invokeAll('remotedb_process_webhook', [
      $type,
      $data,
    ]);

    // Log event.
    \Drupal::logger('remotedb')->info('Webhook type @type has been processed.', [
      '@type' => $type,
    ]);

    return NULL;
  }

}
