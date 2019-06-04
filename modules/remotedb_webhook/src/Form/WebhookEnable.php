<?php

namespace Drupal\remotedb_webhook\Form;

use Drupal\Core\Form\FormStateInterface;

/**
 * Registers a webhook url.
 */
class WebhookEnable extends OperationActionBase {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to enable webhooks for %label?', [
      '%label' => $this->entity->label(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Enable webhook');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->webhookService->add($this->entity);

    $success = $this->webhookService->exists($this->entity);
    $args = ['%label' => $this->entity->label()];
    if ($success) {
      $this->logger('remotedb')->notice('Webhooks are enabled for %label.', $args);
      $this->messenger()->addMessage($this->t('Webhooks are enabled for %label.', $args));
    }
    else {
      $this->messenger()->addError($this->t('Enabling webhooks failed for %label. Check if the remote database is available or consult its error logs.', $args));
    }

    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}
