<?php
/**
 * @file
 * Admin functions for RemoteDB Role module.
 */

/**
 * Admin settings form for RemoteDB Role
 */
function remotedb_role_settings($form, $form_state) {
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
    '#collapsed' => !variable_get('remotedb_role_debug', FALSE),
  );

  // Debug setting
  $form['remotedb_role_extra']['remotedb_role_debug'] = array(
    '#type' => 'checkbox',
    '#title' => t('Debug'),
    '#description' => t('If enabled, a message about which roles were assigned/unassigned will be displayed when users login.'),
    '#default_value' => variable_get('remotedb_role_debug', FALSE),
  );
  $form['remotedb_role_extra']['remotedb_role_subscription_afasget'] = array(
    '#type' => 'checkbox',
    '#title' => t('Get subscribtions from Afas'),
    '#description' => t('If enabled, the remote database will be triggered <em>once</em> to reload subscriptions from afas.'),
  );

  $form['#submit'][] = 'remotedb_role_settings_submit';

  $form = system_settings_form($form);
  return $form;
}

/**
 * Submit handler for RemoteDB Role admin settings form
 */
function remotedb_role_settings_submit($form, $form_state) {
  if ($form_state['values']['remotedb_role_subscription_afasget']) {
    // Trigger database to reload subscriptions.
    $result = RemoteDB::get()->sendRequest('subscription.afasget')->getResult();
    drupal_set_message(t('Response from remote database') . ': ' . $result);
  }
}
