<?php

namespace Drupal\remotedb_webhook\Form;

use Drupal\Core\Entity\EntityConfirmFormBase;
use Drupal\remotedb_webhook\WebhookInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for performing actions on remotedb entities.
 */
abstract class OperationActionBase extends EntityConfirmFormBase {

  /**
   * The webhook service.
   *
   * @var \Drupal\remotedb_webhook\WebhookInterface
   */
  protected $webhookService;

  /**
   * Constructs a new OperationActionBase object.
   *
   * @param \Drupal\remotedb_webhook\WebhookInterface $webhook_service
   *   The webhook service.
   */
  public function __construct(WebhookInterface $webhook_service) {
    $this->webhookService = $webhook_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('remotedb_webhook.webhook')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return $this->entity->toUrl();
  }

}
