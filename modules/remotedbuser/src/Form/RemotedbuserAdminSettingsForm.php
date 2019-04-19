<?php

namespace Drupal\remotedbuser\Form;

use Drupal\Component\Utility\Html;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;

/**
 *
 */
class RemotedbuserAdminSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'remotedbuser_admin_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('remotedbuser.settings');

    foreach (Element::children($form) as $variable) {
      $config->set($variable, $form_state->getValue($form[$variable]['#parents']));
    }
    $config->save();

    if (method_exists($this, '_submitForm')) {
      $this->_submitForm($form, $form_state);
    }

    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['remotedbuser.settings'];
  }

  /**
   *
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['remotedbuser_remotedb'] = [
      '#type' => 'select',
      '#options' => entity_get_controller('remotedb')->options(),
      '#title' => t('Database'),
      '#required' => TRUE,
      '#description' => t('The remote database.'),
      '#default_value' => remotedbuser_variable_get('remotedb'),
    ];
    $form['remotedbuser_login'] = [
      '#type' => 'radios',
      '#title' => t('Login settings'),
      '#options' => [
        REMOTEDB_REMOTEONLY => t('Use remote service only.'),
        REMOTEDB_REMOTEFIRST => t('Use remote service first, local user database is fallback.'),
        REMOTEDB_LOCALFIRST => t('Use local user database first, remote is fallback.'),
      ],
      '#default_value' => remotedbuser_variable_get('login'),
    ];

    $sync_properties_options = [];
    $property_info = entity_get_property_info('user');
    foreach ($property_info['properties'] as $key => $property) {
      switch ($key) {
        case 'uid':
        case 'remotedb_uid':
          // Never sync these properties.
          continue;

        default:
          $sync_properties_options[$key] = Html::escape($property['label']);
          break;
      }
    }
    // Add password as option as well.
    $sync_properties_options['pass'] = t('Password');
    $form['remotedbuser_sync_properties'] = [
      '#type' => 'checkboxes',
      '#title' => t('Synchronize properties'),
      '#description' => t('Select which properties to sync.'),
      '#options' => $sync_properties_options,
      '#default_value' => remotedbuser_variable_get('sync_properties'),
      '#process' => [
        'form_process_checkboxes',
        'remotedbuser_sync_properties_disable',
      ],
    ];

    $form = parent::buildForm($form, $form_state);
    return $form;
  }

}
