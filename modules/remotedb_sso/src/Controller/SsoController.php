<?php

namespace Drupal\remotedb_sso\Controller;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Url;
use Drupal\remotedb\Exception\RemotedbException;
use Drupal\remotedb_sso\TicketServiceInterface;
use Drupal\user\UserStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Controller for remotedb_sso routes.
 */
class SsoController extends ControllerBase {

  /**
   * The current active user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * The user storage.
   *
   * @var \Drupal\user\UserStorageInterface
   */
  protected $userStorage;

  /**
   * The service for requesting tickets from the remote database.
   *
   * @var \Drupal\remotedb_sso\TicketServiceInterface|null
   */
  protected $ticketService;

  /**
   * Constructs a new SsoController object.
   *
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current active user.
   * @param \Drupal\user\UserStorageInterface $user_storage
   *   The user storage.
   * @param \Drupal\remotedb_sso\TicketServiceInterface $ticket_service
   *   (optional) The service for requesting tickets from the remote database.
   */
  public function __construct(AccountProxyInterface $current_user, UserStorageInterface $user_storage, TicketServiceInterface $ticket_service = NULL) {
    $this->currentUser = $current_user;
    $this->userStorage = $user_storage;
    $this->ticketService = $ticket_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    try {
      $ticket_service = $container->get('remotedb_sso.ticket');
    }
    catch (RemotedbException $e) {
      // Log remotedb exceptions, but continue.
      $e->logError();
      $ticket_service = NULL;
    }

    return new static(
      $container->get('current_user'),
      $container->get('entity_type.manager')->getStorage('user'),
      $ticket_service
    );
  }

  /**
   * Logs in the specified user.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   * @param int $remotedb_uid
   *   User ID from the central database.
   * @param int $timestamp
   *   Timestamp generated by the central database.
   * @param string $hashed_pass
   *   Hash generated by the central database.
   */
  public function login(Request $request, $remotedb_uid, $timestamp, $hashed_pass) {
    $target_path = $request->query->get('target_path');
    $query = $request->query->all();
    unset($query['target_path']);

    $destination = Url::fromUserInput('/' . $target_path, [
      'query' => $query,
    ]);
    if ($destination->isRouted()) {
      $route_name = $destination->getRouteName();
      $route_parameters = $destination->getRouteParameters();
      $options = $destination->getOptions();
    }
    else {
      // Not a valid path. We will redirect to the front page later instead.
      $route_name = '<front>';
      $route_parameters = [];
      $options = [];
    }

    // Check if the ticket service is available.
    if (empty($this->ticketService)) {
      // Ticket service is not available, so ignore the attempt and go to the
      // target url.
      return $this->redirect($route_name, $route_parameters, $options);
    }

    if ($this->currentUser->isAuthenticated()) {
      // An user is already logged in, so ignore the attempt and go to the
      // target url.
      return $this->redirect($route_name, $route_parameters, $options);
    }

    if (!$remotedb_uid) {
      // The user anonymous, so ignore the attempt and go to the target url.
      return $this->redirect($route_name, $route_parameters, $options);
    }

    // Validate the ticket.
    try {
      $remote_account = $this->ticketService->validateTicket($remotedb_uid, $timestamp, $hashed_pass);
      if ($remote_account) {
        // Ticket is valid. Update account data in local database.
        $account = $remote_account->toAccount();
        $account->save();

        // Reload the user's account object to ensure a full user object is
        // passed along to the various hooks.
        $this->userStorage->resetCache();
        $account = $this->userStorage->load($account->id());

        // Now login the user.
        user_login_finalize($account);
      }
    }
    catch (RemotedbException $e) {
      // Log any remote database exceptions.
      $e->logError();
    }
    catch (Exception $e) {
      // Log any other exceptions.
      watchdog_exception('remotedb', $e);
    }

    // Finally, perform the redirect.
    return $this->redirect($route_name, $route_parameters, $options);
  }

  /**
   * Redirect the user to the website they wish to login at.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The http request.
   */
  public function goto(Request $request) {
    // Check if the ticket service is available.
    if (empty($this->ticketService)) {
      throw new NotFoundHttpException();
    }

    // Get the site to go to.
    $site = $request->query->get('site');
    if (!$site) {
      throw new NotFoundHttpException();
    }

    // Get path, if there is one.
    $path = $request->query->get('path');

    // Check if the current user is authenticated.
    if ($this->currentUser->isAuthenticated()) {
      // Get ticket.
      $ticket = $this->ticketService->getTicket($this->currentUser);

      // Generate url to redirect to.
      $url_parts = [
        'http:/',
        $site,
        'sso/login',
        $ticket,
        $path,
      ];
    }
    else {
      // Not authenticated. Skip requesting a ticket. Instead redirect to the
      // plain url.
      $url_parts = [
        'http:/',
        $site,
        $path,
      ];
    }

    // Filter out empty parts.
    $url_parts = array_filter($url_parts);
    $url = implode('/', $url_parts);

    $response = new TrustedRedirectResponse($url, 307);
    $build = [
      '#cache' => [
        'max-age' => 0,
      ],
    ];
    $cache_metadata = CacheableMetadata::createFromRenderArray($build);
    $response->addCacheableDependency($cache_metadata);
    return $response;
  }

}
