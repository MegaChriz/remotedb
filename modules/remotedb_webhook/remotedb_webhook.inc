<?php

/**
 * @file
 * Functions that are not needed on every request.
 */

use Drupal\remotedb_webhook\Webhook;

/**
 * Processes a webhook post from remotedb.
 */
function remotedb_webhook_process_webhook() {
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
  \Drupal::moduleHandler()->invokeAll('remotedb_process_webhook', [$type, $data]);

  // Log event.
  \Drupal::logger('remotedb')->info('Webhook type @type has been processed.', ['@type' => $type]);

  return NULL;
}
