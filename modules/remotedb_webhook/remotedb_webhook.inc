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
    watchdog('remotedb', 'Tried to process a webhook with no post data.', array(), WATCHDOG_NOTICE);
    return 'Remote database Webhook Endpoint.';
  }
  if (empty($_POST['data']) || empty($_POST['type'])) {
    watchdog('remotedb', 'Tried to process a webhook with unsufficient information.', array(), WATCHDOG_NOTICE);
    return;
  }

  $data = $_POST['data'];
  $type = $_POST['type'];

  Webhook::process($type, $data);

  // Allow other modules to act on a webhook.
  module_invoke_all('remotedb_process_webhook', $type, $data);

  // Log event.
  watchdog('remotedb', 'Webhook type @type has been processed.', array('@type' => $type), WATCHDOG_INFO);

  return NULL;
}
