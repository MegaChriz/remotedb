<?php

namespace Drupal\remotedb_webhook\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\remotedb_webhook\WebhookInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Default controller for the remotedb_webhook module.
 */
class WebhookController extends ControllerBase {

  /**
   * The webhook service.
   *
   * @var \Drupal\remotedb_webhook\WebhookInterface
   */
  protected $webhook;

  /**
   * The logger service.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Constructs a new WebhookController.
   *
   * @param \Drupal\remotedb_webhook\WebhookInterface $webhook
   *   The webhook service.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger service.
   */
  public function __construct(WebhookInterface $webhook, LoggerInterface $logger) {
    $this->webhook = $webhook;
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('remotedb_webhook.webhook'),
      $container->get('logger.factory')->get('remotedb')
    );
  }

  /**
   * Access callback for a webhook endpoint.
   *
   * @param string $key
   *   The webhook key.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The operating account.
   *
   * @return Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function processWebhookAccess($key, AccountInterface $account) {
    return AccessResult::allowedIf($key == $this->webhook->getKey());
  }

  /**
   * Processes a webhook post from remotedb.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The http request.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   A response in JSON format.
   */
  public function processWebhook(Request $request) {
    if ($request->getMethod() != 'POST') {
      $this->logger->notice('Tried to process a webhook with no post data.');
      return new JsonResponse('Remote database Webhook Endpoint.', 400);
    }

    $type = $request->get('type');
    $data = $request->get('data');

    if (empty($type) || empty($data)) {
      $this->logger->notice('Tried to process a webhook with unsufficient information.');
      return new JsonResponse("Remote database Webhook Endpoint, but missing post data for 'type' or 'data'.", 400);
    }

    if (!is_string($type)) {
      $this->logger->notice("Tried to process a webhook with malformed syntax for 'type' parameter.");
      return new JsonResponse("The parameter 'type' should be a string.", 400);
    }

    // Process webhook.
    $this->webhook->process($type, $data);

    // Allow other modules to act on a webhook.
    $this->moduleHandler()->invokeAll('remotedb_process_webhook', [
      $type,
      $data,
    ]);

    // Log event.
    $this->logger->info('Webhook type @type has been processed with the following data: @data.', [
      '@type' => $type,
      '@data' => print_r($data, TRUE),
    ]);

    return new JsonResponse();
  }

}
