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

  $form = system_settings_form($form);
  return $form;
}
