<?php

namespace Drupal\remotedbuser\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to the dynamic route events.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection): void {
    $route = $collection->get('user.login');
    if ($route instanceof Route) {
      $route->setDefault('_form', '\Drupal\remotedbuser\Form\UserLoginForm');
    }
    $route = $collection->get('user.pass');
    if ($route instanceof Route) {
      $route->setDefault('_form', '\Drupal\remotedbuser\Form\UserPasswordForm');
    }
  }

}
