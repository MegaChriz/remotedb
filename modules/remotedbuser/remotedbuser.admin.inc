<?php

/**
 * @file
 * Administrative page callbacks.
 */

use Drupal\Core\Render\Element;
use Drupal\Component\Utility\Html;
use Drupal\remotedb\Exception\RemotedbException;
use Drupal\remotedbuser\Controller\RemotedbUserController;

/**
 * Get a single remote user.
 *
 * @param mixed $user_id
 *   The user to import from the remote database.
 * @param \Drupal\remotedbuser\Controller\RemotedbUserController $rd_controller
 *   (optional) The controller to use to retrieve remote users.
 */
function remotedbuser_get_remote_user($user_id, RemotedbUserController $rd_controller = NULL) {
  if (empty($rd_controller)) {
    $rd_controller = entity_get_controller('remotedb_user');
  }

  try {
    // Remove extra spaces.
    $user_id = trim($user_id);

    if (empty($user_id)) {
      // Skip "empty" users.
      return;
    }

    // Try to load by email.
    $remote_account = $rd_controller->loadBy($user_id, RemotedbUserController::BY_MAIL);
    if (!$remote_account) {
      // No success, try to load by name.
      $remote_account = $rd_controller->loadBy($user_id, RemotedbUserController::BY_NAME);
    }
    if (!$remote_account) {
      // Still no success, try to load by ID.
      $remote_account = $rd_controller->loadBy($user_id, RemotedbUserController::BY_ID);
    }

    if (isset($remote_account->uid)) {
      // Copy over account data.
      $account = $remote_account->toAccount();
      $account->save();
      $uri = entity_uri('user', $account);
      // @FIXME
      // url() expects a route name or an external URI.
      // drupal_set_message(t('User account <a href="@url">%name</a> copied over from the remote database.', array(
      //         '@url' => url($uri['path'], $uri['options']),
      //         '%name' => $account->name,
      //       )));
    }
    else {
      drupal_set_message(t('No remote user found for %user.', ['%user' => $user_id]));
    }
  }
  catch (RemotedbException $e) {
    $e->logError();
    $e->printMessage();
  }
  catch (Exception $e) {
    watchdog_exception('remotedb', $e);
    drupal_set_message($e->getMessage(), 'error');
  }
}

/**
 * Imports users from the remote database using the Batch API.
 *
 * @param array $user_ids
 *   The ids of users to import from the remote database.
 *   Ids may be the remote user id, user names or mail addresses.
 * @param int $limit_per_batch
 *   (optional) How many users should be imported per batch.
 *   Defaults to 10.
 *
 * @return void
 */
function remotedbuser_get_remote_users_batch(array $user_ids, $limit_per_batch = 10) {
  $operations = [];
  $operations[] = [
    'remotedbuser_get_remote_users_batch_operation',
    [
      $user_ids,
      $limit_per_batch,
    ],
  ];

  $batch = [
    'title' => t('Importing users from the remote database...'),
    'operations' => $operations,
    'progress_message' => '',
    'file' => drupal_get_path('module', 'remotedbuser') . '/remotedbuser.admin.inc',
  ];
  batch_set($batch);
}

/**
 * Imports the given users from the remote database.
 *
 * @param array $user_ids
 *   The ids of users to import from the remote database.
 * @param int $limit_per_batch
 *   (optional) How many users should be imported per batch.
 *   Defaults to 10.
 *
 * @return void
 */
function remotedbuser_get_remote_users_batch_operation(array $user_ids, $limit_per_batch = 10, &$context) {
  if (empty($context['sandbox'])) {
    $context['sandbox'] = [];
    $context['sandbox']['progress'] = 0;
    $context['sandbox']['max'] = count($user_ids);
    $context['sandbox']['user_ids'] = $user_ids;
  }

  $group = array_slice($context['sandbox']['user_ids'], 0, $limit_per_batch);
  $context['sandbox']['user_ids'] = array_slice($context['sandbox']['user_ids'], $limit_per_batch);

  $rd_controller = entity_get_controller('remotedb_user');

  $i = 0;
  foreach ($group as $user_id) {
    $i++;

    // Get remote user and save it locally.
    remotedbuser_get_remote_user($user_id, $rd_controller);

    $context['sandbox']['progress']++;
    $context['message'] = t('Imported @current users out of @total.', [
      '@current' => $context['sandbox']['progress'],
      '@total' => $context['sandbox']['max'],
    ]);

    if ($i >= $limit_per_batch) {
      // Stop.
      break;
    }
  }

  // Inform the batch engine that we are not finished,
  // and provide an estimation of the completion level we reached.
  $context['finished'] = ($context['sandbox']['progress'] / $context['sandbox']['max']);
}
