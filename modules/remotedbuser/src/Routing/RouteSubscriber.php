<?php

namespace Drupal\remotedbuser\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to the dynamic route events.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    if ($route = $collection->get('user.login')) {
      $route->setDefault('_form', '\Drupal\remotedbuser\Form\UserLoginForm');
    }
    if ($route = $collection->get('user.pass')) {
      $route->setDefault('_form', '\Drupal\remotedbuser\Form\UserPasswordForm');
    }
  }

}
