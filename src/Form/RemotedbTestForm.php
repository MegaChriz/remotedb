<?php

namespace Drupal\remotedb\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\FormBase;
use Drupal\remotedb\RemotedbException;

/**
 * Provides a form for manually performing a request to the remote database.
 */
class RemotedbTestForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'remotedb_test_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    if (!$form_state->get(['remotedb_result'])) {
      if (function_exists('krumo_ob')) {
        $form['remotedb_result'] = [
          '#type' => 'item',
          '#title' => t('Result'),
          '#markup' => krumo_ob($form_state->get([
            'remotedb_result',
          ])),
        ];
      }
      else {
        drupal_set_message(t('Enable the Devel module to get a more human readable representation of the response from the remote database.'));
        $form['remotedb_result'] = [
          '#type' => 'textarea',
          '#title' => t('Result'),
          '#value' => print_r($form_state->get([
            'remotedb_result',
          ]), TRUE),
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
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $method = $form_state->getValue(['method']);
    $string = new StringLib();
    $params = $string->textToArray($form_state->getValue(['params']));
    $remotedb = entity_load_single('remotedb', $form_state->getValue(['remotedb']));
    if ($remotedb) {
      try {
        $form_state->set(['remotedb_result'], $remotedb->sendRequest($method, $params));
      }

      catch (RemotedbException $e) {
        $e->printMessage();
      }
    }
    $form_state->setRebuild(TRUE);
  }

}
