<?php

namespace Drupal\remotedb\Form;

use Drupal\Core\Form\FormStateInterface;

/**
 * Controller for editing a remote database.
 */
class RemotedbEditForm extends RemotedbFormBase {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form['#title'] = $this->t('Edit remote database %name', ['%name' => $this->entity->label()]);
    $form = parent::form($form, $form_state);
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    parent::save($form, $form_state);
    $this->messenger()->addStatus($this->t('Updated remote database %name.', ['%name' => $this->entity->label()]));
    return $this->entity;
  }

}
