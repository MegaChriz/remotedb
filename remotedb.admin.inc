<?php

/**
 * @file
 * Administrative page callbacks.
 */

use Drupal\remotedb\Component\String;

// ---------------------------------------------------------------------------
// ENTITY FORM
// ---------------------------------------------------------------------------

/**
 * Form callback for create and edit configuration for a remote database.
 *
 * @see remotedb_form_submit().
 */
function remotedb_form($form, $form_state) {
  $remotedb = $form_state['remotedb'];
  $info = $remotedb->entityInfo();

  $form['#attached']['js'][] = drupal_get_path('module', 'remotedb') . '/remotedb.admin.js';

  $form['label'] = array(
    '#type' => 'textfield',
    '#title' => t('Label'),
    '#maxlength' => 255,
    '#default_value' => $remotedb->label,
  );
  $form['url'] = array(
    '#type' => 'textfield',
    '#title' => t('URL'),
    '#maxlength' => 255,
    '#default_value' => $remotedb->url,
  );


  $form['authentication_methods'] = array(
    '#tree' => TRUE,
  );

  // Status.
  $form['authentication_methods']['status'] = array(
    '#type' => 'item',
    '#title' => t('Enabled authentication methods'),
    '#prefix' => '<div id="authentication-methods-status-wrapper">',
    '#suffix' => '</div>',
  );

  // Order (tabledrag).
  $form['authentication_methods']['order'] = array(
    '#type' => 'item',
    '#title' => t('Authentication method order'),
    '#theme' => 'authentication_methods_order',
  );

  // Settings.
  $form['authentication_methods_settings_title'] = array(
    '#type' => 'item',
    '#title' => t('Authentication methods settings'),
  );
  $form['authentication_methods_settings'] = array(
    '#type' => 'vertical_tabs',
  );

  $methods = $remotedb->getAuthenticationMethods();
  foreach ($methods as $name => $method) {
    $form['authentication_methods']['status'][$name] = array(
      '#type' => 'checkbox',
      '#title' => $method->getLabel(),
      '#default_value' => $method->status,
      '#parents' => array('authentication_methods', $name, 'status'),
      '#description' => $method->getDescription(),
      '#weight' => $method->weight,
    );

    $form['authentication_methods']['order'][$name]['authentication-method'] = array(
      '#markup' => $method->getLabel(),
    );
    $form['authentication_methods']['order'][$name]['weight'] = array(
      '#type' => 'weight',
      '#title' => t('Weight for @title', array('@title' => $method->getLabel())),
      '#title_display' => 'invisible',
      '#delta' => 50,
      '#default_value' => $method->weight,
      '#parents' => array('authentication_methods', $name, 'weight'),
    );
    $form['authentication_methods']['order'][$name]['#weight'] = $method->weight;

    // Retrieve the settings form of the plugin.
    $settings_form = array(
      '#parents' => array('authentication_methods', $name, 'settings'),
      '#tree' => TRUE,
    );
    $settings_form = $method->settingsForm($settings_form, $form_state);
    if (!empty($settings_form)) {
      $form['authentication_methods']['settings'][$name] = array(
        '#type' => 'fieldset',
        '#title' => $method->getLabel(),
        '#parents' => array('authentication_methods', $name, 'settings'),
        '#weight' => $method->weight,
        '#group' => 'authentication_methods_settings',
      );
      $form['authentication_methods']['settings'][$name] += $settings_form;
    }
  }


  $form['actions'] = array(
    '#type' => 'actions',
  );
  $form['actions']['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Save configuration'),
  );
  $form['actions']['cancel'] = array(
    '#type' => 'link',
    '#title' => t('Cancel'),
    '#href' => $info['admin ui']['path'],
  );

  return $form;
}

/**
 * Submit handler for remotedb_form().
 *
 * @see remotedb_form().
 */
function remotedb_form_submit($form, &$form_state) {
  // Save entity.
  $remotedb = $form_state['remotedb'];
  foreach ($form_state['values'] as $key => $value) {
    $remotedb->$key = $value;
  }
  $remotedb->save();

  // Redirect.
  $info = $remotedb->entityInfo();
  $form_state['redirect'] = $info['admin ui']['path'];
}

/**
 * Returns HTML for a text format's authentication-method order form.
 *
 * @param $variables
 *   An associative array containing:
 *   - element: A render element representing the form.
 *
 * @ingroup themeable
 */
function theme_authentication_methods_order($variables) {
  $element = $variables['element'];

  // Order (tabledrag).
  $rows = array();
  foreach (element_children($element, TRUE) as $name) {
    $element[$name]['weight']['#attributes']['class'][] = 'authentication-method-order-weight';
    $rows[] = array(
      'data' => array(
        drupal_render($element[$name]['authentication-method']),
        drupal_render($element[$name]['weight']),
      ),
      'class' => array('draggable'),
    );
  }
  $output = drupal_render_children($element);
  $output .= theme('table', array('rows' => $rows, 'attributes' => array('id' => 'authentication-method-order')));
  drupal_add_tabledrag('authentication-method-order', 'order', 'sibling', 'authentication-method-order-weight', NULL, NULL, TRUE);

  return $output;
}

// ---------------------------------------------------------------------------
// REMOTE DB TEST FORM
// ---------------------------------------------------------------------------

/**
 * Callback for: admin/config/system/remotedb/test.
 *
 * Form to perform a test request.
 *
 * @see remotedb_test_submit()
 */
function remotedb_test_form($form, $form_state) {
  if (isset($form_state['remotedb_result'])) {
    $form['remotedb_result'] = array(
      '#type' => 'item',
      '#title' => t('Result'),
      '#markup' => krumo_ob($form_state['remotedb_result']),
    );
  }

  $form['remotedb'] = array(
    '#type' => 'select',
    '#options' => entity_get_controller('remotedb')->options(),
    '#title' => t('Database'),
    '#required' => TRUE,
    '#description' => t('The remote database.'),
  );

  $form['method'] = array(
    '#type' => 'textfield',
    '#title' => t('Method'),
    '#required' => TRUE,
    '#description' => t('The method to call.'),
  );
  $form['params'] = array(
    '#type' => 'textarea',
    '#title' => t('Parameters'),
    '#description' => t('Specify the parameters to use, one on each line.'),
  );
  $form['execute'] = array(
    '#type' => 'submit',
    '#value' => t('Send request'),
  );

  return $form;
}

/**
 * Performs a test request.
 *
 * @see remotedb_test_form()
 */
function remotedb_test_form_submit($form, &$form_state) {
  $method = $form_state['values']['method'];
  $string = new String();
  $params = $string->textToArray($form_state['values']['params']);
  $remotedb = entity_load_single('remotedb', $form_state['values']['remotedb']);
  if ($remotedb) {
    $remotedb->sendRequest($method, $params);
    $form_state['remotedb_result'] = $remotedb->getResult();
  }
  $form_state['rebuild'] = TRUE;
}
