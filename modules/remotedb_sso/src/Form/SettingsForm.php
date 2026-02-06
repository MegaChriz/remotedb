<?php

namespace Drupal\remotedb_sso\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines a form that configures remotedb_sso settings.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'remotedb_sso_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['remotedb_sso.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('remotedb_sso.settings');
    $websites = $config->get('websites');
    if (!is_array($websites)) {
      $websites = [];
    }

    $form['websites'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Websites'),
      '#description' => $this->t('Specify to which external websites an SSO link automatically must created, one on each line. Omit the http://, but include the subdomain if necassery, such as "www".'),
      '#default_value' => $websites !== [] ? implode("\n", $websites) : NULL,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $values = $form_state->getValues();

    if (isset($values['websites']) && is_string($values['websites']) && strlen(trim($values['websites'])) > 0) {
      $values['websites'] = explode("\n", $values['websites']);
      $values['websites'] = array_map('trim', $values['websites']);
    }
    else {
      $values['websites'] = [];
    }

    $this->config('remotedb_sso.settings')
      ->set('websites', $values['websites'])
      ->save();

    parent::submitForm($form, $form_state);
  }

}
