<?php

namespace Drupal\remotedb_sso\EventSubscriber;

use Drupal\Core\Url;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Redirects user to sso login route.
 */
class SsoLoginRedirect implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = [];
    $events[KernelEvents::REQUEST][] = ['onRequest', 400];
    return $events;
  }

  /**
   * A method to be called whenever a kernel.request event is dispatched.
   *
   * It invokes a rabbit hole behavior on an entity in the request if
   * applicable.
   *
   * @param \Symfony\Component\HttpKernel\Event\RequestEvent $event
   *   The event triggered by the request.
   */
  public function onRequest(RequestEvent $event) {
    $request = $event->getRequest();

    // Don't process events with HTTP exceptions - those have either been thrown
    // by us or have nothing to do with rabbit hole.
    if ($request->get('exception') != NULL) {
      return;
    }

    $path = $request->getPathInfo();
    if (strpos($path, '/sso/login/') === 0) {
      $pattern = '/^\/sso\/login\/([0-9a-z]+)\/([0-9a-z]+)\/([0-9a-z\-\_]+)\/?(.*)/i';
      $route_parameters = [
        'remotedb_uid' => preg_replace($pattern, '${1}', $path),
        'timestamp' => preg_replace($pattern, '${2}', $path),
        'hashed_pass' => preg_replace($pattern, '${3}', $path),
      ];
      $options['query'] = [
        'target_path' => preg_replace($pattern, '${4}', $path),
      ] + $request->query->all();
      $response = new RedirectResponse(Url::fromRoute('remotedb_sso.login', $route_parameters, $options)->toString(), 302);
      $event->setResponse($response);
    }
  }

}
