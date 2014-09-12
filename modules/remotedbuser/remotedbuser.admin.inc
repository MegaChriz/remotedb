<?php

/**
 * @file
 * Administrative page callbacks.
 */

use Drupal\remotedbuser\Controller\RemotedbUserController;

/**
 * Callback for: admin/settings/remotedb/user.
 *
 * System settings form for Remote database user.
 */
function remotedbuser_admin_settings_form($form, &$form_state) {
  $form['remotedbuser_remotedb'] = array(
    '#type' => 'select',
    '#options' => entity_get_controller('remotedb')->options(),
    '#title' => t('Database'),
    '#required' => TRUE,
    '#description' => t('The remote database.'),
    '#default_value' => remotedbuser_variable_get('remotedb'),
  );
  $form['remotedbuser_login'] = array(
    '#type' => 'radios',
    '#title' => t('Login settings'),
    '#options' => array(
      REMOTEDB_REMOTEONLY => t('Use remote service only.'),
      REMOTEDB_REMOTEFIRST => t('Use remote service first, local user database is fallback.'),
      REMOTEDB_LOCALFIRST => t('Use local user database first, remote is fallback.'),
    ),
    '#default_value' => remotedbuser_variable_get('login'),
  );

  $sync_properties_options = array();
  $property_info = entity_get_property_info('user');
  foreach ($property_info['properties'] as $key => $property) {
    switch ($key) {
      case 'uid':
      case 'remotedb_uid':
        // Never sync these properties.
        continue;

      default:
        $sync_properties_options[$key] = check_plain($property['label']);
        break;
    }
  }
  // Add password as option as well.
  $sync_properties_options['pass'] = t('Password');
  $form['remotedbuser_sync_properties'] = array(
    '#type' => 'checkboxes',
    '#title' => t('Synchronize properties'),
    '#description' => t('Select which properties to sync.'),
    '#options' => $sync_properties_options,
    '#default_value' => remotedbuser_variable_get('sync_properties'),
    '#process' => array(
      'form_process_checkboxes',
      'remotedbuser_sync_properties_disable',
    ),
  );

  $form = system_settings_form($form);
  return $form;
}

/**
 * Process callback for remotedbuser_sync_properties.
 *
 * Makes sure 'name' and 'mail' checkboxes can not be edited.
 *
 * @see remotedbuser_admin_settings_form()
 */
function remotedbuser_sync_properties_disable(&$element) {
  foreach (element_children($element) as $key) {
    switch ($key) {
      case 'name':
      case 'mail':
        $element[$key]['#disabled'] = TRUE;
        break;
    }
  }
  return $element;
}

/**
 * Form callback for admin/config/services/remotedb/user/get
 *
 * Copy over an user from the remote database.
 *
 * @see remotedbuser_get_remote_user_form_submit()
 */
function remotedbuser_get_remote_user_form($form, $form_state) {
  $form['user'] = array(
    '#type' => 'textarea',
    '#title' => t('Remote users (ID, username or mail address)'),
    '#description' => t('Put in the user IDs, usernames or mail addresses of the users to copy over from the remote database. Put one on each line.'),
    '#required' => TRUE,
  );
  $form['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Get'),
  );

  return $form;
}

/**
 * Submit handler for remotedbuser_get_remote_user_form().
 *
 * @see remotedbuser_get_remote_user_form()
 * @todo Use batch API.
 */
function remotedbuser_get_remote_user_form_submit($form, $form_state) {
  $user_ids = explode("\n", $form_state['values']['user']);

  $rd_controller = entity_get_controller('remotedb_user');

  foreach ($user_ids as $user_id) {
    // Remove extra spaces.
    $user_id = trim($user_id);

    if (empty($user_id)) {
      // Skip "empty" users.
      continue;
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
      entity_save('user', $account);
      $uri = entity_uri('user', $account);
      drupal_set_message(t('User account <a href="@url">%name</a> copied over from the remote database.', array(
        '@url' => url($uri['path'], $uri['options']),
        '%name' => $account->name,
      )));
    }
    else {
      drupal_set_message(t('No remote user found for %user.', array('%user' => $user_id)));
    }
  }
}
