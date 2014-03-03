<?php
/**
 * @file
 * Admin functions for RemoteDB module.
 */

/**
 * Callback for: admin/settings/remotedb.
 * System settings form for RemoteDB.
 */
function remotedb_settings($form, &$form_state) {
  $form['remotedb'] = array(
    '#type' => 'fieldset',
    '#title' => t('Service mode'),
  );
  $form['remotedb']['remotedb_mode'] = array(
    '#type' => 'radios',
    '#title' => t('Remote service'),
    '#options' => array(
      t('Disabled'),
      t('Staging'),
      t('Live'),
    ),
    '#default_value' => variable_get('remotedb_mode', 0),
  );
  $form['remotedb']['remotedb_userdb'] = array(
    '#type' => 'radios',
    '#title' => t('User database settings'),
    '#options' => array(
      t('Use remote service only.'),
      t('Use remote service first, local user database is fallback.'),
      t('Use local user database first, remote is fallback.'),
    ),
    '#default_value' => variable_get('remotedb_userdb', 0),
  );
  $form['remotedb_service'] = array(
    '#type' => 'fieldset',
    '#title' => t('Remote service URL\'s'),
  );
  $form['remotedb_service']['remotedb_live_url'] = array(
    '#type' => 'textfield',
    '#title' => t('Live URL'),
    '#default_value' => variable_get('remotedb_live_url', ''),
    '#size' => 40,
    '#maxlength' => 255,
  );
  $form['remotedb_service']['remotedb_staging_url'] = array(
    '#type' => 'textfield',
    '#title' => t('Staging URL'),
    '#default_value' => variable_get('remotedb_staging_url', ''),
    '#size' => 40,
    '#maxlength' => 255,
  );

  $form = system_settings_form($form);
  return $form;
}
