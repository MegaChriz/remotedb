<?php

/**
 * @file
 * Administrative page callbacks.
 */

use Drupal\Core\Render\Element;
use Drupal\remotedb\Component\StringLib;
use Drupal\remotedb\Exception\RemotedbException;

// ---------------------------------------------------------------------------
// ENTITY FORM
// ---------------------------------------------------------------------------

/**
 * Form callback for create and edit configuration for a remote database.
 *
 * @see remotedb_form_submit()
 */
function remotedb_form($form, $form_state) {
  $remotedb = $form_state['remotedb'];
  $info = $remotedb->entityInfo();

  $form['#attached']['js'][] = drupal_get_path('module', 'remotedb') . '/remotedb.admin.js';

  $form['label'] = [
    '#type' => 'textfield',
    '#title' => t('Label'),
    '#maxlength' => 255,
    '#default_value' => $remotedb->label,
  ];
  $form['name'] = [
    '#type' => 'machine_name',
    '#size' => '64',
    '#default_value' => $remotedb->name,
    '#required' => TRUE,
    '#machine_name' => [
      'exists' => 'remotedb_load',
      'source' => ['label'],
      'replace_pattern' => '[^0-9a-z_\-]',
      'error' => t('Please only use lowercase alphanumeric characters, underscores (_), and hyphens (-) for style names.'),
    ],
  ];
  $form['url'] = [
    '#type' => 'textfield',
    '#title' => t('URL'),
    '#maxlength' => 255,
    '#default_value' => $remotedb->url,
  ];

  $form['authentication_methods'] = [
    '#tree' => TRUE,
  ];

  // Status.
  $form['authentication_methods']['status'] = [
    '#type' => 'item',
    '#title' => t('Enabled authentication methods'),
    '#prefix' => '<div id="authentication-methods-status-wrapper">',
    '#suffix' => '</div>',
  ];

  // Order (tabledrag).
  $form['authentication_methods']['order'] = [
    '#type' => 'item',
    '#title' => t('Authentication method order'),
    '#theme' => 'authentication_methods_order',
  ];

  // Settings.
  $form['authentication_methods_settings_title'] = [
    '#type' => 'item',
    '#title' => t('Authentication methods settings'),
  ];
  $form['authentication_methods_settings'] = [
    '#type' => 'vertical_tabs',
  ];

  $methods = $remotedb->getAuthenticationMethods();
  foreach ($methods as $name => $method) {
    $form['authentication_methods']['status'][$name] = [
      '#type' => 'checkbox',
      '#title' => $method->getLabel(),
      '#default_value' => $method->status,
      '#parents' => ['authentication_methods', $name, 'status'],
      '#description' => $method->getDescription(),
      '#weight' => $method->weight,
    ];

    $form['authentication_methods']['order'][$name]['authentication-method'] = [
      '#markup' => $method->getLabel(),
    ];
    $form['authentication_methods']['order'][$name]['weight'] = [
      '#type' => 'weight',
      '#title' => t('Weight for @title', ['@title' => $method->getLabel()]),
      '#title_display' => 'invisible',
      '#delta' => 50,
      '#default_value' => $method->weight,
      '#parents' => ['authentication_methods', $name, 'weight'],
    ];
    $form['authentication_methods']['order'][$name]['#weight'] = $method->weight;

    // Retrieve the settings form of the plugin.
    $settings_form = [
      '#parents' => ['authentication_methods', $name, 'settings'],
      '#tree' => TRUE,
    ];
    $settings_form = $method->settingsForm($settings_form, $form_state);
    if (!empty($settings_form)) {
      $form['authentication_methods']['settings'][$name] = [
        '#type' => 'fieldset',
        '#title' => $method->getLabel(),
        '#parents' => ['authentication_methods', $name, 'settings'],
        '#weight' => $method->weight,
        '#group' => 'authentication_methods_settings',
      ];
      $form['authentication_methods']['settings'][$name] += $settings_form;
    }
  }

  $form['actions'] = [
    '#type' => 'actions',
  ];
  $form['actions']['submit'] = [
    '#type' => 'submit',
    '#value' => t('Save configuration'),
  ];
  $form['actions']['cancel'] = [
    '#type' => 'link',
    '#title' => t('Cancel'),
    '#href' => $info['admin ui']['path'],
  ];

  return $form;
}

/**
 * Submit handler for remotedb_form().
 *
 * @see remotedb_form()
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
  $rows = [];
  foreach (Element::children($element, TRUE) as $name) {
    $element[$name]['weight']['#attributes']['class'][] = 'authentication-method-order-weight';
    $rows[] = [
      'data' => [
        \Drupal::service("renderer")->render($element[$name]['authentication-method']),
        \Drupal::service("renderer")->render($element[$name]['weight']),
      ],
      'class' => ['draggable'],
    ];
  }
  $output = drupal_render_children($element);
  // @FIXME
  // theme() has been renamed to _theme() and should NEVER be called directly.
  // Calling _theme() directly can alter the expected output and potentially
  // introduce security issues (see https://www.drupal.org/node/2195739). You
  // should use renderable arrays instead.
  //
  //
  // @see https://www.drupal.org/node/2195739
  // $output .= theme('table', array('rows' => $rows, 'attributes' => array('id' => 'authentication-method-order')));
  // @FIXME
  // TableDrag is now attached with the #tabledrag property of certain render
  // arrays. drupal_add_tabledrag() is now internal and should never be called directly.
  //
  //
  // @see https://www.drupal.org/node/2160571
  // drupal_add_tabledrag('authentication-method-order', 'order', 'sibling', 'authentication-method-order-weight', NULL, NULL, TRUE);
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
    if (function_exists('krumo_ob')) {
      $form['remotedb_result'] = [
        '#type' => 'item',
        '#title' => t('Result'),
        '#markup' => krumo_ob($form_state['remotedb_result']),
      ];
    }
    else {
      drupal_set_message(t('Enable the Devel module to get a more human readable representation of the response from the remote database.'));
      $form['remotedb_result'] = [
        '#type' => 'textarea',
        '#title' => t('Result'),
        '#value' => print_r($form_state['remotedb_result'], TRUE),
      ];
    }
  }

  $form['remotedb'] = [
    '#type' => 'select',
    '#options' => entity_get_controller('remotedb')->options(),
    '#title' => t('Database'),
    '#required' => TRUE,
    '#description' => t('The remote database.'),
  ];

  $form['method'] = [
    '#type' => 'textfield',
    '#title' => t('Method'),
    '#required' => TRUE,
    '#description' => t('The method to call.'),
  ];
  $form['params'] = [
    '#type' => 'textarea',
    '#title' => t('Parameters'),
    '#description' => t('Specify the parameters to use, one on each line.'),
  ];
  $form['execute'] = [
    '#type' => 'submit',
    '#value' => t('Send request'),
  ];

  return $form;
}

/**
 * Performs a test request.
 *
 * @see remotedb_test_form()
 */
function remotedb_test_form_submit($form, &$form_state) {
  $method = $form_state['values']['method'];
  $string = new StringLib();
  $params = $string->textToArray($form_state['values']['params']);
  $remotedb = entity_load_single('remotedb', $form_state['values']['remotedb']);
  if ($remotedb) {
    try {
      $form_state['remotedb_result'] = $remotedb->sendRequest($method, $params);
    }
    catch (RemotedbException $e) {
      $e->printMessage();
    }
  }
  $form_state['rebuild'] = TRUE;
}
