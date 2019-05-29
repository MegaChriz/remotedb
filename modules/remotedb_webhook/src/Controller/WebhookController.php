<?php

namespace Drupal\remotedb_webhook\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\remotedb_webhook\WebhookInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

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
   * @return ???
   */
  public function processWebhook(Request $request) {
    // @todo get post data from request.

    if (empty($_POST)) {
      $this->logger->notice('Tried to process a webhook with no post data.', []);
      return 'Remote database Webhook Endpoint.';
    }
    if (empty($_POST['data']) || empty($_POST['type'])) {
      $this->logger->notice('Tried to process a webhook with unsufficient information.', []);
      return;
    }

    $data = $_POST['data'];
    $type = $_POST['type'];

    $this->webhook->process($type, $data);

    // Allow other modules to act on a webhook.
    $this->moduleHandler()->invokeAll('remotedb_process_webhook', [
      $type,
      $data,
    ]);

    // Log event.
    $this->logger->info('Webhook type @type has been processed.', [
      '@type' => $type,
    ]);

    return NULL;
  }

}
