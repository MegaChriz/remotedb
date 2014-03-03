<?php
/**
 * @file
 * Admin functions for RemoteDB module.
 */

/**
 * Callback for: admin/settings/remotedb.
 * System settings form for RemoteDB.
 */
function uc_remotedb_settings($form, &$form_state) {
  $form['uc_remotedb_sendtodb'] = array(
    '#type' => 'fieldset',
    '#title' => t('Send to database settings'),
  );

  // Select a view
  $options = array(
    '' => t('Select one...'),
  );
  $views = views_get_all_views();
  foreach ($views as $view) {
    if ($view->base_table == 'uc_orders') {
      foreach ($view->display as $display) {
        $options[$view->name . '|' . $display->id] = $view->name . ' - ' . $display->display_title;
      }
    }
  }
  $form['uc_remotedb_sendtodb']['uc_remotedb_order_export_view'] = array(
    '#type' => 'select',
    '#title' => t('View with Ubercart orders to send to database'),
    '#description' => t('Select a view that contains orders that should be send to the remote database.'),
    '#default_value' => variable_get('uc_remotedb_order_export_view', NULL),
    '#options' => $options,
    '#required' => TRUE,
  );

  // Export frequency
  $form['uc_remotedb_sendtodb']['uc_remotedb_order_export_freq'] = array(
    '#type' => 'select',
    '#title' => t('Export frequency'),
    '#description' => t('The frequency at which automatic exports should occur.'),
    '#options' => array(
      '1 second' => t('Every cron run'),
      '1 hour' => t('Once an hour'),
      '6 hours' => t('Every six hours'),
      '12 hours' => t('Every twelve hours'),
      '1 day' => t('Once a day'),
    ),
    '#default_value' => variable_get('uc_remotedb_order_export_freq', '1 day'),
  );

  // Order status
  $options = array(
    '' => t('Select one...'),
  );
  // Create an array of order status options.
  foreach (uc_order_status_list('general') as $uc_status) {
    $options[$uc_status['id']] = $uc_status['title'];
  }
  $form['uc_remotedb_sendtodb']['uc_remotedb_order_export_status'] = array(
    '#type' => 'select',
    '#title' => t('New order status'),
    '#description' => t('Select a status used to mark orders as exported.'),
    '#default_value' => variable_get('uc_remotedb_order_export_status', NULL),
    '#options' => $options,
    '#required' => TRUE,
  );

  $form = system_settings_form($form);
  return $form;
}
