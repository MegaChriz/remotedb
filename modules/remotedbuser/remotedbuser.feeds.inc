<?php

/**
 * @file
 * Feeds hooks.
 */

use Drupal\remotedbuser\Controller\RemotedbUserController;

/**
 * Implements hook_feeds_processor_targets_alter().
 */
function remotedbuser_feeds_processor_targets_alter(&$targets, $entity_type, $bundle_name) {
  $info = entity_get_property_info($entity_type);

  foreach ($info['properties'] as $property_key => $property_info) {
    if (isset($property_info['type']) && $property_info['type'] == 'user') {
      $targets[$property_key . ':remotedb_user'] = [
        'name' => t('@label: by remote user', ['@label' => $property_info['label']]),
        'description' => t('Get user via remote database.'),
        'callback' => 'remotedbuser_feeds_set_user',
      ];
    }
  }
}

/**
 * Feeds target callback for setting user on the entity.
 *
 * @see remotedbuser_feeds_processor_targets_alter()
 */
function remotedbuser_feeds_set_user($source, $entity, $target, array $values, $mapping) {
  list($property_key, $field) = explode(':', $target);

  // Get value.
  $user_id = reset($values);
  $user_id = trim($user_id);

  // First, search in local database.
  // Try to load by email.
  $users = \Drupal::entityTypeManager()->getStorage('user')->loadByProperties(['mail' => $user_id]);
  $account = reset($users);
  if (!$account) {
    // No success, try to load by name.
    $users = \Drupal::entityTypeManager()->getStorage('user')->loadByProperties(['name' => $user_id]);
    $account = reset($users);
  }

  if (!isset($account->uid)) {
    // Search in remote database.
    $rd_controller = \Drupal::entityTypeManager()->getStorage('remotedb_user');
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
    }
  }

  if (isset($account->uid)) {
    // Set value.
    $importer = $source->importer();
    $entity_type = $importer->processor->entityType();
    $entity_wrapper = entity_metadata_wrapper($entity_type, $entity);
    $entity_wrapper->$property_key = $account->uid;
  }
}
