<?php

namespace Drupal\remotedb\Form;

use Drupal\Core\Form\FormStateInterface;

/**
 * Controller for remote database addition forms.
 */
class RemotedbAddForm extends RemotedbFormBase {

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $this->messenger()->addStatus($this->t('Added remote database %name.', ['%name' => $this->entity->label()]));
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    parent::save($form, $form_state);
    $form_state->setRedirect('entity.remotedb.collection');
  }

}
