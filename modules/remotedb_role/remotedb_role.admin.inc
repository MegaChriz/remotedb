<?php

/**
 * @file
 * Administrative page callbacks.
 */

/**
 * Admin settings form for RemoteDB Role
 */
function remotedb_role_settings_form($form, $form_state) {
  $form['remotedb_role_remotedb'] = array(
    '#type' => 'select',
    '#options' => entity_get_controller('remotedb')->options(),
    '#title' => t('Database'),
    '#required' => TRUE,
    '#description' => t('The remote database.'),
    '#default_value' => remotedb_role_variable_get('remotedb'),
  );

  $roles = user_roles(TRUE);
  unset($roles[DRUPAL_AUTHENTICATED_RID]);

  $form['settings'] = array(
    '#type' => 'vertical_tabs',
    '#attached' => array(
      'js' => array(
        drupal_get_path('module', 'remotedb_role') . '/remotedb_role.js',
        array(
          'data' => array('roles' => $roles),
          'type' => 'setting',
        ),
      ),
    ),
  );

  if (count($roles) > 0) {
    foreach ($roles as $rid => $role_name) {
      if (isset($form_state['input']['remotedb_role_' . $rid . '_active'])) {
        $active = $form_state['input']['remotedb_role_' . $rid . '_active'];
      }
      else {
        $active = variable_get('remotedb_role_' . $rid . '_active', 0);
      }
      $subscriptions = variable_get('remotedb_role_' . $rid . '_subscriptions', '');

      $form['remotedb_role_' . $rid] = array(
        '#type' => 'fieldset',
        '#title' => $role_name,
        '#collapsible' => TRUE,
        '#collapsed' => ($active ? FALSE : TRUE),
        '#group' => 'settings',
      );

      // Enable/Disable
      $form['remotedb_role_' . $rid]['remotedb_role_' . $rid . '_active'] = array(
        '#type' => 'radios',
        '#title' => check_plain($role_name) . ' ' . t('status'),
        '#default_value' => $active,
        '#description' => t('Enable or disable this role for automatic role assignment via remote database.'),
        '#options' => array(1 => t('Enabled'), 0 => t('Disabled')),
      );
      $form['remotedb_role_' . $rid]['remotedb_role_' . $rid . '_subscriptions'] = array(
        '#type' => 'textarea',
        '#title' => t('Subscriptions'),
        '#default_value' => variable_get('remotedb_role_' . $rid . '_subscriptions', ''),
        '#description' => t('Specify which subscriptions should give the user the role %role. Enter one per line.', array('%role' => $role_name)),
        '#required' => ($active) ? TRUE : FALSE,
      );
    }
  }

  $form['remotedb_role_extra'] = array(
    '#type' => 'fieldset',
    '#title' => t('Extra'),
    '#collapsible' => TRUE,
    '#collapsed' => !remotedb_role_variable_get('debug'),
  );

  // Debug settings.
  $form['remotedb_role_extra']['remotedb_role_debug'] = array(
    '#type' => 'checkbox',
    '#title' => t('Debug'),
    '#description' => t('If enabled, a message about which roles were assigned/unassigned will be displayed when users login.'),
    '#default_value' => remotedb_role_variable_get('debug'),
  );

  $form = system_settings_form($form);
  return $form;
}
