<?php

/**
 * @file
 * Views integration for RemoteDB-module.
 *
 * Exposes only the remotedb-uid from the users-table.
 */

/**
 * Implements hook_views_data().
 */
function remotedbuser_views_data() {
  $data['users']['remotedb_uid'] = [
    'title' => t('Remote database user ID'),
    'help' => t('ID of the user in the remote database.'),
    'field' => [
      'handler' => 'views_handler_field_numeric',
      'click sortable' => TRUE,
    ],
    'filter' => [
      'handler' => 'views_handler_filter_numeric',
    ],
    'sort' => [
      'handler' => 'views_handler_sort',
    ],
  ];

  return $data;
}
