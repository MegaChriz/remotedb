<?php

namespace Drupal\remotedbuser\Form;

use Drupal\user\Form\UserLoginForm as UserLoginFormBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Authenticates users via the remote database.
 */
class UserLoginForm extends UserLoginFormBase {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('user.flood_control'),
      $container->get('entity_type.manager')->getStorage('user'),
      $container->get('remotedbuser.authentication'),
      $container->get('renderer'),
      $container->get('bare_html_page_renderer')
    );
  }

}
