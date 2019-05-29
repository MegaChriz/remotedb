<?php

namespace Drupal\remotedb_webhook\Form;

use Drupal\Core\Form\FormStateInterface;

/**
 * Removes a webhook url.
 */
class WebhookDisable extends OperationActionBase {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to disable webhooks for %label?', [
      '%label' => $this->entity->label(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Disable webhook');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->webhookService->delete($this->entity);
    $this->clearCache();

    $success = !$this->webhookService->exists($this->entity);
    $args = ['%label' => $this->entity->label()];
    if ($success) {
      $this->logger('remotedb')->notice('Webhooks are disabled for %label.', $args);
      $this->messenger()->addMessage($this->t('Webhooks are disabled for %label.', $args));
    }
    else {
      $this->messenger()->addError($this->t('Disabling webhooks failed for %label. Check if the remote database is available or consult its error logs.', $args));
    }

    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}
