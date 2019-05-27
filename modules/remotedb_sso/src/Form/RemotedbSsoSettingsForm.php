<?php

/**
 * @file
 * Contains \Drupal\remotedb_sso\Form\RemotedbSsoSettingsForm.
 */

namespace Drupal\remotedb_sso\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;

class RemotedbSsoSettingsForm extends ConfigFormBase {

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

  public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $form['remotedb_sso_websites'] = [
      '#type' => 'textarea',
      '#title' => t('Websites'),
      '#description' => t('Specify to which external websites an SSO link automatically must created, one on each line. Omit the http://, but include the subdomain if necassery, such as "www".'),
      '#default_value' => Util::variableGet('websites'),
    ];
    $form = parent::buildForm($form, $form_state);
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('remotedb_sso.settings');

    foreach (Element::children($form) as $variable) {
      $config->set($variable, $form_state->getValue($form[$variable]['#parents']));
    }
    $config->save();

    if (method_exists($this, '_submitForm')) {
      $this->_submitForm($form, $form_state);
    }

    parent::submitForm($form, $form_state);
  }

}
