<?php

namespace Drupal\remotedb_sso_test;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;

/**
 * Modifies the sso ticket service.
 */
class RemotedbSsoTestServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    // Overrides 'remotedb_sso.ticket' class.
    $definition = $container->getDefinition('remotedb_sso.ticket');
    $definition->setClass(MockTicketService::class);
    $definition->setFactory(NULL);
  }

}
