<?php

/**
 * @file
 * Administrative page callbacks.
 */

/**
 * Admin settings form for Remote database SSO.
 */
function remotedb_sso_settings_form($form, $form_state) {
  $form['remotedb_sso_websites'] = array(
    '#type' => 'textarea',
    '#title' => t('Websites'),
    '#description' => t('Specify to which external websites an SSO link automatically must created, one on each line. Omit the http://, but include the subdomain if necassery, such as "www".'),
    '#default_value' => remotedb_sso_variable_get('websites'),
  );
  $form = system_settings_form($form);
  return $form;
}
