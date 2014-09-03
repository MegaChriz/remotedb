<?php

/**
 * @file
 * Administrative page callbacks.
 */

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
