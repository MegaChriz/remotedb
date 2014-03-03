<?php
/**
 * @file
 * Import/export functions for Ubercart orders.
 */

// ---------------------------------------------------------------------------
// REMOTE DB FUNCTIONS
// ---------------------------------------------------------------------------

/**
 * Sends orders to remote database.
 *
 * @return ???
 */
function uc_remotedb_export_orders() {
  // Load all orders ready for export.
  $uc_remotedb_order_export_view = variable_get('uc_remotedb_order_export_view', NULL);
  if (!$uc_remotedb_order_export_view) {
    // No view has been selected. Abort export.
    watchdog(
      'remotedb',
      'The order export is aborted, because there is no view specified to pull the results from. Go to the <a href="@admin-settings-url">Ubercart settings page</a> for RemoteDB to set this.',
      array(
        '@admin-settings-url' => url('admin/config/remotedb/ubercart'),
      ),
      WATCHDOG_ERROR
    );
    return;
  }
  $params = explode('|', $uc_remotedb_order_export_view);
  $view_results = views_get_view_result($params[0], $params[1]);

  if (count($view_results) > 0) {
    foreach ($view_results as $view_result) {
      if (!isset($view_result->order_id)) {
        $variables = array(
          '%view' => $params[0],
          '%display_id' => $params[1],
          '%order_id' => 'order_id',
        );
        watchdog('remotedb', 'The view %view with display %display_id has no field called %order_id. The order export is aborted.', $variables, WATCHDOG_ERROR);
        drupal_set_message(t('The view %view with display %display_id has no field called %order_id. The order export is aborted.', $variables), 'error');
        return;
      }
      // Load the order object.
      $order_orig = uc_order_load($view_result->order_id);
      $order_string = serialize($order_orig);
      $order = unserialize($order_string);
      // Format for database.
      uc_remotedb_prepare_order_for_export($order);
      // Keep array of orders to modify after successfull export.
      $orders_orig[$order_orig->order_id] = $order_orig;
      $orders[$order->order_id] = $order;
    }

    // Send orders to the remote database.
    $server_address = $_SERVER['SERVER_ADDR'];
    if ($_SERVER['SERVER_PORT'] != '80') {
      $server_address .= ':' . $_SERVER['SERVER_PORT'];
    }
    $result = RemoteDB::get()->sendRequest('order.post', array($server_address, $orders))->getResult();

    if (is_array($result)) {
      // Update status for all orders that were saved (if set).
      if (isset($result['saved']) && is_array($result['saved']) && count($result['saved']) > 0) {
        if ($order_export_status = variable_get('uc_remotedb_order_export_status')) {
          $order_ids = array();
          // Find out which orders to save.
          foreach ($result['saved'] as $order) {
            if (isset($order['uc_order_id'])) {
              $uc_order_id = $order['uc_order_id'];
              $order_ids[$uc_order_id] = $uc_order_id;
            }
          }
          // Update orders.
          foreach ($orders_orig as $order_id => $order_orig) {
            if (isset($order_ids[$order_orig->order_id])) {
              $order_orig->order_status = $order_export_status;
              uc_order_save($order_orig);
            }
          }
        }
      }

      // Errors.
      if (isset($result['errors']) && is_array($result['errors']) && count($result['errors']) > 0) {
        foreach ($result['errors'] as $error) {
          drupal_set_message($error, 'error');
        }
      }
    }
  }
  else {
    watchdog('remotedb', 'No orders to send to the remote database.', array(), WATCHDOG_INFO);
    return t('No orders to send to the remote database.');
  }

  // Update the order export timestamp.
  variable_set('uc_remotedb_order_export_last', REQUEST_TIME);

  // Prepare variables for log message.
  $log_message_vars = array(
    '!send' => 0,
    '!saved' => 0,
    '@orders_unsaved' => t('None'),
    '@orders_saved' => t('None'),
  );
  // Find out how much orders were send and how much were saved.
  if (!empty($orders_orig) && count($orders_orig) > 0) {
    $orders_send = count($orders_orig);
    $orders_saved = 0;
    if (!empty($order_ids) && count($order_ids) > 0) {
      $orders_saved = count($order_ids);
    }
    $log_message_vars['!send'] = $orders_send;
    $log_message_vars['!saved'] = $orders_saved;

    // Find out which orders were not saved.
    $unsaved = array();
    $order_ids_flipped = array();
    if (!empty($order_ids) && count($order_ids) > 0) {
      $order_ids_flipped = array_flip($order_ids);
    }
    foreach ($orders_orig as $order_orig_id => $order_orig) {
      if (!isset($order_ids_flipped[$order_orig_id])) {
        $unsaved[] = $order_orig_id;
      }
    }
    if (count($unsaved) > 0) {
      $log_message_vars['@orders_unsaved'] = implode(', ', $unsaved);
    }
  }

  if (is_object($result) && isset($result->message) && isset($result->is_error) && $result->is_error) {
    // There was an error when connecting with the remote database. Report the error.
    watchdog('remotedb', '@error', array('@error' => $result->message), WATCHDOG_ERROR);
    drupal_set_message($result->message, 'error');
    return;
  }
  elseif (!empty($order_ids) && count($order_ids) > 0) {
    $log_message_vars['@orders_saved'] = implode(', ', $order_ids);
  }

  // Log message.
  watchdog('remotedb', "Saved !saved of !send orders into the remote database.\nFailed: @orders_unsaved.\nSucceeded: @orders_saved.", $log_message_vars, WATCHDOG_INFO);
  return $return_message;
}

/**
 * Prepare an order for being send to the remote database.
 *
 * @param object $order
 *   The original Ubercart order.
 *
 * @return void
 */
function uc_remotedb_prepare_order_for_export($order) {
  $order->uc_order_id = $order->order_id;
  $order->email = $order->primary_email;
  $order->order_date = $order->created;
  $order->order_modified = $order->modified;

  // Add Afas customer ID if available.
  $account = user_load($order->uid);
  if (!empty($account->afas_dbid)) {
    $order->afas_dbid = $account->afas_dbid;
  }

  // Preprare addresses.
  if (isset($order->uc_addresses)) {
    $order->delivery_address = $order->uc_addresses['shipping']->getFieldData();
    $order->billing_address = $order->uc_addresses['billing']->getFieldData();

    $address_types = array(
      'delivery_',
      'billing_',
    );

    // Fix addresses.
    foreach ($address_types as $address_type) {
      foreach ($order->{$address_type . 'address'} as $fieldname => $value) {
        // Substract ucxf prefix if available.
        if (strpos($fieldname, 'ucxf_') === 0) {
          $fieldname = substr($fieldname, strlen('ucxf_'));
        }
        // Don't allow n/a values.
        if ($value == t('n/a')) {
          $value = '';
        }

        $order->{$address_type . 'address'}[$fieldname] = $value;
      }
    }
  }

  // Add quote context.
  if (isset($order->quote['method']) && !empty($order->line_items)) {
    // Find shipping quote line item and add the shipping method as 'model'.
    foreach ($order->line_items as $key => $item) {
      if ($item['type'] == 'shipping') {
        $order->line_items[$key]['shipping_method'] = $order->quote['method'];
      }
    }
  }

  // Add order comments.
  $order->comments = uc_remotedb_compile_comments($order->order_id);

  // Delete unnessecary order data.
  unset($order->uc_addresses);
  unset($order->data);
  unset($order->rdf_mapping);
  foreach ($order->products as $product) {
    unset($product->order);
    unset($product->data);
    unset($product->rdf_mapping);
  }
  foreach ($order->line_items as $index => $item) {
    unset($order->line_items[$index]['data']);
  }
}

/**
 * Send order data to remote db.
 *
 * This is a test function
 *
 * @param int $order_id
 *
 * @return string
 */
function uc_remotedb_test_send_order($order_id) {
  $order = uc_order_load($order_id);

  // Format for database.
  uc_remotedb_prepare_order_for_export($order);

  $server_address = $_SERVER['SERVER_ADDR'];
  if ($_SERVER['SERVER_PORT'] != '80') {
    $server_address .= ':' . $_SERVER['SERVER_PORT'];
  }
  return RemoteDB::get()->sendRequest('order.post', array($server_address, array($order)))->getResult();
}
/*
// To execute above code:
module_load_include('inc', 'uc_remotedb');
uc_remotedb_test_send_order(24);
*/

/**
 * Get orders from remote database.
 *
 * @return ???
 * @todo Implement!
 */
function uc_remotedb_import_orders() {
  $result = RemoteDB::get()->sendRequest('order.get', $_SERVER['HTTP_HOST'])->getResult();
  if (is_array($result) && count($result) > 0) {
    foreach ($result as $order_values) {
      if (isset($order_values['uc_order_id'])) {
        $order_orig = uc_order_load($order_values['uc_order_id']);
        $order = uc_order_load($order_values['uc_order_id']);
      }
      else {
        $order = uc_order_new();
      }

      // Update order info
      foreach ($order_values as $key => $value) {
        if (!is_array($value)) {
          $order->$key = $value;
        }
      }

      // Update product info
      $order->products = array();
      foreach ($order_values['products'] as $product_values) {
        $product = new stdClass();
        foreach ($product_values as $key => $value) {
          $product->$key = $value;
        }
        if (isset($product_values['uc_order_product_id'])) {
          $product->order_product_id = $product_values['uc_order_product_id'];
        }
        $order->products[] = $product;
      }
      uc_order_save($order);
    }
  }
  print_r_tree(get_defined_vars());
  die();
}

/**
 * Generates comments to be send as text.
 *
 * @param int $order_id
 *   The ID of the order to compile comments for.
 *
 * @return string
 */
function uc_remotedb_compile_comments($order_id) {
  $output = '';

  // Load comments data.  
  $comments = uc_order_comments_load($order_id);
  $admin_comments = uc_order_comments_load($order_id, TRUE);
  // Prepare comments data for output.
  $export_comments = array();
  $export_comments_tbl = array(
    array(
      t('Date'),
      t('Comment'),
    ),
  );
  foreach ($comments as $comment) {
    // Send only comments that "contain" data. Comments can contain a single
    // dash, so they have to be longer than 1 character to qualify for
    // sending to Profit.
    if (!empty($comment->message) && strlen($comment->message) > 1) {
      $export_comments[] = $comment->message;
      $export_comments_tbl[] = array(
        date('d-m-Y H:i', $comment->created),
        $comment->message,
      );
    }
  }
  $export_admin_comments = array();
  $export_admin_comments_tbl = array(
    array(
      t('Date'),
      t('Comment'),
    ),
  );
  foreach ($admin_comments as $comment) {
    // Send only comments that "contain" data. Comments can contain a single
    // dash, so they have to be longer than 1 character to qualify for
    // sending to Profit.
    if (!empty($comment->message) && strlen($comment->message) > 1) {
      $export_admin_comments[] = $comment->message;
      $export_admin_comments_tbl[] = array(
        date('d-m-Y H:i', $comment->created),
        $comment->message,
      );
    }
  }

  return implode("\n---\n\n", $export_comments);

  // Generate output.
  if (module_exists('libraries') && libraries_get_path('text-table')) {
    $output .= "\n----------------------\nOPMERKINGEN BIJ DE BESTELLING\n----------------------\n";
    include_once(libraries_get_path('text-table') . '/stringGrid.class.php');
    $oTable = new stringGrid();
    $tabArray = array();
    $tabArray['colWidth']  = array(20, 60);
    $tabArray['hAlign']    = array(0, 0); # align 0->left, 1->center, 2 -> right
    $tabArray['vAlign']    = array(0, 0);
    $output .= $oTable->GetCollumned($export_comments_tbl, $tabArray);
    $output .= "\n----------------------\nCOMMENTAAR VAN DE BEHEERDER\n----------------------\n";
    $output .= $oTable->GetCollumned($export_admin_comments_tbl, $tabArray);
  }
  else {
    if (count($export_comments) > 0) {
      $output .= "\n----------------------\nOPMERKINGEN BIJ DE BESTELLING\n----------------------\n";
      $output .= implode("\n----------------------\n", $export_comments);
      $output .= "\n";
    }
    if (count($export_admin_comments) > 0) {
      $output .= "\n----------------------\nCOMMENTAAR VAN DE BEHEERDER\n----------------------\n";
      $output .= implode("\n----------------------\n", $export_admin_comments);
      $output .= "\n";
    }
  }
  return $output;
}
